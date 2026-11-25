<?php

namespace App\Http\Controllers;

use App\Models\MasterGedung;
use App\Models\MasterToilet;
use App\Services\ResikKitaService;
use App\Services\SpreadsheetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BuildingController extends Controller
{
    public function __construct(
        protected ResikKitaService $service,
        protected SpreadsheetService $spreadsheet,
    ) {}

    public function index(Request $request): View
    {
        $buildings = MasterGedung::withCount('toilets')->orderBy('nama_gedung')->get();
        $toilets = MasterToilet::with('building')->get()->map(fn (MasterToilet $t) => $this->service->formatToilet($t))->all();

        if ($request->filled('q')) {
            $term = Str::lower($request->string('q')->value());
            $buildings = $buildings->filter(fn (MasterGedung $b) => Str::contains(
                Str::lower($b->nama_gedung.' '.$b->deskripsi),
                $term
            ));
        }

        $buildings = $buildings->map(fn (MasterGedung $b) => [
            'id' => $b->id,
            'name' => $b->nama_gedung,
            'description' => $b->deskripsi,
            'toilet_count' => $b->toilets_count,
        ])->values()->all();

        return view('admin.buildings', compact('buildings', 'toilets'));
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:master_gedung,nama_gedung'],
            'description' => ['nullable', 'string', 'max:180'],
        ]);

        MasterGedung::create(['nama_gedung' => $input['name'], 'deskripsi' => $input['description'] ?? null]);

        return back()->with('success', 'Gedung berhasil ditambahkan.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['building_file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120']]);
        $file = $request->file('building_file');
        $rawRows = $this->spreadsheet->readRows($file->getRealPath(), $file->getClientOriginalExtension());

        if (count($rawRows) < 2) {
            return back()->withErrors(['building_file' => 'File kosong atau tidak memiliki baris data.']);
        }

        $headers = array_map(fn ($h) => Str::lower(trim((string) $h)), array_shift($rawRows));
        $required = ['nama_gedung'];
        if (array_diff($required, $headers)) {
            return back()->withErrors(['building_file' => 'Format file gedung harus memiliki kolom: nama_gedung, deskripsi.']);
        }

        $col = array_flip($headers);
        $count = 0;
        foreach ($rawRows as $row) {
            $name = trim($row[$col['nama_gedung']] ?? '');
            if ($name === '') {
                continue;
            }
            $desc = isset($col['deskripsi']) ? trim($row[$col['deskripsi']] ?? '') : null;
            MasterGedung::firstOrCreate(['nama_gedung' => $name], ['deskripsi' => $desc]);
            $count++;
        }

        return back()->with('success', "{$count} data gedung berhasil diimpor.");
    }

    public function template()
    {
        $headers = ['nama_gedung', 'deskripsi'];
        $rows = [
            ['Gedung Graha Widya', 'Gedung pembelajaran teori dan perpustakaan pusat'],
            ['Gedung Pusat Praktik Kejuruan', 'Laboratorium PPLG, DKV, dan bengkel TJKT'],
        ];

        return $this->spreadsheet->downloadXlsx('template-master-gedung.xlsx', $headers, $rows);
    }

    public function edit(int $id): View
    {
        $b = MasterGedung::findOrFail($id);
        $building = ['id' => $b->id, 'name' => $b->nama_gedung, 'description' => $b->deskripsi];

        return view('admin.building-edit', compact('building'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('master_gedung', 'nama_gedung')->ignore($id)],
            'description' => ['nullable', 'string', 'max:180'],
        ]);

        MasterGedung::findOrFail($id)->update(['nama_gedung' => $input['name'], 'deskripsi' => $input['description'] ?? null]);

        return redirect()->route('buildings.index')->with('success', 'Data gedung diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $building = MasterGedung::findOrFail($id);
        if ($building->toilets()->exists()) {
            return back()->with('error', 'Gedung masih memiliki lokasi toilet; pindahkan atau hapus toilet terkait terlebih dahulu.');
        }

        $building->delete();

        return back()->with('success', 'Gedung berhasil dihapus.');
    }
}
