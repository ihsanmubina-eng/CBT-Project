<?php

namespace App\Http\Controllers;

use App\Models\DetailJadwal;
use App\Models\HistoriPengerjaan;
use App\Models\JadwalPiket;
use App\Models\KehadiranSiswa;
use App\Models\MasterGedung;
use App\Models\MasterToilet;
use App\Models\Siswa;
use App\Models\TokenAkses;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DatabaseController extends Controller
{
    public function index(): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return redirect()->route(session()->has('petugas.token_id') ? 'petugas.dashboard' : 'login');
    }

    public function login(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        if (session()->has('petugas.token_id')) {
            return redirect()->route('petugas.dashboard');
        }

        return view('auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()->withErrors(['username' => 'Username atau kata sandi tidak cocok.'])->withInput($request->only('username'));
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))->with('success', 'Berhasil masuk ke panel ResikKita.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Kamu sudah keluar dari panel.');
    }

    public function loginStudent(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'token' => ['required', 'string', 'max:30'],
            'team' => ['required', 'integer', 'between:1,4'],
        ]);
        $token = TokenAkses::query()
            ->where('kode_token', Str::upper(trim($input['token'])))
            ->whereDate('tanggal', today())
            ->where('status', 'Aktif')
            ->latest('id')
            ->first();

        if (! $token || ! $this->tokenIsValid($token)) {
            return back()->withErrors(['token' => 'Token tidak aktif untuk hari ini. Minta kode terbaru dari guru.'])->withInput();
        }

        $team = (int) $input['team'];
        $schedule = $this->activeScheduleForTeam($token, $team);
        if (! $schedule) {
            return back()->withErrors(['team' => "Belum ada jadwal untuk Regu {$team} pada hari ini."])->withInput();
        }

        $request->session()->regenerate();
        $request->session()->put(['petugas.token_id' => $token->id, 'petugas.regu' => $team]);

        return redirect()->route('petugas.dashboard')->with('success', 'Token aktif. Tugas regu kamu sudah dibuka.');
    }

    public function petugasDashboard(): View|RedirectResponse
    {
        $token = $this->currentPetugasToken();
        if (! $token) {
            session()->forget(['petugas.token_id', 'petugas.regu']);

            return redirect()->route('login')->with('error', 'Token sudah tidak aktif. Minta token baru dari guru.');
        }

        $team = (int) session('petugas.regu');
        $schedule = $this->activeScheduleForTeam($token, $team);
        abort_unless($schedule, 404);
        $report = $schedule->report;

        return view('petugas.dashboard', [
            'schedule' => $this->scheduleRow($schedule),
            'report' => $report ? [
                'status_kebersihan' => $report->status_kebersihan,
                'catatan' => $report->catatan,
                'status_kunci' => $report->status_kunci,
                'waktu_kirim' => $report->created_at->format('H:i'),
            ] : null,
            'team' => $team,
        ]);
    }

    public function submitReport(Request $request): RedirectResponse
    {
        $token = $this->currentPetugasToken();
        if (! $token) {
            return redirect()->route('login')->with('error', 'Token sudah tidak aktif. Minta token baru dari guru.');
        }

        $team = (int) session('petugas.regu');
        $schedule = $this->activeScheduleForTeam($token, $team);
        abort_unless($schedule, 404);
        if ($schedule->report()->exists()) {
            return back()->with('error', 'Laporan regu ini sudah dikirim dan terkunci.');
        }

        $input = $request->validate([
            'status_kebersihan' => ['required', 'in:Bersih,Perlu dibersihkan,Rusak'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($schedule, $token, $team, $input): void {
            HistoriPengerjaan::create([
                'tanggal' => today(),
                'jadwal_id' => $schedule->id,
                'token_id' => $token->id,
                'toilet_id' => $schedule->toilet_id,
                'pelapor_id' => null,
                'status_kebersihan' => $input['status_kebersihan'],
                'catatan' => $input['catatan'] ?? null,
                'status_kunci' => 'Terkunci',
            ]);
            $schedule->details()->update(['status_penugasan' => 'Selesai']);
            foreach ($schedule->students()->get() as $student) {
                KehadiranSiswa::updateOrCreate(
                    ['tanggal' => today(), 'siswa_id' => $student->id],
                    ['status_kehadiran' => 'Hadir'],
                );
            }
        });

        return redirect()->route('petugas.dashboard')->with('success', 'Laporan sudah dikirim dan dikunci.');
    }

    public function logoutStudent(Request $request): RedirectResponse
    {
        $request->session()->forget(['petugas.token_id', 'petugas.regu']);
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Akses regu sudah ditutup.');
    }

    public function dashboard(): View
    {
        $token = $this->todayToken();
        $day = $this->indonesianDay(today()->dayOfWeekIso);
        $week = $token?->minggu_ke ?? $this->currentRotationWeek();
        $assignments = JadwalPiket::query()
            ->where('status_jadwal', 'Aktif')
            ->where('hari', $day)
            ->where('minggu_ke', $week)
            ->with(['toilet.building', 'students', 'details', 'report'])
            ->orderBy('nama_regu')
            ->get()
            ->map(fn (JadwalPiket $schedule) => $this->scheduleRow($schedule))
            ->all();
        $toilets = MasterToilet::with('building')->orderBy('gedung_id')->orderBy('nama_toilet')->get()
            ->map(fn (MasterToilet $toilet) => $this->toiletRow($toilet))->all();
        $buildings = MasterGedung::withCount('toilets')->orderBy('nama_gedung')->get()
            ->map(fn (MasterGedung $building) => ['id' => $building->id, 'name' => $building->nama_gedung, 'description' => $building->deskripsi, 'toilet_count' => $building->toilets_count])->all();
        $reports = HistoriPengerjaan::with(['schedule.toilet.building', 'toilet.building'])
            ->whereDate('tanggal', today())->latest('created_at')->get()
            ->map(fn (HistoriPengerjaan $report) => $this->reportRow($report))->all();

        return view('admin.dashboard', [
            'assignments' => $assignments,
            'buildings' => $buildings,
            'toilets' => $toilets,
            'reports' => $reports,
            'token' => $token?->kode_token,
            'tokenWeek' => $week,
            'finished' => collect($assignments)->where('status', 'Selesai')->count(),
            'activeToilets' => MasterToilet::where('status_toilet', 'Aktif')->count(),
        ]);
    }

    public function generateToken(Request $request): RedirectResponse
    {
        $input = $request->validate(['week' => ['required', 'integer', 'between:1,4']]);
        $week = (int) $input['week'];
        if (! JadwalPiket::where('status_jadwal', 'Aktif')->where('hari', $this->indonesianDay(today()->dayOfWeekIso))->where('minggu_ke', $week)->exists()) {
            return back()->withErrors(['week' => 'Belum ada jadwal aktif untuk minggu ini.']);
        }

        TokenAkses::whereDate('tanggal', today())->where('status', 'Aktif')->update(['status' => 'Dicabut']);
        $token = TokenAkses::create([
            'kode_token' => 'PKT-'.random_int(1000, 9999),
            'tanggal' => today(),
            'minggu_ke' => $week,
            'berlaku_mulai' => now()->format('H:i:s'),
            'berlaku_sampai' => '23:59:59',
            'dibuat_oleh' => Auth::id(),
            'status' => 'Aktif',
        ]);

        return back()->with('success', "Token {$token->kode_token} aktif untuk jadwal minggu {$week} sampai akhir hari ini.");
    }

    public function users(): View
    {
        return view('admin.users', ['users' => User::orderBy('nama_lengkap')->get()
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->nama_lengkap, 'username' => $user->username, 'role' => $user->role])->all()]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:40', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'role' => ['required', 'in:Admin,Guru,Kesiswaan'],
        ]);

        User::create([
            'nama_lengkap' => $input['name'],
            'username' => $input['username'],
            'password' => $input['password'],
            'role' => $input['role'],
        ]);

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function deleteUser(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        if ($user->is(Auth::user())) {
            return back()->with('error', 'Akun yang sedang digunakan tidak bisa dihapus.');
        }
        if ($user->tokens()->exists() || $user->koreksiLaporan()->exists()) {
            return back()->with('error', 'Akun memiliki riwayat token atau koreksi; ubah statusnya melalui pengelolaan akun.');
        }

        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }

    public function students(): View
    {
        $students = Siswa::withCount('details')->orderBy('nama_lengkap')->get()->map(fn (Siswa $student) => $this->studentRow($student))->all();

        return view('admin.students', compact('students'));
    }

    public function storeStudent(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'nis' => ['required', 'string', 'max:30', 'unique:siswa,nis'],
            'name' => ['required', 'string', 'max:100'],
            'class' => ['required', 'string', 'max:40'],
        ]);
        Siswa::create(['nis' => $input['nis'], 'nama_lengkap' => $input['name'], 'rombel' => $input['class']]);

        return back()->with('success', 'Data siswa berhasil disimpan.');
    }

    public function importStudents(Request $request): RedirectResponse
    {
        $request->validate(['student_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $handle = fopen($request->file('student_file')->getRealPath(), 'rb');
        if ($handle === false) {
            return back()->withErrors(['student_file' => 'File CSV tidak bisa dibaca.']);
        }

        $headers = fgetcsv($handle, 0, ',');
        if ($headers === false) {
            fclose($handle);

            return back()->withErrors(['student_file' => 'File CSV kosong.']);
        }
        $headers = array_map(fn ($header) => Str::lower(trim((string) $header, "\xEF\xBB\xBF \t\n\r\0\x0B")), $headers);
        $required = ['nis', 'nama_lengkap', 'rombel'];
        if (array_diff($required, $headers)) {
            fclose($handle);

            return back()->withErrors(['student_file' => 'Kolom CSV siswa harus memuat: nis,nama_lengkap,rombel.']);
        }

        $columns = array_flip($headers);
        $rows = [];
        $seen = [];
        $line = 1;
        while (($values = fgetcsv($handle, 0, ',')) !== false) {
            $line++;
            if ($values === [null] || count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            if (count($values) !== count($headers)) {
                fclose($handle);

                return back()->withErrors(['student_file' => "Jumlah kolom pada baris {$line} tidak sesuai template."]);
            }

            $row = [
                'nis' => trim((string) $values[$columns['nis']]),
                'nama_lengkap' => trim((string) $values[$columns['nama_lengkap']]),
                'rombel' => trim((string) $values[$columns['rombel']]),
            ];
            $validator = Validator::make($row, [
                'nis' => ['required', 'string', 'max:30'],
                'nama_lengkap' => ['required', 'string', 'max:100'],
                'rombel' => ['required', 'string', 'max:40'],
            ]);
            if ($validator->fails() || isset($seen[$row['nis']])) {
                fclose($handle);

                return back()->withErrors(['student_file' => "Baris {$line}: ".($validator->errors()->first() ?: 'NIS duplikat pada file.')]);
            }
            $seen[$row['nis']] = true;
            $rows[] = $row;
            if (count($rows) > 5000) {
                fclose($handle);

                return back()->withErrors(['student_file' => 'Maksimal 5.000 siswa per file.']);
            }
        }
        fclose($handle);
        if ($rows === []) {
            return back()->withErrors(['student_file' => 'File CSV tidak berisi data siswa.']);
        }

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                Siswa::updateOrCreate(['nis' => $row['nis']], [
                    'nama_lengkap' => $row['nama_lengkap'],
                    'rombel' => $row['rombel'],
                    'status_siswa' => 'Aktif',
                    'tanggal_mutasi' => null,
                    'jenis_mutasi' => null,
                    'catatan_mutasi' => null,
                ]);
            }
        });

        return back()->with('success', count($rows).' data siswa berhasil diimpor.');
    }

    public function archiveStudent(Request $request, int $id): RedirectResponse
    {
        $input = $request->validate([
            'tanggal_mutasi' => ['required', 'date'],
            'jenis_mutasi' => ['required', 'in:Pindah Sekolah,Mutasi Kelas,Lulus'],
            'catatan_mutasi' => ['nullable', 'string', 'max:500'],
        ]);
        $student = Siswa::findOrFail($id);
        $student->update(array_merge($input, ['status_siswa' => $input['jenis_mutasi'] === 'Lulus' ? 'Lulus' : 'Mutasi']));

        return back()->with('success', 'Siswa dipindahkan ke arsip mutasi.');
    }

    public function deleteStudent(int $id): RedirectResponse
    {
        $student = Siswa::findOrFail($id);
        if ($student->details()->exists() || $student->attendance()->exists() || $student->reports()->exists()) {
            return back()->with('error', 'Siswa memiliki histori. Arsipkan siswa agar riwayatnya tetap tersimpan.');
        }
        $student->delete();

        return back()->with('success', 'Data siswa berhasil dihapus.');
    }

    public function schedule(): View
    {
        $schedules = JadwalPiket::where('status_jadwal', 'Aktif')->with(['toilet.building', 'students', 'details', 'report'])
            ->orderByRaw("CASE hari WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 ELSE 6 END")
            ->orderBy('minggu_ke')->orderBy('nama_regu')->get()
            ->map(fn (JadwalPiket $schedule) => $this->scheduleRow($schedule))->all();
        $buildings = MasterGedung::orderBy('nama_gedung')->get()->map(fn (MasterGedung $building) => [
            'id' => $building->id, 'name' => $building->nama_gedung, 'description' => $building->deskripsi,
        ])->all();

        return view('admin.schedule', compact('schedules', 'buildings'));
    }

    public function storeSchedule(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'day' => ['required', 'in:Senin,Selasa,Rabu,Kamis,Jumat'],
            'week' => ['required', 'integer', 'between:1,4'],
            'team' => ['required', 'integer', 'between:1,4'],
            'building' => ['required', 'string', 'exists:master_gedung,nama_gedung'],
            'room' => ['required', 'string', 'max:100'],
            'students' => ['required', 'string', 'max:500'],
            'class' => ['required', 'string', 'max:40'],
        ]);
        $toilet = $this->findToilet($input['building'], $input['room']);
        if (! $toilet) {
            return back()->withErrors(['room' => 'Lokasi toilet tidak ditemukan. Tambahkan toilet di Master Toilet dahulu.'])->withInput();
        }
        $students = $this->resolveStudents($input['students'], $input['class']);
        if ($students === null) {
            return back()->withErrors(['students' => 'Ada siswa yang belum terdaftar di Data Siswa. Impor roster siswa dahulu.'])->withInput();
        }
        if (JadwalPiket::where('hari', $input['day'])->where('minggu_ke', $input['week'])
            ->where('nama_regu', $input['team'])->where('status_jadwal', 'Aktif')->exists()) {
            return back()->withErrors(['team' => 'Regu ini sudah memiliki jadwal aktif pada hari dan minggu tersebut.'])->withInput();
        }

        DB::transaction(function () use ($input, $toilet, $students): void {
            $schedule = JadwalPiket::create([
                'hari' => $input['day'], 'minggu_ke' => $input['week'], 'nama_regu' => $input['team'],
                'toilet_id' => $toilet->id, 'status_jadwal' => 'Aktif',
            ]);
            foreach ($students as $student) {
                $schedule->details()->create(['siswa_id' => $student->id, 'status_penugasan' => 'Aktif']);
            }
        });

        return back()->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function importSchedule(Request $request): RedirectResponse
    {
        $request->validate(['schedule_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $handle = fopen($request->file('schedule_file')->getRealPath(), 'rb');
        if ($handle === false) {
            return back()->withErrors(['schedule_file' => 'File CSV tidak bisa dibaca.']);
        }

        $headers = fgetcsv($handle, 0, ',');
        if ($headers === false) {
            fclose($handle);

            return back()->withErrors(['schedule_file' => 'File CSV kosong.']);
        }
        $headers = array_map(fn ($header) => Str::lower(trim((string) $header, "\xEF\xBB\xBF \t\n\r\0\x0B")), $headers);
        $required = ['hari', 'minggu', 'regu', 'gedung', 'ruangan', 'siswa', 'rombel'];
        if (array_diff($required, $headers)) {
            fclose($handle);

            return back()->withErrors(['schedule_file' => 'Kolom CSV harus memuat: '.implode(', ', $required).'.']);
        }

        $columns = array_flip($headers);
        $rows = [];
        $slots = [];
        $line = 1;
        while (($values = fgetcsv($handle, 0, ',')) !== false) {
            $line++;
            if ($values === [null] || count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            if (count($values) !== count($headers)) {
                fclose($handle);

                return back()->withErrors(['schedule_file' => "Jumlah kolom pada baris {$line} tidak sesuai template."]);
            }

            $row = [
                'day' => trim((string) $values[$columns['hari']]),
                'week' => (int) trim((string) $values[$columns['minggu']]),
                'team' => (int) trim((string) $values[$columns['regu']]),
                'building' => trim((string) $values[$columns['gedung']]),
                'room' => trim((string) $values[$columns['ruangan']]),
                'students' => trim((string) $values[$columns['siswa']]),
                'class' => trim((string) $values[$columns['rombel']]),
            ];
            $validator = Validator::make($row, [
                'day' => ['required', 'in:Senin,Selasa,Rabu,Kamis,Jumat'],
                'week' => ['required', 'integer', 'between:1,4'],
                'team' => ['required', 'integer', 'between:1,4'],
                'building' => ['required', 'exists:master_gedung,nama_gedung'],
                'room' => ['required', 'string', 'max:100'],
                'students' => ['required', 'string', 'max:1200'],
                'class' => ['required', 'string', 'max:1200'],
            ]);
            if ($validator->fails()) {
                fclose($handle);

                return back()->withErrors(['schedule_file' => "Baris {$line}: ".$validator->errors()->first()]);
            }

            $slot = implode('|', [$row['day'], $row['week'], $row['team']]);
            if (isset($slots[$slot])) {
                fclose($handle);

                return back()->withErrors(['schedule_file' => "Baris {$line}: Regu {$row['team']} mendapat lebih dari satu toilet pada hari dan minggu yang sama."]);
            }
            $slots[$slot] = true;
            $toilet = $this->findToilet($row['building'], $row['room']);
            if (! $toilet) {
                fclose($handle);

                return back()->withErrors(['schedule_file' => "Baris {$line}: toilet tidak cocok dengan Master Toilet."]);
            }
            $students = $this->resolveStudents($row['students'], $row['class']);
            if ($students === null || $students === []) {
                fclose($handle);

                return back()->withErrors(['schedule_file' => "Baris {$line}: semua siswa harus sudah terdaftar di Data Siswa dan cocok dengan rombel."]);
            }
            $rows[] = ['schedule' => $row, 'toilet' => $toilet, 'students' => $students];
            if (count($rows) > 500) {
                fclose($handle);

                return back()->withErrors(['schedule_file' => 'Maksimal 500 baris jadwal per file.']);
            }
        }
        fclose($handle);
        if ($rows === []) {
            return back()->withErrors(['schedule_file' => 'File CSV tidak berisi jadwal.']);
        }

        DB::transaction(function () use ($rows): void {
            JadwalPiket::where('status_jadwal', 'Aktif')->update(['status_jadwal' => 'Arsip']);
            foreach ($rows as $row) {
                $schedule = JadwalPiket::create([
                    'hari' => $row['schedule']['day'], 'minggu_ke' => $row['schedule']['week'],
                    'nama_regu' => $row['schedule']['team'], 'toilet_id' => $row['toilet']->id,
                    'status_jadwal' => 'Aktif',
                ]);
                foreach ($row['students'] as $student) {
                    $schedule->details()->create(['siswa_id' => $student->id, 'status_penugasan' => 'Aktif']);
                }
            }
        });

        return back()->with('success', count($rows).' jadwal berhasil diimpor. Siklus sebelumnya diarsipkan; histori laporan tetap tersimpan.');
    }

    public function updateSchedule(Request $request, int $id): RedirectResponse
    {
        $input = $request->validate([
            'status' => ['required', 'in:Belum diperiksa,Selesai'],
            'attendance' => ['required', 'in:Hadir,Izin,Sakit,Tidak hadir'],
        ]);
        $schedule = JadwalPiket::with('students')->findOrFail($id);
        foreach ($schedule->students as $student) {
            $schedule->details()->where('siswa_id', $student->id)->update(['status_penugasan' => $input['status']]);
            KehadiranSiswa::updateOrCreate(
                ['tanggal' => today(), 'siswa_id' => $student->id],
                ['status_kehadiran' => $input['attendance']],
            );
        }

        return back()->with('success', 'Status jadwal dan kehadiran siswa diperbarui.');
    }

    public function scheduleTemplate()
    {
        $content = "hari,minggu,regu,gedung,ruangan,siswa,rombel\n";

        return response()->streamDownload(fn () => print($content), 'template-jadwal-piket.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function studentTemplate()
    {
        $content = "nis,nama_lengkap,rombel\n";

        return response()->streamDownload(fn () => print($content), 'template-data-siswa.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function buildings(Request $request): View
    {
        $buildings = MasterGedung::withCount('toilets')->orderBy('nama_gedung')->get();
        $toilets = MasterToilet::with('building')->get()->map(fn (MasterToilet $toilet) => $this->toiletRow($toilet))->all();
        if ($request->filled('q')) {
            $buildings = $buildings->filter(fn (MasterGedung $building) => Str::contains(Str::lower($building->nama_gedung.' '.$building->deskripsi), Str::lower($request->string('q')->value())));
        }
        $buildings = $buildings->map(fn (MasterGedung $building) => [
            'id' => $building->id, 'name' => $building->nama_gedung, 'description' => $building->deskripsi,
            'toilet_count' => $building->toilets_count,
        ])->values()->all();

        return view('admin.buildings', compact('buildings', 'toilets'));
    }

    public function storeBuilding(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:master_gedung,nama_gedung'],
            'description' => ['nullable', 'string', 'max:180'],
        ]);
        MasterGedung::create(['nama_gedung' => $input['name'], 'deskripsi' => $input['description'] ?? null]);

        return back()->with('success', 'Gedung berhasil ditambahkan.');
    }

    public function editBuilding(int $id): View
    {
        $model = MasterGedung::findOrFail($id);
        $building = ['id' => $model->id, 'name' => $model->nama_gedung, 'description' => $model->deskripsi];

        return view('admin.building-edit', compact('building'));
    }

    public function updateBuilding(Request $request, int $id): RedirectResponse
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('master_gedung', 'nama_gedung')->ignore($id)],
            'description' => ['nullable', 'string', 'max:180'],
        ]);
        MasterGedung::findOrFail($id)->update(['nama_gedung' => $input['name'], 'deskripsi' => $input['description'] ?? null]);

        return redirect()->route('buildings.index')->with('success', 'Data gedung diperbarui.');
    }

    public function deleteBuilding(int $id): RedirectResponse
    {
        $building = MasterGedung::findOrFail($id);
        if ($building->toilets()->exists()) {
            return back()->with('error', 'Gedung masih memiliki lokasi toilet; pindahkan atau hapus toilet terkait terlebih dahulu.');
        }
        $building->delete();

        return back()->with('success', 'Gedung berhasil dihapus.');
    }

    public function toilets(Request $request): View
    {
        $query = MasterToilet::with('building')->orderBy('gedung_id')->orderBy('nama_toilet');
        if ($request->filled('q')) {
            $term = Str::lower($request->string('q')->value());
            $query->where(fn ($builder) => $builder->whereRaw('LOWER(nama_toilet) LIKE ?', ["%{$term}%"])
                ->orWhereRaw('LOWER(lantai) LIKE ?', ["%{$term}%"]));
        }
        $toilets = $query->get()->map(fn (MasterToilet $toilet) => $this->toiletRow($toilet))->all();
        $buildings = MasterGedung::orderBy('nama_gedung')->get()->map(fn (MasterGedung $building) => [
            'id' => $building->id, 'name' => $building->nama_gedung,
        ])->all();

        return view('admin.toilets', compact('toilets', 'buildings'));
    }

    public function storeToilet(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'building' => ['required', 'exists:master_gedung,nama_gedung'],
            'floor' => ['required', 'string', 'max:30'],
            'type' => ['required', 'in:Pria,Wanita,Bersama'],
            'cubicles' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['required', 'in:Aktif,Tidak aktif'],
        ]);
        $building = MasterGedung::where('nama_gedung', $input['building'])->firstOrFail();
        MasterToilet::create([
            'gedung_id' => $building->id, 'nama_toilet' => $input['name'], 'lantai' => $input['floor'],
            'jenis_toilet' => $input['type'], 'jumlah_bilik' => $input['cubicles'], 'status_toilet' => $input['status'],
        ]);

        return back()->with('success', 'Lokasi toilet berhasil ditambahkan.');
    }

    public function editToilet(int $id): View
    {
        $toilet = $this->toiletRow(MasterToilet::with('building')->findOrFail($id));
        $buildings = MasterGedung::orderBy('nama_gedung')->get()->map(fn (MasterGedung $building) => ['id' => $building->id, 'name' => $building->nama_gedung])->all();

        return view('admin.toilet-edit', compact('toilet', 'buildings'));
    }

    public function updateToilet(Request $request, int $id): RedirectResponse
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'building' => ['required', 'exists:master_gedung,nama_gedung'],
            'floor' => ['required', 'string', 'max:30'],
            'type' => ['required', 'in:Pria,Wanita,Bersama'],
            'cubicles' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['required', 'in:Aktif,Tidak aktif'],
        ]);
        $building = MasterGedung::where('nama_gedung', $input['building'])->firstOrFail();
        MasterToilet::findOrFail($id)->update([
            'gedung_id' => $building->id, 'nama_toilet' => $input['name'], 'lantai' => $input['floor'],
            'jenis_toilet' => $input['type'], 'jumlah_bilik' => $input['cubicles'], 'status_toilet' => $input['status'],
        ]);

        return redirect()->route('toilets.index')->with('success', 'Data toilet diperbarui.');
    }

    public function deleteToilet(int $id): RedirectResponse
    {
        $toilet = MasterToilet::findOrFail($id);
        if ($toilet->schedules()->exists() || $toilet->reports()->exists()) {
            return back()->with('error', 'Toilet memiliki jadwal atau histori laporan; ubah statusnya menjadi Tidak aktif.');
        }
        $toilet->delete();

        return back()->with('success', 'Lokasi toilet berhasil dihapus.');
    }

    public function history(): View
    {
        $records = Siswa::whereIn('status_siswa', ['Mutasi', 'Lulus'])->withCount('details')->orderByDesc('tanggal_mutasi')->get()
            ->map(fn (Siswa $student) => $this->historyRow($student))->all();

        return view('admin.history', ['records' => $records, 'totalRecords' => count($records)]);
    }

    public function editHistory(int $id): View
    {
        $record = $this->historyRow(Siswa::withCount('details')->findOrFail($id));

        return view('admin.history-edit', compact('record'));
    }

    public function updateHistory(Request $request, int $id): RedirectResponse
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'nis' => ['required', 'string', 'max:30', Rule::unique('siswa', 'nis')->ignore($id)],
            'class' => ['required', 'string', 'max:40'],
            'date' => ['required', 'date'],
            'type' => ['required', 'in:Pindah Sekolah,Mutasi Kelas,Lulus'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        Siswa::findOrFail($id)->update([
            'nama_lengkap' => $input['name'], 'nis' => $input['nis'], 'rombel' => $input['class'],
            'tanggal_mutasi' => $input['date'], 'jenis_mutasi' => $input['type'],
            'status_siswa' => $input['type'] === 'Lulus' ? 'Lulus' : 'Mutasi', 'catatan_mutasi' => $input['note'] ?? null,
        ]);

        return redirect()->route('history.index')->with('success', 'Arsip siswa diperbarui.');
    }

    public function deleteHistory(int $id): RedirectResponse
    {
        Siswa::findOrFail($id)->update([
            'status_siswa' => 'Aktif', 'tanggal_mutasi' => null, 'jenis_mutasi' => null, 'catatan_mutasi' => null,
        ]);

        return back()->with('success', 'Siswa dikembalikan ke status aktif.');
    }

    public function publicDashboard(): View
    {
        $token = $this->todayToken();
        $day = $this->indonesianDay(today()->dayOfWeekIso);
        $week = $token?->minggu_ke ?? $this->currentRotationWeek();
        $assignments = JadwalPiket::where('status_jadwal', 'Aktif')->where('hari', $day)->where('minggu_ke', $week)
            ->with(['toilet.building', 'students', 'details', 'report'])->orderBy('nama_regu')->get()
            ->map(fn (JadwalPiket $schedule) => $this->scheduleRow($schedule))->all();

        return view('public.dashboard', compact('assignments'));
    }

    public function exportHistory()
    {
        $students = Siswa::whereIn('status_siswa', ['Mutasi', 'Lulus'])->orderBy('tanggal_mutasi')->get();

        return response()->streamDownload(function () use ($students): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['Nama siswa', 'NIS', 'Rombel', 'Tanggal mutasi', 'Jenis mutasi', 'Keterangan']);
            foreach ($students as $student) {
                fputcsv($stream, [$student->nama_lengkap, $student->nis, $student->rombel, $student->tanggal_mutasi?->toDateString(), $student->jenis_mutasi, $student->catatan_mutasi]);
            }
            fclose($stream);
        }, 'arsip-siswa-mutasi.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function currentPetugasToken(): ?TokenAkses
    {
        if (! session()->has('petugas.token_id')) {
            return null;
        }
        $token = TokenAkses::find(session('petugas.token_id'));

        return $token && $this->tokenIsValid($token) ? $token : null;
    }

    private function tokenIsValid(TokenAkses $token): bool
    {
        if ($token->status !== 'Aktif' || ! $token->tanggal->isToday()) {
            return false;
        }
        $time = now()->format('H:i:s');

        return $time >= $token->berlaku_mulai && $time <= $token->berlaku_sampai;
    }

    private function todayToken(): ?TokenAkses
    {
        return TokenAkses::whereDate('tanggal', today())->where('status', 'Aktif')->latest('id')->first();
    }

    private function activeScheduleForTeam(TokenAkses $token, int $team): ?JadwalPiket
    {
        return JadwalPiket::with(['toilet.building', 'students', 'details', 'report'])
            ->where('status_jadwal', 'Aktif')
            ->where('hari', $this->indonesianDay(today()->dayOfWeekIso))
            ->where('minggu_ke', $token->minggu_ke)
            ->where('nama_regu', $team)
            ->latest('id')->first();
    }

    private function scheduleRow(JadwalPiket $schedule): array
    {
        $schedule->loadMissing(['toilet.building', 'students', 'details', 'report']);
        $students = $schedule->students;
        $attendance = $students->first()
            ? KehadiranSiswa::whereDate('tanggal', today())->where('siswa_id', $students->first()->id)->value('status_kehadiran')
            : null;
        $complete = $schedule->report !== null || ($schedule->details->isNotEmpty() && $schedule->details->every(fn (DetailJadwal $detail) => $detail->status_penugasan === 'Selesai'));

        return [
            'id' => $schedule->id,
            'day' => $schedule->hari,
            'week' => $schedule->minggu_ke,
            'team' => $schedule->nama_regu,
            'building' => $schedule->toilet->building->nama_gedung,
            'room' => $schedule->toilet->nama_toilet.' ('.$schedule->toilet->lantai.')',
            'students' => $students->pluck('nama_lengkap')->implode(', '),
            'class' => $students->pluck('rombel')->unique()->implode(', '),
            'status' => $complete ? 'Selesai' : 'Belum diperiksa',
            'attendance' => $attendance ?? 'Belum diisi',
        ];
    }

    private function toiletRow(MasterToilet $toilet): array
    {
        $toilet->loadMissing('building');

        return [
            'id' => $toilet->id, 'name' => $toilet->nama_toilet, 'building' => $toilet->building->nama_gedung,
            'building_id' => $toilet->gedung_id, 'floor' => $toilet->lantai, 'type' => $toilet->jenis_toilet,
            'cubicles' => $toilet->jumlah_bilik, 'status' => $toilet->status_toilet,
        ];
    }

    private function studentRow(Siswa $student): array
    {
        return [
            'id' => $student->id, 'nis' => $student->nis, 'name' => $student->nama_lengkap,
            'class' => $student->rombel, 'status' => $student->status_siswa,
            'pickets' => $student->details_count ?? $student->details()->count(),
            'tanggal_mutasi' => $student->tanggal_mutasi?->toDateString(),
            'jenis_mutasi' => $student->jenis_mutasi, 'catatan_mutasi' => $student->catatan_mutasi,
        ];
    }

    private function historyRow(Siswa $student): array
    {
        $row = $this->studentRow($student);

        return [
            'id' => $row['id'], 'name' => $row['name'], 'nis' => $row['nis'], 'class' => $row['class'],
            'date' => $row['tanggal_mutasi'] ?? today()->toDateString(), 'type' => $row['jenis_mutasi'] ?? 'Mutasi Kelas',
            'note' => $row['catatan_mutasi'] ?? '', 'pickets' => $row['pickets'],
        ];
    }

    private function reportRow(HistoriPengerjaan $report): array
    {
        $report->loadMissing(['schedule.toilet.building', 'toilet.building']);

        return [
            'id' => $report->id, 'team' => $report->schedule->nama_regu,
            'building' => $report->toilet->building->nama_gedung, 'room' => $report->toilet->nama_toilet,
            'status_kebersihan' => $report->status_kebersihan, 'catatan' => $report->catatan,
            'waktu_kirim' => $report->created_at->format('H:i'),
        ];
    }

    private function resolveStudents(string $names, string $class)
    {
        $studentNames = collect(preg_split('/\s*;\s*/', $names) ?: [])->filter()->unique()->values();
        $classes = collect(preg_split('/\s*;\s*/', $class) ?: [])->filter()->values();
        if ($classes->count() === 1) {
            $classes = collect(array_fill(0, $studentNames->count(), $classes->first()));
        }
        if ($classes->count() !== $studentNames->count()) {
            return null;
        }

        $students = $studentNames->map(fn (string $name, int $index) => Siswa::where('nama_lengkap', $name)
            ->where('rombel', $classes[$index])->where('status_siswa', 'Aktif')->first());

        return $students->contains(null) ? null : $students;
    }

    private function findToilet(string $buildingName, string $room): ?MasterToilet
    {
        $normalize = fn (string $value) => Str::of($value)->lower()->replaceMatches('/\blt\.?\s*/', 'lantai ')->replace(['(', ')'], '')->squish()->value();
        $roomKey = $normalize($room);

        return MasterToilet::with('building')->whereHas('building', fn ($query) => $query->where('nama_gedung', $buildingName))
            ->get()->first(fn (MasterToilet $toilet) => in_array($roomKey, [
                $normalize($toilet->nama_toilet),
                $normalize($toilet->nama_toilet.' '.$toilet->lantai),
            ], true));
    }

    private function indonesianDay(int $day): string
    {
        return [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'][$day];
    }

    private function currentRotationWeek(): int
    {
        return (int) ((today()->isoWeek % 4) ?: 4);
    }
}