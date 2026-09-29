<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tugas Regu {{ $team }} · ResikKita</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
</head>
<body class="petugas-page">
    <header class="petugas-topbar">
        <a class="brand" href="{{ route('petugas.dashboard') }}"><img src="{{ asset('assets/LogoSekolah.png') }}" alt="Logo SMK Wikrama Bogor"><span class="brand-copy"><strong>ResikKita</strong><small>Portal petugas piket</small></span></a>
        <div class="petugas-top-actions"><span class="team-chip"><i data-lucide="users-round"></i>Regu {{ $team }}</span><form method="post" action="{{ route('petugas.logout') }}">@csrf<button class="button button-secondary button-small" type="submit"><i data-lucide="log-out"></i>Keluar</button></form></div>
    </header>
    <main class="petugas-content">
        @if (session('success'))<div class="alert alert-success" role="status"><i data-lucide="circle-check"></i><span>{{ session('success') }}</span></div>@endif
        @if (session('error'))<div class="alert alert-error" role="alert"><i data-lucide="circle-alert"></i><span>{{ session('error') }}</span></div>@endif
        @if ($errors->any())<div class="alert alert-error" role="alert"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif

        <div class="petugas-heading"><p class="eyebrow">TUGAS HARI INI · {{ $schedule['day'] }}</p><h1>Halo, Regu {{ $team }}</h1><p>Periksa lokasi yang menjadi tanggung jawab regumu.</p></div>
        <section class="assignment-panel" aria-labelledby="location-title">
            <div class="assignment-map"><i data-lucide="map-pin-check-inside"></i><span>LOKASI TUGAS</span></div>
            <div class="assignment-main">
                <div><p class="assignment-building">{{ $schedule['building'] }} · Minggu {{ $schedule['week'] }}</p><h2 id="location-title">{{ $schedule['room'] }}</h2><p class="assignment-members"><i data-lucide="users-round"></i>{{ $schedule['students'] }} <span>·</span> {{ $schedule['class'] }}</p></div>
                <div class="assignment-state"><span class="badge {{ $report ? '' : 'badge-warn' }}">{{ $report ? 'Laporan terkirim' : 'Menunggu pemeriksaan' }}</span><small>1 lokasi toilet untuk Regu {{ $team }}</small></div>
            </div>
        </section>

        @if ($report)
            <section class="report-result surface">
                <div class="report-result-head"><span class="metric-icon"><i data-lucide="clipboard-check"></i></span><div><p class="eyebrow">LAPORAN TERKIRIM · {{ $report['waktu_kirim'] }}</p><h2>Status kebersihan: {{ $report['status_kebersihan'] }}</h2></div><span class="badge">Terkunci</span></div>
                @if ($report['catatan'])<div class="report-note"><strong>Catatan regu</strong><p>{{ $report['catatan'] }}</p></div>@endif
                <div class="notice"><i data-lucide="lock-keyhole"></i><span>Laporan sudah dikirim dan terkunci. Hubungi guru jika perlu ada koreksi.</span></div>
            </section>
        @else
            <section class="surface report-form-panel">
                <div class="surface-heading"><div><h2>Hasil pemeriksaan</h2><p>Pilih kondisi toilet setelah regu menyelesaikan pemeriksaan.</p></div><span class="metric-icon"><i data-lucide="clipboard-list"></i></span></div>
                <form class="form-stack" method="post" action="{{ route('petugas.report') }}">
                    @csrf
                    <fieldset class="status-choices"><legend>Status kebersihan</legend>
                        <label class="status-choice"><input type="radio" name="status_kebersihan" value="Bersih" required><span><i data-lucide="sparkles"></i><strong>Bersih</strong><small>Sudah layak digunakan</small></span></label>
                        <label class="status-choice"><input type="radio" name="status_kebersihan" value="Perlu dibersihkan" required><span><i data-lucide="brush-cleaning"></i><strong>Perlu dibersihkan</strong><small>Masih perlu penanganan</small></span></label>
                        <label class="status-choice"><input type="radio" name="status_kebersihan" value="Rusak" required><span><i data-lucide="triangle-alert"></i><strong>Rusak</strong><small>Perlu dilaporkan ke guru</small></span></label>
                    </fieldset>
                    <label class="field"><span>Catatan (opsional)</span><textarea name="catatan" placeholder="Contoh: keran air perlu diperbaiki" maxlength="500">{{ old('catatan') }}</textarea></label>
                    <div class="report-submit-row"><span><i data-lucide="lock-keyhole"></i>Setelah dikirim, laporan akan terkunci.</span><button class="button button-primary" type="submit"><i data-lucide="send"></i>Kirim laporan regu</button></div>
                </form>
            </section>
        @endif
        <footer class="petugas-footer">ResikKita <span>·</span> SMK Wikrama Bogor</footer>
    </main>
    <script>if (window.lucide) window.lucide.createIcons();</script>
</body>
</html>