<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Services\ResikKitaService;
use App\Services\SpreadsheetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function __construct(
        protected ResikKitaService $service,
        protected SpreadsheetService $spreadsheet,
    ) {}

    public function index(): View
    {
        $students = Siswa::withCount('details')->orderBy('nama_lengkap')->get()
            ->map(fn (Siswa $s) => $this->service->formatStudent($s))->all();

        return view('admin.students', compact('students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'nis' => ['required', 'string', 'max:30', 'unique:siswa,nis'],
            'name' => ['required', 'string', 'max:100'],
            'class' => ['required', 'string', 'max:40'],
        ]);

        Siswa::create([
            'nis' => $input['nis'],
            'nama_lengkap' => $input['name'],
            'rombel' => $input['class'],
        ]);

        return back()->with('success', 'Data siswa berhasil disimpan.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['student_file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120']]);
        $file = $request->file('student_file');
        $rawRows = $this->spreadsheet->readRows($file->getRealPath(), $file->getClientOriginalExtension());

        if (count($rawRows) < 2) {
            return back()->withErrors(['student_file' => 'File kosong atau tidak memiliki data.']);
        }

        $headers = array_map(fn ($h) => Str::lower(trim((string) $h)), array_shift($rawRows));
        $required = ['nis', 'nama_lengkap', 'rombel'];
        if (array_diff($required, $headers)) {
            return back()->withErrors(['student_file' => 'Kolom file siswa harus memuat: nis, nama_lengkap, rombel.']);
        }

        $col = array_flip($headers);
        $rows = [];
        $seen = [];
        $line = 1;

        foreach ($rawRows as $values) {
            $line++;
            $row = [
                'nis' => trim((string) ($values[$col['nis']] ?? '')),
                'nama_lengkap' => trim((string) ($values[$col['nama_lengkap']] ?? '')),
                'rombel' => trim((string) ($values[$col['rombel']] ?? '')),
            ];

            $validator = Validator::make($row, [
                'nis' => ['required', 'string', 'max:30'],
                'nama_lengkap' => ['required', 'string', 'max:100'],
                'rombel' => ['required', 'string', 'max:40'],
            ]);

            if ($validator->fails() || isset($seen[$row['nis']])) {
                return back()->withErrors(['student_file' => "Baris {$line}: ".($validator->errors()->first() ?: 'NIS duplikat pada file.')]);
            }

            $seen[$row['nis']] = true;
            $rows[] = $row;
            if (count($rows) > 5000) {
                return back()->withErrors(['student_file' => 'Maksimal 5.000 siswa per file.']);
            }
        }

        if ($rows === []) {
            return back()->withErrors(['student_file' => 'File tidak berisi data siswa valid.']);
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

    public function template()
    {
        $headers = ['nis', 'nama_lengkap', 'rombel'];
        $rows = [
            ['12400101', 'Ahmad Fauzan Pratama', 'XII PPLG 1'],
            ['12400102', 'Alifia Zahra Khairunnisa', 'XII PPLG 1'],
        ];

        return $this->spreadsheet->downloadXlsx('template-data-siswa.xlsx', $headers, $rows);
    }

    public function archive(Request $request, int $id): RedirectResponse
    {
        $input = $request->validate([
            'tanggal_mutasi' => ['required', 'date'],
            'jenis_mutasi' => ['required', 'in:Pindah Sekolah,Mutasi Kelas,Lulus'],
            'catatan_mutasi' => ['nullable', 'string', 'max:500'],
        ]);

        $student = Siswa::findOrFail($id);
        $student->update(array_merge($input, [
            'status_siswa' => $input['jenis_mutasi'] === 'Lulus' ? 'Lulus' : 'Mutasi',
        ]));

        return back()->with('success', 'Siswa dipindahkan ke arsip mutasi.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $student = Siswa::findOrFail($id);
        if ($student->details()->exists() || $student->attendance()->exists() || $student->reports()->exists()) {
            return back()->with('error', 'Siswa memiliki histori. Arsipkan siswa agar riwayatnya tetap tersimpan.');
        }

        $student->delete();

        return back()->with('success', 'Data siswa berhasil dihapus.');
    }
}
