<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::orderBy('nama_lengkap')->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->nama_lengkap,
                'username' => $u->username,
                'role' => $u->role,
            ])->all();

        return view('admin.users', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:40', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'role' => ['required', 'in:Admin,Guru,Kesiswaan'],
        ]);

        User::create([
            'nama_lengkap' => $input['name'],
            'username' => $input['username'],
            'password' => $input['password'],
            'role' => $input['role'],
        ]);

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        if ($user->is(Auth::user())) {
            return back()->with('error', 'Akun yang sedang digunakan tidak bisa dihapus.');
        }
        if ($user->tokens()->exists() || $user->koreksiLaporan()->exists()) {
            return back()->with('error', 'Akun memiliki riwayat token atau koreksi; ubah statusnya melalui pengelolaan akun.');
        }

        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}
