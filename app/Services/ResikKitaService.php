<?php

namespace App\Services;

use App\Models\DetailJadwal;
use App\Models\HistoriPengerjaan;
use App\Models\JadwalPiket;
use App\Models\KehadiranSiswa;
use App\Models\MasterToilet;
use App\Models\Siswa;
use App\Models\TokenAkses;
use Illuminate\Support\Str;

class ResikKitaService
{
    /**
     * Get valid active schedule days based on configuration.
     *
     * @return array<int, string>
     */
    public function allowedDays(): array
    {
        return config('app.allow_weekend', true)
            ? ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']
            : ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
    }

    /**
     * Map day of week ISO (1 = Monday ... 7 = Sunday) to Indonesian day name.
     */
    public function indonesianDay(int $day): string
    {
        return [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
            5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
        ][$day] ?? 'Senin';
    }

    /**
     * Calculate 4-week cycle rotation (returns 1, 2, 3, or 4).
     */
    public function currentRotationWeek(): int
    {
        return (int) ((today()->isoWeek % 4) ?: 4);
    }

    /**
     * Get active token for today.
     */
    public function todayToken(): ?TokenAkses
    {
        return TokenAkses::whereDate('tanggal', today())
            ->where('status', 'Aktif')
            ->latest('id')
            ->first();
    }

    /**
     * Verify whether a token is active and valid for current time.
     */
    public function tokenIsValid(TokenAkses $token): bool
    {
        if ($token->status !== 'Aktif' || ! $token->tanggal->isToday()) {
            return false;
        }
        $time = now()->format('H:i:s');

        return $time >= $token->berlaku_mulai && $time <= $token->berlaku_sampai;
    }

    /**
     * Retrieve current student session's token if still valid.
     */
    public function currentPetugasToken(): ?TokenAkses
    {
        if (! session()->has('petugas.token_id')) {
            return null;
        }
        $token = TokenAkses::find(session('petugas.token_id'));

        return $token && $this->tokenIsValid($token) ? $token : null;
    }

    /**
     * Find active schedule for a team on today's day & current token week.
     */
    public function activeScheduleForTeam(TokenAkses $token, int $team): ?JadwalPiket
    {
        return JadwalPiket::with(['toilet.building', 'students', 'details', 'report'])
            ->where('status_jadwal', 'Aktif')
            ->where('hari', $this->indonesianDay(today()->dayOfWeekIso))
            ->where('minggu_ke', $token->minggu_ke)
            ->where('nama_regu', $team)
            ->latest('id')
            ->first();
    }

