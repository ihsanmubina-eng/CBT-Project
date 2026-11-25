<?php

namespace App\Http\Controllers;

use App\Models\MasterGedung;
use App\Models\MasterRegu;
use App\Models\Siswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(): View
    {
        $teams = MasterRegu::with(['building', 'leader'])
            ->withCount('schedules')
            ->orderBy('id')
            ->get();

        $buildings = MasterGedung::orderBy('nama_gedung')->get();
        $students = Siswa::where('status_siswa', 'Aktif')->orderBy('nama_lengkap')->get();

        return view('admin.teams', [
            'teams' => $teams,
            'buildings' => $buildings,
            'students' => $students,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_regu' => ['required', 'string', 'max:100'],
            'gedung_id' => ['required', 'exists:master_gedung,id'],
            'ketua_siswa_id' => ['nullable', 'exists:siswa,id'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        MasterRegu::create($data);

        return redirect()->route('teams.index')->with('success', "Master Regu {$data['nama_regu']} berhasil ditambahkan.");
    }

    public function edit(int $id): View
    {
        $team = MasterRegu::with(['building', 'leader'])->findOrFail($id);
        $buildings = MasterGedung::orderBy('nama_gedung')->get();
        $students = Siswa::where('status_siswa', 'Aktif')->orderBy('nama_lengkap')->get();

        return view('admin.team-edit', [
            'team' => $team,
            'buildings' => $buildings,
            'students' => $students,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $team = MasterRegu::findOrFail($id);

        $data = $request->validate([
            'nama_regu' => ['required', 'string', 'max:100'],
            'gedung_id' => ['required', 'exists:master_gedung,id'],
            'ketua_siswa_id' => ['nullable', 'exists:siswa,id'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $team->update($data);

        return redirect()->route('teams.index')->with('success', "Data {$team->nama_regu} berhasil diperbarui.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $team = MasterRegu::withCount('schedules')->findOrFail($id);

        if ($team->schedules_count > 0) {
            return back()->with('error', "Regu {$team->nama_regu} tidak dapat dihapus karena masih terikat dengan {$team->schedules_count} jadwal piket.");
        }

        $name = $team->nama_regu;
        $team->delete();

        return redirect()->route('teams.index')->with('success', "Regu {$name} berhasil dihapus.");
    }
}
