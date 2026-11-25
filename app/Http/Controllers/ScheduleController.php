<?php

namespace App\Http\Controllers;

use App\Models\JadwalPiket;
use App\Models\KehadiranSiswa;
use App\Models\MasterGedung;
use App\Services\ResikKitaService;
use App\Services\SpreadsheetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(
        protected ResikKitaService $service,
        protected SpreadsheetService $spreadsheet,
    ) {}

    public function index(): View
    {
        $allowedDays = $this->service->allowedDays();
        $rawSchedules = JadwalPiket::where('status_jadwal', 'Aktif')
            ->with(['toilet.building', 'students', 'details', 'report'])
            ->orderByRaw("CASE hari WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6 WHEN 'Minggu' THEN 7 ELSE 8 END")
            ->orderBy('minggu_ke')
            ->orderBy('nama_regu')
            ->get();

        $schedules = $this->service->formatScheduleItems($rawSchedules);

        $buildings = MasterGedung::orderBy('nama_gedung')->get()
            ->map(fn (MasterGedung $b) => [
                'id' => $b->id,
                'name' => $b->nama_gedung,
                'description' => $b->deskripsi,
            ])->all();

        $toilets = \App\Models\MasterToilet::with('building')->where('status_toilet', 'Aktif')->get()
            ->map(fn (\App\Models\MasterToilet $t) => $this->service->formatToilet($t))->all();

        return view('admin.schedule', compact('schedules', 'buildings', 'toilets', 'allowedDays'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validDays = implode(',', $this->service->allowedDays());
        $input = $request->validate([
            'day' => ['required', "in:{$validDays}"],
            'week' => ['required', 'integer', 'between:1,4'],
            'team' => ['required', 'integer', 'between:1,4'],
            'building' => ['required', 'string', 'exists:master_gedung,nama_gedung'],
            'room' => ['required', 'string', 'max:100'],
            'students' => ['required', 'string', 'max:500'],
            'class' => ['required', 'string', 'max:40'],
        ]);

        $toilet = $this->service->findToilet($input['building'], $input['room']);
        if (! $toilet) {
            return back()->withErrors(['room' => 'Lokasi toilet tidak ditemukan. Tambahkan toilet di Master Toilet dahulu.'])->withInput();
        }

        $students = $this->service->resolveStudents($input['students'], $input['class']);
        if ($students === null) {
            return back()->withErrors(['students' => 'Ada siswa yang belum terdaftar di Data Siswa. Impor roster siswa dahulu.'])->withInput();
        }

        if (JadwalPiket::where('hari', $input['day'])->where('minggu_ke', $input['week'])
            ->where('nama_regu', $input['team'])->where('status_jadwal', 'Aktif')->exists()) {
            return back()->withErrors(['team' => 'Regu ini sudah memiliki jadwal aktif pada hari dan minggu tersebut.'])->withInput();
        }

        DB::transaction(function () use ($input, $toilet, $students): void {
            $schedule = JadwalPiket::create([
                'hari' => $input['day'],
                'minggu_ke' => $input['week'],
                'nama_regu' => $input['team'],
                'toilet_id' => $toilet->id,
                'status_jadwal' => 'Aktif',
            ]);

            foreach ($students as $student) {
                $schedule->details()->create(['siswa_id' => $student->id, 'status_penugasan' => 'Aktif']);
            }
        });

        return back()->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['schedule_file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120']]);
        $file = $request->file('schedule_file');
        $rawRows = $this->spreadsheet->readRows($file->getRealPath(), $file->getClientOriginalExtension());

        if (count($rawRows) < 2) {
            return back()->withErrors(['schedule_file' => 'File kosong atau tidak memiliki baris data.']);
        }

        $headers = array_map(fn ($h) => Str::lower(trim((string) $h)), array_shift($rawRows));
        $required = ['hari', 'minggu', 'regu', 'gedung', 'ruangan', 'siswa', 'rombel'];
        if (array_diff($required, $headers)) {
            return back()->withErrors(['schedule_file' => 'Kolom file harus memuat: '.implode(', ', $required).'.']);
        }

        $columns = array_flip($headers);
        $rows = [];
        $slots = [];
        $line = 1;

        foreach ($rawRows as $values) {
            $line++;
            $row = [
                'day' => trim((string) ($values[$columns['hari']] ?? '')),
                'week' => (int) trim((string) ($values[$columns['minggu']] ?? '')),
                'team' => (int) trim((string) ($values[$columns['regu']] ?? '')),
                'building' => trim((string) ($values[$columns['gedung']] ?? '')),
                'room' => trim((string) ($values[$columns['ruangan']] ?? '')),
                'students' => trim((string) ($values[$columns['siswa']] ?? '')),
                'class' => trim((string) ($values[$columns['rombel']] ?? '')),
            ];

            $validDays = implode(',', $this->service->allowedDays());
            $validator = Validator::make($row, [
                'day' => ['required', "in:{$validDays}"],
                'week' => ['required', 'integer', 'between:1,4'],
                'team' => ['required', 'integer', 'between:1,4'],
                'building' => ['required', 'exists:master_gedung,nama_gedung'],
                'room' => ['required', 'string', 'max:100'],
                'students' => ['required', 'string', 'max:1200'],
                'class' => ['required', 'string', 'max:1200'],
            ]);

            if ($validator->fails()) {
                return back()->withErrors(['schedule_file' => "Baris {$line}: ".$validator->errors()->first()]);
            }

            $slot = implode('|', [$row['day'], $row['week'], $row['team']]);
            if (isset($slots[$slot])) {
                return back()->withErrors(['schedule_file' => "Baris {$line}: Regu {$row['team']} mendapat lebih dari satu toilet pada hari dan minggu yang sama."]);
            }
            $slots[$slot] = true;

            $toilet = $this->service->findToilet($row['building'], $row['room']);
            if (! $toilet) {
                return back()->withErrors(['schedule_file' => "Baris {$line}: toilet '{$row['room']}' di '{$row['building']}' tidak cocok dengan Master Toilet."]);
            }

            $students = $this->service->resolveStudents($row['students'], $row['class']);
            if ($students === null || $students === []) {
                return back()->withErrors(['schedule_file' => "Baris {$line}: semua siswa harus sudah terdaftar di Data Siswa dan cocok dengan rombel."]);
            }

            $rows[] = ['schedule' => $row, 'toilet' => $toilet, 'students' => $students];
            if (count($rows) > 500) {
                return back()->withErrors(['schedule_file' => 'Maksimal 500 baris jadwal per file.']);
            }
        }

        if ($rows === []) {
            return back()->withErrors(['schedule_file' => 'File tidak berisi baris jadwal valid.']);
        }

        DB::transaction(function () use ($rows): void {
            JadwalPiket::where('status_jadwal', 'Aktif')->update(['status_jadwal' => 'Arsip']);
            foreach ($rows as $row) {
                $schedule = JadwalPiket::create([
                    'hari' => $row['schedule']['day'],
                    'minggu_ke' => $row['schedule']['week'],
                    'nama_regu' => $row['schedule']['team'],
                    'toilet_id' => $row['toilet']->id,
                    'status_jadwal' => 'Aktif',
                ]);
                foreach ($row['students'] as $student) {
                    $schedule->details()->create(['siswa_id' => $student->id, 'status_penugasan' => 'Aktif']);
                }
            }
        });

        return back()->with('success', count($rows).' jadwal berhasil diimpor. Siklus sebelumnya diarsipkan; histori laporan tetap tersimpan.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $input = $request->validate([
            'student_id' => ['nullable', 'exists:siswa,id'],
            'status' => ['required', 'in:Belum diperiksa,Selesai'],
            'attendance' => ['required', 'in:Hadir,Izin,Sakit,Tidak hadir'],
        ]);

        $schedule = JadwalPiket::with('students')->findOrFail($id);

        if (! empty($input['student_id'])) {
            $studentId = (int) $input['student_id'];
            $schedule->details()->where('siswa_id', $studentId)->update(['status_penugasan' => $input['status']]);
            KehadiranSiswa::updateOrCreate(
                ['tanggal' => today(), 'siswa_id' => $studentId],
                ['status_kehadiran' => $input['attendance']],
            );
        } else {
            foreach ($schedule->students as $student) {
                $schedule->details()->where('siswa_id', $student->id)->update(['status_penugasan' => $input['status']]);
                KehadiranSiswa::updateOrCreate(
                    ['tanggal' => today(), 'siswa_id' => $student->id],
                    ['status_kehadiran' => $input['attendance']],
                );
            }
        }

        return back()->with('success', 'Status jadwal dan kehadiran siswa berhasil diperbarui.');
    }

    public function template()
    {
        $headers = ['hari', 'minggu', 'regu', 'gedung', 'ruangan', 'siswa', 'rombel'];
        $rows = [
            ['Senin', 1, 1, 'Gedung Graha Widya', 'Toilet Siswa Putra Lt. 1', 'Ahmad Fauzan Pratama; Alifia Zahra Khairunnisa', 'XII PPLG 1'],
            ['Senin', 1, 2, 'Gedung Graha Widya', 'Toilet Siswa Putri Lt. 1', 'Bintang Wahyu Hidayat; Dewi Cahyaningrum', 'XII PPLG 2'],
        ];

        return $this->spreadsheet->downloadXlsx('template-jadwal-piket.xlsx', $headers, $rows);
    }
}