    /**
     * Format a schedule model into display-ready array.
     */
    public function formatSchedule(JadwalPiket $schedule): array
    {
        $schedule->loadMissing(['toilet.building', 'students', 'details', 'report']);
        $students = $schedule->students;
        $attendance = $students->first()
            ? KehadiranSiswa::whereDate('tanggal', today())->where('siswa_id', $students->first()->id)->value('status_kehadiran')
            : null;
        $complete = $schedule->report !== null || (
            $schedule->details->isNotEmpty() &&
            $schedule->details->every(fn (DetailJadwal $d) => $d->status_penugasan === 'Selesai')
        );

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

    /**
     * Format schedules broken down per individual student for detailed attendance.
     */
    public function formatScheduleItems(iterable $schedules): array
    {
        $items = [];
        foreach ($schedules as $schedule) {
            $schedule->loadMissing(['toilet.building', 'students', 'details', 'report']);
            $buildingName = $schedule->toilet?->building?->nama_gedung ?? '-';
            $roomName = $schedule->toilet ? $schedule->toilet->nama_toilet.' ('.$schedule->toilet->lantai.')' : '-';

            if ($schedule->students->isEmpty()) {
                $items[] = [
                    'id' => $schedule->id,
                    'schedule_id' => $schedule->id,
                    'student_id' => null,
                    'student_name' => 'Belum ada siswa',
                    'nis' => null,
                    'class' => '-',
                    'day' => $schedule->hari,
                    'week' => $schedule->minggu_ke,
                    'team' => $schedule->nama_regu,
                    'building' => $buildingName,
                    'room' => $roomName,
                    'status' => 'Belum diperiksa',
                    'attendance' => 'Belum diisi',
                ];
                continue;
            }

            foreach ($schedule->students as $student) {
                $detail = $schedule->details->firstWhere('siswa_id', $student->id);
                $attendance = KehadiranSiswa::whereDate('tanggal', today())
                    ->where('siswa_id', $student->id)
                    ->value('status_kehadiran');

                $items[] = [
                    'id' => $schedule->id,
                    'schedule_id' => $schedule->id,
                    'student_id' => $student->id,
                    'student_name' => $student->nama_lengkap,
                    'nis' => $student->nis,
                    'class' => $student->rombel,
                    'day' => $schedule->hari,
                    'week' => $schedule->minggu_ke,
                    'team' => $schedule->nama_regu,
                    'building' => $buildingName,
                    'room' => $roomName,
                    'status' => $detail?->status_penugasan ?? 'Belum diperiksa',
                    'attendance' => $attendance ?? 'Belum diisi',
                ];
            }
        }

        return $items;
    }

    /**
     * Format toilet model for views.
     */
    public function formatToilet(MasterToilet $toilet): array
    {
        $toilet->loadMissing('building');

        return [
            'id' => $toilet->id,
            'name' => $toilet->nama_toilet,
            'building' => $toilet->building->nama_gedung,
            'building_id' => $toilet->gedung_id,
            'floor' => $toilet->lantai,
            'type' => $toilet->jenis_toilet,
            'cubicles' => $toilet->jumlah_bilik,
            'status' => $toilet->status_toilet,
        ];
    }

    /**
     * Format student model for views.
     */
    public function formatStudent(Siswa $student): array
    {
        return [
            'id' => $student->id,
            'nis' => $student->nis,
            'name' => $student->nama_lengkap,
            'class' => $student->rombel,
            'status' => $student->status_siswa,
            'pickets' => $student->details_count ?? $student->details()->count(),
            'tanggal_mutasi' => $student->tanggal_mutasi?->toDateString(),
            'jenis_mutasi' => $student->jenis_mutasi,
            'catatan_mutasi' => $student->catatan_mutasi,
        ];
    }

    /**
     * Format student model for mutation archive history.
     */
    public function formatHistory(Siswa $student): array
    {
        $row = $this->formatStudent($student);

        return [
            'id' => $row['id'],
            'name' => $row['name'],
            'nis' => $row['nis'],
            'class' => $row['class'],
            'date' => $row['tanggal_mutasi'] ?? today()->toDateString(),
            'type' => $row['jenis_mutasi'] ?? 'Mutasi Kelas',
            'note' => $row['catatan_mutasi'] ?? '',
            'pickets' => $row['pickets'],
        ];
    }

    /**
     * Format HistoriPengerjaan report for views.
     */
    public function formatReport(HistoriPengerjaan $report): array
    {
        $report->loadMissing(['schedule.toilet.building', 'toilet.building', 'reporter']);

        return [
            'id' => $report->id,
            'team' => $report->schedule->nama_regu ?? ($report->schedule->regu_id ?? '-'),
            'building' => $report->toilet->building->nama_gedung ?? ($report->schedule->toilet->building->nama_gedung ?? '-'),
            'room' => $report->toilet->nama_toilet ?? ($report->schedule->toilet->nama_toilet ?? '-'),
            'student_name' => $report->reporter?->nama_lengkap ?? 'Petugas Regu',
            'nis' => $report->reporter?->nis ?? '',
            'rombel' => $report->reporter?->rombel ?? '',
            'status_kehadiran' => $report->status_kehadiran ?? 'Hadir',
            'status_kebersihan' => $report->status_kebersihan,
            'catatan' => $report->catatan,
            'waktu_kirim' => $report->created_at ? $report->created_at->format('H:i') : '-',
        ];
    }

    /**
     * Find toilet by building and room string.
     */
    public function findToilet(string $buildingName, string $room): ?MasterToilet
    {
        $normalize = fn (string $val) => Str::of($val)->lower()
            ->replaceMatches('/\blt\.?\s*/', 'lantai ')
            ->replace(['(', ')'], '')
            ->squish()
            ->value();

        $roomKey = $normalize($room);

        return MasterToilet::with('building')
            ->whereHas('building', fn ($q) => $q->where('nama_gedung', $buildingName))
            ->get()
            ->first(fn (MasterToilet $t) => in_array($roomKey, [
                $normalize($t->nama_toilet),
                $normalize($t->nama_toilet.' '.$t->lantai),
            ], true));
    }

    /**
     * Resolve list of student models from names & classes separated by semicolon.
     */
    public function resolveStudents(string $names, string $class)
    {
        $studentNames = collect(preg_split('/\s*;\s*/', $names) ?: [])->filter()->unique()->values();
        $classes = collect(preg_split('/\s*;\s*/', $class) ?: [])->filter()->values();
        if ($classes->count() === 1) {
            $classes = collect(array_fill(0, $studentNames->count(), $classes->first()));
        }
        if ($classes->count() !== $studentNames->count()) {
            return null;
        }

        $students = $studentNames->map(fn (string $name, int $i) => Siswa::where('nama_lengkap', $name)
            ->where('rombel', $classes[$i])
            ->where('status_siswa', 'Aktif')
            ->first());

        return $students->contains(null) ? null : $students;
    }
}
