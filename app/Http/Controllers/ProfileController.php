<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $stats = [
            'total_tokens' => $user->tokens()->count(),
            'active_tokens' => $user->tokens()->where('status', 'Aktif')->count(),
            'total_koreksi' => $user->koreksiLaporan()->count(),
            'joined_at' => $user->created_at ? $user->created_at->translatedFormat('d F Y') : '-',
            'last_updated' => $user->updated_at ? $user->updated_at->diffForHumans() : '-',
        ];

        return view('admin.profile', compact('user', 'stats'));
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $input = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:40', Rule::unique('users', 'username')->ignore($user->id)],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'hapus_foto' => ['nullable', 'in:1,true'],
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username ini sudah digunakan oleh akun lain.',
            'foto.image' => 'File harus berupa gambar (JPG, PNG, atau WebP).',
            'foto.mimes' => 'Format file harus berupa JPG, JPEG, PNG, atau WebP.',
            'foto.max' => 'Ukuran foto profil maksimal 2 MB.',
            'foto.uploaded' => 'Ukuran file foto melebihi batas upload server PHP (maksimal 2 MB). Silakan pilih foto dengan resolusi lebih kecil.',
        ]);

        $user->nama_lengkap = $input['nama_lengkap'];
        $user->username = $input['username'];

        if ($request->boolean('hapus_foto')) {
            if ($user->foto && Storage::disk('public')->exists($user->foto)) {
                Storage::disk('public')->delete($user->foto);
            }
            $user->foto = null;
        } elseif ($request->hasFile('foto')) {
            if ($user->foto && Storage::disk('public')->exists($user->foto)) {
                Storage::disk('public')->delete($user->foto);
            }
            $user->foto = $request->file('foto')->store('avatars', 'public');
        }

        $user->save();

        return back()->with('success', 'Profil akun dan foto berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal harus 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password berhasil diperbarui.');
    }
}
