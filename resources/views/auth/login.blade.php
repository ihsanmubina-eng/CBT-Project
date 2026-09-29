<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · ResikKita</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
</head>
<body class="auth-page">
    <main class="auth-wrap">
        <a class="auth-brand" href="{{ route('public.dashboard') }}">
            <img src="{{ asset('assets/LogoSekolah.png') }}" alt="Logo SMK Wikrama Bogor">
            <span><strong>ResikKita</strong><small>SMK Wikrama Bogor</small></span>
        </a>
        <section class="auth-intro">
            <p class="eyebrow">KEBERSIHAN, TANGGUNG JAWAB BERSAMA</p>
            <h1>Monitoring kebersihan toilet sekolah.</h1>
            <p>Kelola fasilitas, jadwal piket, dan laporan harian dari satu tempat.</p>
        </section>
        <section class="auth-panel" aria-labelledby="login-title">
            <div class="auth-heading"><span class="eyebrow">RESIKKITA PORTAL</span><h2 id="login-title">Selamat datang</h2><p>Masuk untuk melanjutkan ke sistem.</p></div>
            @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
            @if ($errors->any())<div class="alert alert-error" role="alert">{{ $errors->first() }}</div>@endif
            @if (session('error'))<div class="alert alert-error" role="alert">{{ session('error') }}</div>@endif
            @php($studentHasError = $errors->has('token') || $errors->has('team'))
            <div class="segmented" role="tablist" aria-label="Jenis akses">
                <button class="segment {{ $studentHasError ? '' : 'active' }}" type="button" id="staff-tab" role="tab" aria-selected="{{ $studentHasError ? 'false' : 'true' }}" aria-controls="staff-panel" data-auth-tab="staff">Admin / Guru</button>
                <button class="segment {{ $studentHasError ? 'active' : '' }}" type="button" id="student-tab" role="tab" aria-selected="{{ $studentHasError ? 'true' : 'false' }}" aria-controls="student-panel" data-auth-tab="student">Piket siswa</button>
            </div>
            <form class="form-stack {{ $studentHasError ? 'is-hidden' : '' }}" id="staff-panel" method="post" action="{{ route('login.authenticate') }}" data-auth-panel="staff" aria-hidden="{{ $studentHasError ? 'true' : 'false' }}">
                @csrf
                <label class="field"><span>Username</span><input name="username" value="{{ old('username') }}" autocomplete="username" required></label>
                <label class="field"><span>Kata sandi</span><input name="password" type="password" autocomplete="current-password" required></label>
                <button class="button button-primary button-full" type="submit"><i data-lucide="log-in"></i>Masuk ke dashboard</button>
                <p class="form-hint"><i data-lucide="info"></i>Akun staf pertama dibuat melalui perintah <strong>php artisan resikkita:create-staff-user</strong>.</p>
            </form>
            <form class="form-stack {{ $studentHasError ? '' : 'is-hidden' }}" id="student-panel" method="post" action="{{ route('login.student') }}" data-auth-panel="student" aria-hidden="{{ $studentHasError ? 'false' : 'true' }}">
                @csrf
                <label class="field"><span>Kode dari guru</span><input name="token" value="{{ old('token') }}" placeholder="Contoh: PKT-7821" autocomplete="off" required></label>
                <label class="field"><span>Regu piket</span><select name="team" required>@foreach (range(1, 4) as $team)<option value="{{ $team }}" @selected((int) old('team', 1) === $team)>Regu {{ $team }}</option>@endforeach</select></label>
                <button class="button button-primary button-full" type="submit"><i data-lucide="key-round"></i>Lihat tugas regu</button>
                <p class="form-hint"><i data-lucide="info"></i>Gunakan token aktif yang dibagikan guru hari ini.</p>
            </form>
            <a class="auth-public-link" href="{{ route('public.dashboard') }}">Lihat dashboard publik <i data-lucide="arrow-up-right"></i></a>
        </section>
        <footer class="auth-footer">© {{ date('Y') }} ResikKita <span>·</span> SMK Wikrama Bogor</footer>
    </main>
    <script>
        if (window.lucide) window.lucide.createIcons();
        const tabs = document.querySelectorAll('[data-auth-tab]');
        const panels = document.querySelectorAll('[data-auth-panel]');
        tabs.forEach((tab) => tab.addEventListener('click', () => {
            const selected = tab.dataset.authTab;
            tabs.forEach((item) => {
                const active = item === tab;
                item.classList.toggle('active', active);
                item.setAttribute('aria-selected', String(active));
            });
            panels.forEach((panel) => {
                const active = panel.dataset.authPanel === selected;
                panel.classList.toggle('is-hidden', !active);
                panel.setAttribute('aria-hidden', String(!active));
            });
        }));
    </script>
</body>
</html>
