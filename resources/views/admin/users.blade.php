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
        <div class="surface-heading">
            <div>
                <h2>Hak akses & peran</h2>
                <p>Panduan wewenang dan cakupan tugas setiap peran di panel.</p>
            </div>
            <i data-lucide="shield-check"></i>
        </div>

        <div class="role-guide-list">
            <!-- Role Admin -->
            <div class="role-guide-card">
                <div class="role-guide-header">
                    <div class="role-guide-title">
                        <i data-lucide="shield"></i>
                        <span>Admin</span>
                    </div>
                    <span class="badge">Akses Penuh</span>
                </div>
                <div class="role-guide-desc">
                    Memegang kendali menyeluruh atas konfigurasi sistem, akun pengguna, dan seluruh master data sekolah.
                </div>
                <ul class="role-guide-perms">
                    <li><i data-lucide="check"></i> Kelola akun login (tambah, ubah peran, hapus)</li>
                    <li><i data-lucide="check"></i> Master data Gedung, Toilet, Regu, dan Data Siswa</li>
                    <li><i data-lucide="check"></i> Susun jadwal piket dan terbitkan Token Akses harian</li>
                </ul>
            </div>

            <!-- Role Guru -->
            <div class="role-guide-card">
                <div class="role-guide-header">
                    <div class="role-guide-title">
                        <i data-lucide="user-check"></i>
                        <span>Guru</span>
                    </div>
                    <span class="badge badge-blue">Monitoring Piket</span>
                </div>
                <div class="role-guide-desc">
                    Koordinator piket harian yang memantau pelaksanaan kebersihan dan presensi siswa di lapangan.
                </div>
                <ul class="role-guide-perms">
                    <li><i data-lucide="check"></i> Pantau status kebersihan toilet per gedung secara real-time</li>
                    <li><i data-lucide="check"></i> Rekap kehadiran siswa petugas piket (Hadir / Tidak Hadir)</li>
                    <li><i data-lucide="check"></i> Tinjau jadwal dan verifikasi laporan kebersihan toilet</li>
                </ul>
            </div>

            <!-- Role Kesiswaan -->
            <div class="role-guide-card">
                <div class="role-guide-header">
                    <div class="role-guide-title">
                        <i data-lucide="graduation-cap"></i>
                        <span>Kesiswaan</span>
                    </div>
                    <span class="badge badge-warn">Data & Mutasi</span>
                </div>
                <div class="role-guide-desc">
                    Pengelola data induk kesiswaan, riwayat mutasi siswa, serta integritas rekam jejak kedisiplinan.
                </div>
                <ul class="role-guide-perms">
                    <li><i data-lucide="check"></i> Kelola data siswa aktif, rombel, dan import CSV/Excel</li>
                    <li><i data-lucide="check"></i> Proses mutasi siswa (pindah sekolah, mutasi kelas, lulus)</li>
                    <li><i data-lucide="check"></i> Otomatisasi penonaktifan jadwal siswa mutasi & arsip histori</li>
                </ul>
            </div>
        </div>

        <div class="notice" style="margin-top: 14px; margin-bottom: 0;">
            <i data-lucide="info"></i>
            <span><strong>Catatan:</strong> Siswa petugas piket tidak memerlukan akun pengguna, melainkan mengakses portal menggunakan <strong>Token Akses Harian</strong>.</span>
        </div>
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
