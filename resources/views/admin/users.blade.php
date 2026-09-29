@extends('layouts.app')

@section('title', 'Pengguna')
@section('page-title', 'Manajemen Pengguna')

@section('content')
<div class="page-heading"><div><p class="eyebrow">PENGELOLAAN AKSES</p><h1>Pengguna sistem</h1><p>Kelola akun admin, guru, dan petugas kesiswaan.</p></div></div>
<section class="content-grid">
    <div class="surface surface-pad">
        <div class="surface-heading"><div><h2>Tambah pengguna</h2><p>Akun disimpan ke database sekolah.</p></div><i data-lucide="user-round-plus"></i></div>
        <form class="form-stack" method="post" action="{{ route('users.store') }}">
            @csrf
            <label class="field"><span>Nama lengkap</span><input name="name" value="{{ old('name') }}" placeholder="Contoh: Sri Wahyuni, S.Pd." required maxlength="100"></label>
            <label class="field"><span>Username</span><input name="username" value="{{ old('username') }}" placeholder="nama.pengguna" required maxlength="40"></label>
            <label class="field"><span>Password awal</span><input name="password" type="password" autocomplete="new-password" minlength="8" required></label>
            <label class="field"><span>Role / jabatan</span><select name="role" required><option value="Guru">Guru</option><option value="Kesiswaan">Kesiswaan</option><option value="Admin">Admin</option></select></label>
            <div class="notice"><i data-lucide="info"></i><span>Password disimpan dalam bentuk hash dan tidak dapat dilihat kembali.</span></div>
            <button class="button button-primary" type="submit"><i data-lucide="plus"></i>Simpan pengguna</button>
        </form>
    </div>
    <div class="surface surface-pad">
        <div class="surface-heading"><div><h2>Hak akses</h2><p>Peran yang tersedia di panel.</p></div></div>
        <div class="stat-list"><div class="stat-row"><strong>Admin</strong><span>Pengaturan sistem dan semua data</span></div><div class="stat-row"><strong>Guru</strong><span>Jadwal dan status piket</span></div><div class="stat-row"><strong>Kesiswaan</strong><span>Data siswa dan arsip mutasi</span></div></div>
    </div>
</section>
<section class="surface surface-pad" style="margin-top:16px">
    <div class="surface-heading"><div><h2>Akun terdaftar</h2><p>{{ count($users) }} pengguna terdaftar.</p></div></div>
    <div class="table-wrap"><table class="data-table js-datatable"><thead><tr><th>Nama</th><th>Username</th><th>Role</th><th>Aksi</th></tr></thead><tbody>
        @forelse ($users as $user)
            <tr><td><strong>{{ $user['name'] }}</strong></td><td>{{ $user['username'] }}</td><td><span class="badge badge-blue">{{ $user['role'] }}</span></td><td><div class="table-actions"><form method="post" action="{{ route('users.delete', $user['id']) }}" data-confirm="Hapus pengguna {{ $user['name'] }}?">@csrf @method('DELETE')<button class="row-link" type="submit" aria-label="Hapus pengguna"><i data-lucide="trash-2"></i></button></form></div></td></tr>
        @empty
            <tr><td colspan="4"><div class="empty-state">Belum ada pengguna.</div></td></tr>
        @endforelse
    </tbody></table></div>
</section>
@endsection
