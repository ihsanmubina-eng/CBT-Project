<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Publik · ResikKita</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.6/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.6/js/dataTables.min.js"></script>
    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
</head>
<body class="public-page">
    <header class="public-topbar">
        <a class="brand" href="{{ route('public.dashboard') }}"><img src="{{ asset('assets/LogoSekolah.png') }}" alt="Logo SMK Wikrama Bogor"><span class="brand-copy"><strong>ResikKita</strong><small>SMK Wikrama Bogor</small></span></a>
        <div class="public-top-actions"><a class="button button-secondary" href="{{ route('login') }}"><i data-lucide="log-in"></i>Masuk staf</a></div>
    </header>
    <main class="public-content">
        @if (session('success'))<div class="alert alert-success" role="status"><i data-lucide="circle-check"></i>{{ session('success') }}</div>@endif
        <div class="public-heading"><div><p class="eyebrow">MONITORING HARIAN</p><h1>Status piket kebersihan</h1><p>Informasi penugasan siswa dan kondisi toilet sekolah.</p></div><div class="public-count"><strong>{{ count($assignments) }}</strong> penugasan ditampilkan</div></div>
        <div class="toolbar">
            <div><h2 class="section-title">Jadwal dan pemeriksaan hari ini</h2><p class="section-subtitle">Data ditampilkan untuk informasi warga sekolah.</p></div>
        </div>
        <div class="table-wrap"><table class="data-table js-datatable"><thead><tr><th>No</th><th>Nama siswa</th><th>Rombel</th><th>Gedung / toilet</th><th>Status piket</th><th>Kehadiran</th></tr></thead><tbody>
            @forelse ($assignments as $index => $assignment)
                <tr><td>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</td><td><strong>{{ $assignment['students'] }}</strong><small>{{ $assignment['day'] }} · Minggu {{ $assignment['week'] }}</small></td><td>{{ $assignment['class'] }}</td><td><strong>{{ $assignment['building'] }}</strong><small>{{ $assignment['room'] }}</small></td><td><span class="badge {{ $assignment['status'] === 'Selesai' ? '' : 'badge-warn' }}">{{ $assignment['status'] }}</span></td><td><span class="badge {{ $assignment['attendance'] === 'Hadir' ? '' : 'badge-warn' }}">{{ $assignment['attendance'] }}</span></td></tr>
            @empty
                <tr><td colspan="6"><div class="empty-state">Tidak ada jadwal yang cocok.</div></td></tr>
            @endforelse
        </tbody></table></div>
    </main>
    <footer class="public-footer">ResikKita · SMK Wikrama Bogor</footer>
    <script>
        if (window.lucide) window.lucide.createIcons();
        document.querySelectorAll('.js-datatable').forEach((table) => {
            if (table.querySelector('tbody td[colspan]')) return;
            new DataTable(table, {
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                order: [],
                language: {
                    search: 'Cari',
                    searchPlaceholder: 'Cari data...',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: '_START_–_END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada data',
                    paginate: { first: 'Awal', last: 'Akhir', next: 'Berikutnya', previous: 'Sebelumnya' }
                }
            });
        });
    </script>
</body>
</html>