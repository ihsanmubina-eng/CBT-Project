@extends('layouts.app')

@section('title', 'Profil Pengguna')
@section('page-title', 'Profil Pengguna')

@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">AKUN PENGGUNA</p>
        <h1>Profil Saya</h1>
        <p>Kelola data identitas, foto profil, dan kredensial keamanan login Anda.</p>
    </div>
</div>

{{-- Hero Profile Summary Card --}}
<section class="surface surface-pad profile-hero-card" style="margin-bottom: 24px;">
    <div class="profile-hero">
        <div class="profile-avatar-wrapper">
            @if ($user->avatar_url)
                <img src="{{ $user->avatar_url }}" alt="{{ $user->nama_lengkap }}" class="profile-avatar-large profile-avatar-img">
            @else
                <div class="profile-avatar-large" aria-hidden="true">
                    {{ $user->initials }}
                </div>
            @endif
        </div>
        <div class="profile-hero-info">
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <h2 style="margin:0; font-size:20px; font-weight:700; color:var(--text-primary);">
                    {{ $user->nama_lengkap }}
                </h2>
                <span class="badge badge-info">{{ $user->role }}</span>
                <span class="badge badge-success">Akun Aktif</span>
            </div>
            <div class="profile-meta-tags">
                <span class="profile-meta-tag">
                    <i data-lucide="at-sign" style="width:14px; height:14px;"></i>
                    <strong>{{ $user->username }}</strong>
                </span>
                <span class="profile-meta-tag">
                    <i data-lucide="calendar" style="width:14px; height:14px;"></i>
                    Terdaftar sejak {{ $stats['joined_at'] }}
                </span>
                <span class="profile-meta-tag">
                    <i data-lucide="clock" style="width:14px; height:14px;"></i>
                    Terakhir diperbarui {{ $stats['last_updated'] }}
                </span>
            </div>
        </div>
    </div>
</section>

{{-- Ringkasan Metrik / Aktivitas Akun --}}
<section class="metric-grid" aria-label="Statistik Pengguna" style="margin-bottom: 24px;">
    <div class="metric">
        <div>
            <div class="metric-label">Peran Sistem</div>
            <div class="metric-value" style="font-size:20px;">{{ $user->role }}</div>
        </div>
        <div class="metric-icon"><i data-lucide="shield-check"></i></div>
    </div>
    <div class="metric">
        <div>
            <div class="metric-label">Token Akses Dibuat</div>
            <div class="metric-value">{{ $stats['total_tokens'] }}</div>
        </div>
        <div class="metric-icon"><i data-lucide="key-round"></i></div>
    </div>
    <div class="metric">
        <div>
            <div class="metric-label">Token Aktif Hari Ini</div>
            <div class="metric-value">{{ $stats['active_tokens'] }}</div>
        </div>
        <div class="metric-icon"><i data-lucide="ticket"></i></div>
    </div>
    <div class="metric">
        <div>
            <div class="metric-label">Laporan Terkoreksi</div>
            <div class="metric-value">{{ $stats['total_koreksi'] }}</div>
        </div>
        <div class="metric-icon"><i data-lucide="clipboard-check"></i></div>
    </div>
</section>

