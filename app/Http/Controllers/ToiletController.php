<?php

namespace App\Http\Controllers;

use App\Models\MasterGedung;
use App\Models\MasterToilet;
use App\Services\ResikKitaService;
use App\Services\SpreadsheetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ToiletController extends Controller
{
    public function __construct(
        protected ResikKitaService $service,
        protected SpreadsheetService $spreadsheet,
    ) {}

    public function index(Request $request): View
    {
        $query = MasterToilet::with('building')->orderBy('gedung_id')->orderBy('nama_toilet');

        if ($request->filled('q')) {
            $term = Str::lower($request->string('q')->value());
            $query->where(fn ($b) => $b->whereRaw('LOWER(nama_toilet) LIKE ?', ["%{$term}%"])
                ->orWhereRaw('LOWER(lantai) LIKE ?', ["%{$term}%"]));
        }

        $toilets = $query->get()->map(fn (MasterToilet $t) => $this->service->formatToilet($t))->all();
        $buildings = MasterGedung::orderBy('nama_gedung')->get()->map(fn (MasterGedung $b) => [
            'id' => $b->id, 'name' => $b->nama_gedung,
        ])->all();

        return view('admin.toilets', compact('toilets', 'buildings'));
    }

    public function store(Request $request): RedirectResponse
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
            'gedung_id' => $building->id,
            'nama_toilet' => $input['name'],
            'lantai' => $input['floor'],
            'jenis_toilet' => $input['type'],
            'jumlah_bilik' => $input['cubicles'],
            'status_toilet' => $input['status'],
        ]);

        return back()->with('success', 'Lokasi toilet berhasil ditambahkan.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['toilet_file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120']]);
        $file = $request->file('toilet_file');
        $rawRows = $this->spreadsheet->readRows($file->getRealPath(), $file->getClientOriginalExtension());

        if (count($rawRows) < 2) {
            return back()->withErrors(['toilet_file' => 'File kosong atau tidak memiliki baris data.']);
        }

        $headers = array_map(fn ($h) => Str::lower(trim((string) $h)), array_shift($rawRows));
        $required = ['nama_toilet', 'nama_gedung', 'lantai'];
        if (array_diff($required, $headers)) {
            return back()->withErrors(['toilet_file' => 'Kolom file harus memuat minimal: nama_toilet, nama_gedung, lantai.']);
        }

        $col = array_flip($headers);
        $count = 0;
        foreach ($rawRows as $idx => $row) {
            $name = trim($row[$col['nama_toilet']] ?? '');
            $buildingName = trim($row[$col['nama_gedung']] ?? '');
            $floor = trim($row[$col['lantai']] ?? '');
            if ($name === '' || $buildingName === '' || $floor === '') {
                continue;
            }

            $building = MasterGedung::firstOrCreate(['nama_gedung' => $buildingName]);
            $type = isset($col['jenis_toilet']) ? trim($row[$col['jenis_toilet']] ?? 'Bersama') : 'Bersama';
            if (! in_array($type, ['Pria', 'Wanita', 'Bersama'], true)) {
                $type = 'Bersama';
            }
            $cubicles = isset($col['jumlah_bilik']) ? max(1, (int) ($row[$col['jumlah_bilik']] ?? 1)) : 1;
            $status = isset($col['status_toilet']) ? trim($row[$col['status_toilet']] ?? 'Aktif') : 'Aktif';
            if (! in_array($status, ['Aktif', 'Tidak aktif'], true)) {
                $status = 'Aktif';
            }

            MasterToilet::updateOrCreate([
                'gedung_id' => $building->id,
                'nama_toilet' => $name,
                'lantai' => $floor,
            ], [
                'jenis_toilet' => $type,
                'jumlah_bilik' => $cubicles,
                'status_toilet' => $status,
            ]);
            $count++;
        }

        return back()->with('success', "{$count} lokasi toilet berhasil diimpor.");
    }

    public function template()
    {
        $headers = ['nama_toilet', 'nama_gedung', 'lantai', 'jenis_toilet', 'jumlah_bilik', 'status_toilet'];
        $rows = [
            ['Toilet Siswa Putra Lt. 1', 'Gedung Graha Widya', 'Lantai 1', 'Pria', 4, 'Aktif'],
            ['Toilet Siswa Putri Lt. 1', 'Gedung Graha Widya', 'Lantai 1', 'Wanita', 4, 'Aktif'],
        ];

        return $this->spreadsheet->downloadXlsx('template-master-toilet.xlsx', $headers, $rows);
    }

    public function edit(int $id): View
    {
        $toilet = $this->service->formatToilet(MasterToilet::with('building')->findOrFail($id));
        $buildings = MasterGedung::orderBy('nama_gedung')->get()->map(fn (MasterGedung $b) => ['id' => $b->id, 'name' => $b->nama_gedung])->all();

        return view('admin.toilet-edit', compact('toilet', 'buildings'));
    }

    public function update(Request $request, int $id): RedirectResponse
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
            'gedung_id' => $building->id,
            'nama_toilet' => $input['name'],
            'lantai' => $input['floor'],
            'jenis_toilet' => $input['type'],
            'jumlah_bilik' => $input['cubicles'],
            'status_toilet' => $input['status'],
        ]);

        return redirect()->route('toilets.index')->with('success', 'Data toilet diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $toilet = MasterToilet::findOrFail($id);
        if ($toilet->schedules()->exists() || $toilet->reports()->exists()) {
            return back()->with('error', 'Toilet memiliki jadwal atau histori laporan; ubah statusnya menjadi Tidak aktif.');
        }

        $toilet->delete();

        return back()->with('success', 'Lokasi toilet berhasil dihapus.');
    }
}
