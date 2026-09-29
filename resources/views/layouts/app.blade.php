<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ResikKita') · ResikKita</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.6/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.6/js/dataTables.min.js"></script>
    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
</head>
<body class="admin-shell">
    <div class="mobile-scrim" data-sidebar-close></div>
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="{{ route('dashboard') }}">
            <img src="{{ asset('assets/LogoSekolah.png') }}" alt="Logo SMK Wikrama Bogor">
            <span class="brand-copy"><strong>ResikKita</strong><small>Sistem Monitoring Toilet</small></span>
        </a>
        <nav class="side-nav" aria-label="Navigasi utama">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i data-lucide="layout-dashboard"></i><span>Dashboard</span></a>
            <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><i data-lucide="user-round-plus"></i><span>Create User</span></a>
            <a class="nav-link {{ request()->routeIs('students.*') ? 'active' : '' }}" href="{{ route('students.index') }}"><i data-lucide="graduation-cap"></i><span>Data Siswa</span></a>
            <a class="nav-link {{ request()->routeIs('schedule.*') ? 'active' : '' }}" href="{{ route('schedule.index') }}"><i data-lucide="calendar-days"></i><span>Jadwal Piket</span></a>
            <a class="nav-link {{ request()->routeIs('buildings.*') ? 'active' : '' }}" href="{{ route('buildings.index') }}"><i data-lucide="building-2"></i><span>Master Gedung</span></a>
            <a class="nav-link {{ request()->routeIs('toilets.*') ? 'active' : '' }}" href="{{ route('toilets.index') }}"><i data-lucide="toilet"></i><span>Master Toilet</span></a>
            <a class="nav-link {{ request()->routeIs('history.*') ? 'active' : '' }}" href="{{ route('history.index') }}"><i data-lucide="history"></i><span>Histori Data</span></a>
        </nav>
        <div class="sidebar-bottom">
            <a class="public-link" href="{{ route('public.dashboard') }}"><i data-lucide="external-link"></i><span>Dashboard publik</span></a>
            <div class="school-name">SMK Wikrama Bogor</div>
            <div class="version">ResikKita <span>·</span> Panel Admin</div>
        </div>
    </aside>

    <div class="workspace">
        <header class="topbar">
            <button class="icon-button menu-toggle" type="button" aria-label="Buka menu" aria-expanded="false" data-sidebar-toggle><i data-lucide="menu"></i></button>
            <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
            <div class="topbar-user">
                @php($userNameParts = preg_split('/\s+/', auth()->user()->nama_lengkap))
                <div class="user-copy"><strong>{{ auth()->user()->nama_lengkap }}</strong><span>{{ auth()->user()->role }}</span></div>
                <div class="avatar" aria-hidden="true">{{ collect($userNameParts)->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('') }}</div>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="icon-button logout-button" type="submit" aria-label="Keluar" title="Keluar"><i data-lucide="log-out"></i></button>
                </form>
            </div>
        </header>
        <main class="page-content">
            @if (session('success'))
                <div class="alert alert-success" role="status"><i data-lucide="circle-check"></i><span>{{ session('success') }}</span><button class="alert-close" type="button" aria-label="Tutup pesan" data-dismiss><i data-lucide="x"></i></button></div>
            @endif
            @if (session('error'))
                <div class="alert alert-error" role="alert"><i data-lucide="circle-alert"></i><span>{{ session('error') }}</span></div>
            @endif
            @if ($errors->any())
                <div class="alert alert-error" role="alert"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>
            @endif
            @yield('content')
        </main>
    </div>
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
        const sidebar = document.querySelector('#sidebar');
        const menuToggle = document.querySelector('[data-sidebar-toggle]');
        const closeMenu = () => {
            sidebar?.classList.remove('sidebar-open');
            document.body.classList.remove('menu-open');
            menuToggle?.setAttribute('aria-expanded', 'false');
        };
        menuToggle?.addEventListener('click', () => {
            const open = sidebar.classList.toggle('sidebar-open');
            document.body.classList.toggle('menu-open', open);
            menuToggle.setAttribute('aria-expanded', String(open));
        });
        document.querySelectorAll('[data-sidebar-close]').forEach((el) => el.addEventListener('click', closeMenu));
        document.querySelectorAll('[data-dismiss]').forEach((el) => el.addEventListener('click', () => el.closest('.alert')?.remove()));
        document.querySelectorAll('[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        }));
        document.querySelectorAll('[data-open-dialog]').forEach((button) => button.addEventListener('click', () => {
            const dialog = document.getElementById(button.dataset.openDialog);
            if (!dialog) return;
            dialog.showModal();
            const url = new URL(window.location.href);
            url.searchParams.set('edit', button.dataset.dialogKey);
            window.history.replaceState(null, '', url);
        }));
        document.querySelectorAll('[data-dialog-close]').forEach((button) => button.addEventListener('click', () => button.closest('dialog')?.close()));
        document.querySelectorAll('[data-edit-dialog]').forEach((dialog) => {
            dialog.addEventListener('click', (event) => {
                if (event.target === dialog) dialog.close();
            });
            dialog.addEventListener('close', () => {
                const url = new URL(window.location.href);
                if (url.searchParams.get('edit') === dialog.dataset.dialogKey) {
                    url.searchParams.delete('edit');
                    window.history.replaceState(null, '', url);
                }
            });
        });
        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            const openDialog = document.querySelector('[data-edit-dialog][open]');
            if (openDialog) {
                event.preventDefault();
                openDialog.close();
            }
        });
        const editKey = new URLSearchParams(window.location.search).get('edit');
        const activeDialog = [...document.querySelectorAll('[data-edit-dialog]')].find((dialog) => dialog.dataset.dialogKey === editKey);
        activeDialog?.showModal();
    </script>
</body>
</html>