{{-- Form Pengaturan Profil & Keamanan --}}
<div class="content-grid">
    {{-- Card 1: Informasi Dasar Profil & Foto --}}
    <div class="surface surface-pad">
        <div class="surface-heading">
            <div>
                <h2>Informasi Profil</h2>
                <p>Perbarui foto profil, nama lengkap, dan username Anda.</p>
            </div>
            <i data-lucide="user-pen"></i>
        </div>

        <form class="form-stack" method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Avatar Upload Section --}}
            <div class="profile-photo-editor">
                <div class="photo-preview-wrap">
                    @if ($user->avatar_url)
                        <img id="avatar-preview-img" src="{{ $user->avatar_url }}" alt="Preview" class="profile-avatar-preview">
                        <div id="avatar-preview-fallback" class="profile-avatar-large" style="display:none;">{{ $user->initials }}</div>
                    @else
                        <img id="avatar-preview-img" src="" alt="Preview" class="profile-avatar-preview" style="display:none;">
                        <div id="avatar-preview-fallback" class="profile-avatar-large">{{ $user->initials }}</div>
                    @endif
                </div>
                <div class="photo-controls">
                    <div class="photo-actions-row">
                        <label class="button button-secondary" for="avatar-file-input" style="cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                            <i data-lucide="camera"></i>
                            <span>Pilih Foto Baru</span>
                            <input id="avatar-file-input" name="foto" type="file" accept="image/jpeg,image/png,image/webp" style="display:none;">
                        </label>
                        @if ($user->foto)
                            <button class="button button-secondary" type="button" id="btn-remove-photo" style="display:inline-flex; align-items:center; gap:6px; color:var(--status-danger);">
                                <i data-lucide="trash-2"></i>
                                <span>Hapus Foto</span>
                            </button>
                            <input type="hidden" name="hapus_foto" id="input-remove-photo" value="0">
                        @endif
                    </div>
                    <div id="photo-filename-label" style="font-size:12px; color:var(--text-muted); margin-top:2px;">
                        Format JPG, PNG, atau WebP (Maks. 2MB).
                    </div>
                </div>
            </div>

            <label class="field">
                <span>Nama Lengkap</span>
                <input name="nama_lengkap" type="text" value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required maxlength="100">
            </label>

            <label class="field">
                <span>Username (Kredensial Login)</span>
                <input name="username" type="text" value="{{ old('username', $user->username) }}" required maxlength="40">
            </label>

            <label class="field">
                <span>Role / Hak Akses</span>
                <input type="text" value="{{ $user->role }}" disabled style="background:var(--surface-subtle); color:var(--text-muted); cursor:not-allowed;">
                <span class="field-help" style="font-size:12px; color:var(--text-muted); margin-top:4px;">
                    Role akun dikelola langsung oleh Administrator utama melalui menu Create User.
                </span>
            </label>

            <div style="margin-top: 12px;">
                <button class="button button-primary" type="submit">
                    <i data-lucide="check"></i>Simpan Perubahan Profil
                </button>
            </div>
        </form>
    </div>

    {{-- Card 2: Keamanan & Ganti Password --}}
    <div class="surface surface-pad">
        <div class="surface-heading">
            <div>
                <h2>Keamanan Akun</h2>
                <p>Ubah kata sandi login untuk melindungi akun Anda.</p>
            </div>
            <i data-lucide="lock"></i>
        </div>

        <form class="form-stack" method="post" action="{{ route('profile.password') }}">
            @csrf
            @method('PUT')

            <label class="field">
                <span>Password Saat Ini</span>
                <input name="current_password" type="password" placeholder="Masukkan password saat ini" required>
            </label>

            <label class="field">
                <span>Password Baru</span>
                <input name="password" type="password" placeholder="Minimal 8 karakter" minlength="8" required>
            </label>

            <label class="field">
                <span>Konfirmasi Password Baru</span>
                <input name="password_confirmation" type="password" placeholder="Ulangi password baru" minlength="8" required>
            </label>

            <div class="notice">
                <i data-lucide="shield-alert"></i>
                <span>Gunakan minimal 8 karakter dengan kombinasi huruf dan angka untuk keamanan optimal.</span>
            </div>

            <div style="margin-top: 12px;">
                <button class="button button-primary" type="submit">
                    <i data-lucide="key"></i>Perbarui Password
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const avatarInput = document.getElementById('avatar-file-input');
    const previewImg = document.getElementById('avatar-preview-img');
    const previewFallback = document.getElementById('avatar-preview-fallback');
    const filenameLabel = document.getElementById('photo-filename-label');
    const btnRemovePhoto = document.getElementById('btn-remove-photo');
    const inputRemovePhoto = document.getElementById('input-remove-photo');

    avatarInput?.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        if (inputRemovePhoto) inputRemovePhoto.value = '0';

        if (!file.type.match(/^image\//i)) {
            alert('File harus berupa gambar (JPG, PNG, atau WebP).');
            this.value = '';
            return;
        }

        filenameLabel.textContent = 'Memproses foto...';
        filenameLabel.style.color = 'var(--text-muted)';

        const reader = new FileReader();
        reader.onload = function (e) {
            const img = new Image();
            img.onload = function () {
                const maxDim = 512;
                let w = img.width;
                let h = img.height;

                if (w > h) {
                    if (w > maxDim) {
                        h = Math.round((h * maxDim) / w);
                        w = maxDim;
                    }
                } else {
                    if (h > maxDim) {
                        w = Math.round((w * maxDim) / h);
                        h = maxDim;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, w, h);

                const dataUrl = canvas.toDataURL('image/jpeg', 0.88);
                if (previewImg) {
                    previewImg.src = dataUrl;
                    previewImg.style.display = 'block';
                }
                if (previewFallback) previewFallback.style.display = 'none';

                canvas.toBlob(function (blob) {
                    if (blob && window.DataTransfer) {
                        const newFile = new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });
                        const dt = new DataTransfer();
                        dt.items.add(newFile);
                        avatarInput.files = dt.files;
                        filenameLabel.textContent = file.name + ' (Disesuaikan: ' + Math.round(blob.size / 1024) + ' KB) ✓ Siap disimpan';
                        filenameLabel.style.color = '#059669';
                    } else {
                        filenameLabel.textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
                        filenameLabel.style.color = 'var(--text-muted)';
                    }
                }, 'image/jpeg', 0.88);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });

    btnRemovePhoto?.addEventListener('click', function () {
        if (inputRemovePhoto) inputRemovePhoto.value = '1';
        if (avatarInput) avatarInput.value = '';
        if (previewImg) previewImg.style.display = 'none';
        if (previewFallback) previewFallback.style.display = 'grid';
        filenameLabel.textContent = 'Foto profil akan dihapus setelah Anda menekan tombol "Simpan Perubahan Profil".';
        filenameLabel.style.color = '#ef4444';
    });
</script>
@endsection