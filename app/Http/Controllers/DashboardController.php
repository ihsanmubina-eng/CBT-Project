<?php

namespace App\Http\Controllers;

use App\Models\HistoriPengerjaan;
use App\Models\JadwalPiket;
use App\Models\MasterGedung;
use App\Models\MasterToilet;
use App\Models\TokenAkses;
use App\Services\ResikKitaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ResikKitaService $service,
    ) {}

    public function index(): View
    {
        $token = $this->service->todayToken();
        $day = $this->service->indonesianDay(today()->dayOfWeekIso);
        $week = $token?->minggu_ke ?? $this->service->currentRotationWeek();

        $assignments = JadwalPiket::query()
            ->where('status_jadwal', 'Aktif')
            ->where('hari', $day)
            ->where('minggu_ke', $week)
            ->with(['toilet.building', 'students', 'details', 'report'])
            ->orderBy('nama_regu')
            ->get()
            ->map(fn (JadwalPiket $s) => $this->service->formatSchedule($s))
            ->all();

        $toilets = MasterToilet::with('building')->orderBy('gedung_id')->orderBy('nama_toilet')->get()
            ->map(fn (MasterToilet $t) => $this->service->formatToilet($t))->all();

        $buildings = MasterGedung::withCount('toilets')->orderBy('nama_gedung')->get()
            ->map(fn (MasterGedung $b) => [
                'id' => $b->id,
                'name' => $b->nama_gedung,
                'description' => $b->deskripsi,
                'toilet_count' => $b->toilets_count,
            ])->all();

        $reports = HistoriPengerjaan::with(['schedule.toilet.building', 'toilet.building'])
            ->whereDate('tanggal', today())
            ->latest('created_at')
            ->get()
            ->map(fn (HistoriPengerjaan $r) => $this->service->formatReport($r))
            ->all();

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
        $day = $this->service->indonesianDay(today()->dayOfWeekIso);

        if (! JadwalPiket::where('status_jadwal', 'Aktif')->where('hari', $day)->where('minggu_ke', $week)->exists()) {
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
}
