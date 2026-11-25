<?php

namespace App\Http\Controllers;

use App\Models\JadwalPiket;
use App\Models\Siswa;
use App\Services\ResikKitaService;
use App\Services\SpreadsheetService;
use Illuminate\View\View;

class PublicMonitoringController extends Controller
{
    public function __construct(
        protected ResikKitaService $service,
        protected SpreadsheetService $spreadsheet,
    ) {}

    public function dashboard(): View
    {
        $token = $this->service->todayToken();
        $day = $this->service->indonesianDay(today()->dayOfWeekIso);
        $week = $token?->minggu_ke ?? $this->service->currentRotationWeek();

        $assignments = JadwalPiket::where('status_jadwal', 'Aktif')
            ->where('hari', $day)
            ->where('minggu_ke', $week)
            ->with(['toilet.building', 'students', 'details', 'report'])
            ->orderBy('nama_regu')
            ->get()
            ->map(fn (JadwalPiket $s) => $this->service->formatSchedule($s))
            ->all();

        return view('public.dashboard', compact('assignments'));
    }

    public function exportHistory()
    {
        $students = Siswa::whereIn('status_siswa', ['Mutasi', 'Lulus'])
            ->orderBy('tanggal_mutasi')
            ->get();

        $headers = ['Nama Siswa', 'NIS', 'Rombel', 'Tanggal Mutasi', 'Jenis Mutasi', 'Keterangan'];
        $rows = [];
        foreach ($students as $s) {
            $rows[] = [
                $s->nama_lengkap,
                $s->nis,
                $s->rombel,
                $s->tanggal_mutasi?->toDateString(),
                $s->jenis_mutasi,
                $s->catatan_mutasi,
            ];
        }

        return $this->spreadsheet->downloadXlsx('arsip-siswa-mutasi.xlsx', $headers, $rows);
    }
}
