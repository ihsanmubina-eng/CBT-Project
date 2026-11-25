<?php

namespace App\Http\Controllers;

use App\Models\DetailJadwal;
use App\Models\HistoriPengerjaan;
use App\Models\JadwalPiket;
use App\Models\KehadiranSiswa;
use App\Models\MasterRegu;
use App\Models\Siswa;
use App\Services\ResikKitaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PetugasController extends Controller
{
    public function __construct(
        protected ResikKitaService $service,
    ) {}

    public function dashboard(Request $request): View|RedirectResponse
    {
        $token = $this->service->currentPetugasToken();
        if (! $token) {
            session()->forget(['petugas.token_id', 'petugas.regu']);

            return redirect()->route('login')->with('error', 'Token sudah tidak aktif. Minta token baru dari guru.');
        }

        $team = (int) session('petugas.regu');
        $day = $this->service->indonesianDay(today()->dayOfWeekIso);

        // Ambil profil MasterRegu terdaftar jika ada
        $regu = MasterRegu::with(['building', 'leader'])
            ->where('id', $team)
            ->orWhere('nama_regu', "Regu {$team}")
            ->first();

        // Ambil semua penugasan toilet untuk regu ini pada hari & minggu berjalan
        $schedules = JadwalPiket::with(['toilet.building', 'students', 'details', 'reports.reporter', 'team'])
            ->where('status_jadwal', 'Aktif')
            ->where('hari', $day)
            ->where('minggu_ke', $token->minggu_ke)
            ->where(function ($q) use ($team, $regu) {
                $q->where('nama_regu', $team);
                if ($regu) {
                    $q->orWhere('regu_id', $regu->id);
                }
            })
            ->get();

        if ($schedules->isEmpty()) {
            return redirect()->route('login')->with('error', "Belum ada jadwal untuk Regu {$team} pada hari {$day}.");
        }

        // Tentukan toilet yang sedang dipilih (default toilet pertama)
        $selectedScheduleId = (int) $request->query('schedule_id', $schedules->first()->id);
        $activeSchedule = $schedules->firstWhere('id', $selectedScheduleId) ?: $schedules->first();

        // Siapkan opsi dropdown toilet
        $toiletOptions = $schedules->map(function ($s) {
            $reportedCount = $s->reports->count();
            $studentsCount = max(1, $s->students->count());
            $isComplete = $reportedCount >= $studentsCount;

            return [
                'id' => $s->id,
                'toilet_name' => $s->toilet->nama_toilet,
                'floor' => $s->toilet->lantai,
                'reported_count' => $reportedCount,
                'students_count' => $studentsCount,
                'is_complete' => $isComplete,
            ];
        })->all();

        // Siapkan data 2 siswa untuk toilet aktif
        $studentList = [];
        foreach ($activeSchedule->students as $student) {
            $studentReport = $activeSchedule->reports->firstWhere('pelapor_id', $student->id);
            $studentList[] = [
                'id' => $student->id,
                'name' => $student->nama_lengkap,
                'nis' => $student->nis,
                'rombel' => $student->rombel,
                'has_reported' => $studentReport !== null,
                'report' => $studentReport ? [
                    'status_kebersihan' => $studentReport->status_kebersihan,
                    'status_kehadiran' => $studentReport->status_kehadiran ?? 'Hadir',
                    'catatan' => $studentReport->catatan,
                    'waktu_kirim' => $studentReport->created_at->format('H:i'),
                ] : null,
            ];
        }

        $completedToilets = collect($toiletOptions)->where('is_complete', true)->count();
        $totalToilets = count($toiletOptions);

        return view('petugas.dashboard', [
            'team' => $team,
            'regu' => $regu,
            'day' => $day,
            'week' => $token->minggu_ke,
            'buildingName' => $regu?->building?->nama_gedung ?? $activeSchedule->toilet->building->nama_gedung,
            'toiletOptions' => $toiletOptions,
            'activeSchedule' => $activeSchedule,
            'studentList' => $studentList,
            'completedToilets' => $completedToilets,
            'totalToilets' => $totalToilets,
        ]);
    }

    public function inspectStudent(int $scheduleId, int $studentId): View|RedirectResponse
    {
        $token = $this->service->currentPetugasToken();
        if (! $token) {
            return redirect()->route('login')->with('error', 'Token sudah tidak aktif. Minta token baru dari guru.');
        }

        $team = (int) session('petugas.regu');
        $regu = MasterRegu::with(['building', 'leader'])
            ->where('id', $team)
            ->orWhere('nama_regu', "Regu {$team}")
            ->first();

        $schedule = JadwalPiket::with(['toilet.building', 'students', 'reports'])->findOrFail($scheduleId);

        abort_unless($schedule->nama_regu === $team || ($regu && $schedule->regu_id === $regu->id), 403, 'Jadwal ini bukan milik regu Anda.');
        abort_unless($schedule->students->contains('id', $studentId), 404, 'Siswa tidak terdaftar di toilet ini.');

        // Cek jika siswa sudah mengisi laporan
        $existing = $schedule->reports->firstWhere('pelapor_id', $studentId);
        if ($existing) {
            return redirect()->route('petugas.dashboard', ['schedule_id' => $scheduleId])
                ->with('error', 'Laporan untuk siswa ini sudah selesai dan terkunci.');
        }

        $student = Siswa::findOrFail($studentId);

        return view('petugas.inspect', [
            'team' => $team,
            'regu' => $regu,
            'schedule' => $schedule,
            'toilet' => $schedule->toilet,
            'buildingName' => $schedule->toilet->building->nama_gedung,
            'student' => $student,
        ]);
    }

    public function submitReport(Request $request): RedirectResponse
    {
        $token = $this->service->currentPetugasToken();
        if (! $token) {
            return redirect()->route('login')->with('error', 'Token sudah tidak aktif. Minta token baru dari guru.');
        }

        $input = $request->validate([
            'schedule_id' => ['required', 'exists:jadwal_piket,id'],
            'student_id' => ['required', 'exists:siswa,id'],
            'status_kehadiran' => ['required', 'in:Hadir,Tidak Hadir'],
            'status_kebersihan' => ['required', 'in:Bersih,Perlu dibersihkan,Rusak,Tidak Bertugas'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        if ($input['status_kehadiran'] === 'Tidak Hadir') {
            $input['status_kebersihan'] = 'Tidak Bertugas';
            if (empty(trim((string) ($input['catatan'] ?? '')))) {
                $input['catatan'] = 'Siswa tidak hadir / absen piket.';
            }
        }

        $schedule = JadwalPiket::with('students')->findOrFail($input['schedule_id']);
        $studentId = (int) $input['student_id'];

        if (! $schedule->students->contains('id', $studentId)) {
            return back()->with('error', 'Siswa tidak terdaftar pada jadwal toilet ini.');
        }

        $existing = HistoriPengerjaan::where('jadwal_id', $schedule->id)
            ->where('pelapor_id', $studentId)
            ->whereDate('tanggal', today())
            ->exists();

        if ($existing) {
            return redirect()->route('petugas.dashboard', ['schedule_id' => $schedule->id])
                ->with('error', 'Laporan untuk siswa ini sudah dikirim dan terkunci.');
        }

        DB::transaction(function () use ($schedule, $token, $studentId, $input): void {
            HistoriPengerjaan::create([
                'tanggal' => today(),
                'jadwal_id' => $schedule->id,
                'token_id' => $token->id,
                'toilet_id' => $schedule->toilet_id,
                'pelapor_id' => $studentId,
                'status_kehadiran' => $input['status_kehadiran'],
                'status_kebersihan' => $input['status_kebersihan'],
                'catatan' => $input['catatan'] ?? null,
                'status_kunci' => 'Terkunci',
            ]);

            DetailJadwal::where('jadwal_id', $schedule->id)
                ->where('siswa_id', $studentId)
                ->update(['status_penugasan' => $input['status_kehadiran'] === 'Hadir' ? 'Selesai' : 'Tidak Hadir']);

            KehadiranSiswa::updateOrCreate(
                ['tanggal' => today(), 'siswa_id' => $studentId],
                ['status_kehadiran' => $input['status_kehadiran']],
            );
        });

        $student = Siswa::find($studentId);

        return redirect()->route('petugas.dashboard', ['schedule_id' => $schedule->id])
            ->with('success', "Laporan untuk {$student->nama_lengkap} (Presensi: {$input['status_kehadiran']}) berhasil disimpan dan dikunci.");
    }

    public function logoutStudent(Request $request): RedirectResponse
    {
        session()->forget(['petugas.token_id', 'petugas.regu']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Berhasil keluar dari sesi petugas.');
    }
}
