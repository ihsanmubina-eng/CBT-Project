<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Services\ResikKitaService;
use App\Services\SpreadsheetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function __construct(
        protected ResikKitaService $service,
        protected SpreadsheetService $spreadsheet,
    ) {}

    public function index(): View
    {
        $records = Siswa::whereIn('status_siswa', ['Mutasi', 'Lulus'])
            ->withCount('details')
            ->orderByDesc('tanggal_mutasi')
            ->get()
            ->map(fn (Siswa $s) => $this->service->formatHistory($s))
            ->all();

        return view('admin.history', [
            'records' => $records,
            'totalRecords' => count($records),
        ]);
    }

    public function edit(int $id): View
    {
        $record = $this->service->formatHistory(Siswa::withCount('details')->findOrFail($id));

        return view('admin.history-edit', compact('record'));
    }

    public function update(Request $request, int $id): RedirectResponse
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
            'nama_lengkap' => $input['name'],
            'nis' => $input['nis'],
            'rombel' => $input['class'],
            'tanggal_mutasi' => $input['date'],
            'jenis_mutasi' => $input['type'],
            'status_siswa' => $input['type'] === 'Lulus' ? 'Lulus' : 'Mutasi',
            'catatan_mutasi' => $input['note'] ?? null,
        ]);

        return redirect()->route('history.index')->with('success', 'Arsip siswa diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        Siswa::findOrFail($id)->update([
            'status_siswa' => 'Aktif',
            'tanggal_mutasi' => null,
            'jenis_mutasi' => null,
            'catatan_mutasi' => null,
        ]);

        return back()->with('success', 'Siswa dikembalikan ke status aktif.');
    }
}
