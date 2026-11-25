<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Piket Regu {{ $team }} · ResikKita</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
    <style>
        .workflow-shell {
            max-width: 920px;
            margin: 0 auto;
            padding: 24px 16px 80px;
        }
        .workflow-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 20px 24px;
            margin-bottom: 24px;
        }
        .toilet-picker-card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 20px 24px;
            margin-bottom: 24px;
        }
        .toilet-dropdown-select {
            display: block;
            width: 100%;
            height: 50px !important;
            min-height: 50px !important;
            padding: 0 46px 0 16px !important;
            font-family: inherit;
            font-size: 14.5px !important;
            font-weight: 600;
            line-height: 48px !important;
            color: var(--text-primary);
            background-color: var(--surface);
            border: 1.5px solid var(--line);
            border-radius: 8px;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%231e5a44' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 16px 16px;
            box-sizing: border-box;
            transition: all .15s ease;
        }
        .toilet-dropdown-select:hover {
            border-color: var(--accent);
        }
        .toilet-dropdown-select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-subtle);
        }
        .students-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .student-item-card {
            background: var(--surface);
            border: 1.5px solid var(--line);
            border-radius: 10px;
            padding: 22px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 16px;
            transition: all .15s ease;
        }
        .student-item-card.is-done {
            background: #fafbfc;
            border-color: var(--line);
        }
        .student-item-card:hover {
            border-color: var(--accent);
        }
        .student-info-name {
            font-family: 'Manrope', sans-serif;
            font-size: 17px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 4px 0 2px;
        }
        .report-summary-box {
            padding: 14px 16px;
            border-radius: 8px;
            background: var(--canvas);
            border: 1px solid var(--line);
            font-size: 13px;
        }
        @media (max-width: 680px) {
            .students-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>
</head>
<body style="background: var(--canvas)">
    <header class="petugas-topbar">
        <a class="brand" href="{{ route('petugas.dashboard') }}">
            <img src="{{ asset('assets/LogoSekolah.png') }}" alt="Logo SMK Wikrama Bogor">
            <span class="brand-copy"><strong>ResikKita</strong><small>Portal Petugas Piket Siswa</small></span>
        </a>
        <div style="display:flex; align-items:center; gap:10px">
            <span class="badge badge-blue"><i data-lucide="shield"></i>{{ $regu ? $regu->nama_regu : 'Regu ' . $team }}</span>
            @if ($regu && $regu->leader)
                <span class="badge" style="background:var(--surface); border:1px solid var(--line); color:var(--text-secondary)">
                    <i data-lucide="user-check"></i>Ketua: {{ $regu->leader->nama_lengkap }}
                </span>
            @endif
            <form method="post" action="{{ route('petugas.logout') }}">
                @csrf
                <button class="button button-secondary button-small" type="submit">
                    <i data-lucide="log-out"></i>Keluar
                </button>
            </form>
        </div>
    </header>

    <main class="workflow-shell">
        @if (session('success'))
            <div class="alert alert-success"><i data-lucide="check-circle-2"></i><span>{{ session('success') }}</span></div>
        @endif
        @if (session('error'))
            <div class="alert alert-error"><i data-lucide="alert-circle"></i><span>{{ session('error') }}</span></div>
        @endif

        <!-- Header Wilayah Tugas & Progres -->
        <div class="workflow-header">
            <div>
                <p class="eyebrow">{{ $day }} · Minggu Ke-{{ $week }} · {{ $regu ? $regu->nama_regu : 'Regu ' . $team }}</p>
                <h1 style="margin:2px 0 0; font-family:'Manrope',sans-serif; font-size:20px; font-weight:800; color:var(--text-primary)">
                    Wilayah Tugas: {{ $buildingName }}
                </h1>
                @if ($regu && $regu->leader)
                    <p style="margin:4px 0 0; color:var(--text-muted); font-size:13px">
                        Koordinator / Ketua Regu: <strong>{{ $regu->leader->nama_lengkap }}</strong> ({{ $regu->leader->rombel }}) · {{ $totalToilets }} lokasi toilet.
                    </p>
                @else
                    <p style="margin:4px 0 0; color:var(--text-muted); font-size:13px">
                        Regu {{ $team }} bertugas di {{ $totalToilets }} lokasi toilet pada gedung ini hari ini.
                    </p>
                @endif
            </div>
            <div style="text-align:right">
                <span style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.04em">Status Regu</span>
                <div style="font-family:'Manrope',sans-serif; font-size:18px; font-weight:800; color:var(--accent)">
                    {{ $completedToilets }} / {{ $totalToilets }} Toilet Selesai
                </div>
            </div>
        </div>

        <!-- Langkah 1: Dropdown Pemilihan Toilet -->
        <div class="toilet-picker-card">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px">
                <label for="toilet-dropdown" style="font-size:12px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.04em; display:flex; align-items:center; gap:6px">
                    <i data-lucide="bath"></i>
                    1. Lokasi Toilet di {{ $buildingName }}
                </label>
                <span style="font-size:12px; color:var(--text-muted)">
                    Sedang melihat: <strong>{{ $activeSchedule->toilet->nama_toilet }}</strong>
                </span>
            </div>
            <div style="margin-bottom:0">
                <select id="toilet-dropdown" class="toilet-dropdown-select" onchange="window.location.href='{{ route('petugas.dashboard') }}?schedule_id=' + this.value">
                    @foreach ($toiletOptions as $opt)
                        <option value="{{ $opt['id'] }}" {{ $opt['id'] === $activeSchedule->id ? 'selected' : '' }}>
                            {{ $opt['toilet_name'] }} ({{ $opt['floor'] }}) — [{{ $opt['is_complete'] ? 'Lengkap Selesai' : $opt['reported_count'] . '/' . $opt['students_count'] . ' Selesai' }}]
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Langkah 2: Dua Nama Siswa Piket di Toilet Ini -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px">
            <div style="font-size:12px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.04em; display:flex; align-items:center; gap:6px">
                <i data-lucide="users"></i>
                2. Petugas Piket (1 Toilet = 2 Siswa)
            </div>
            <span style="font-size:12.5px; color:var(--text-muted)">
                Lokasi: <strong>{{ $activeSchedule->toilet->nama_toilet }} ({{ $activeSchedule->toilet->lantai }})</strong>
            </span>
        </div>

        <div class="students-grid">
            @foreach ($studentList as $idx => $s)
                <div class="student-item-card {{ $s['has_reported'] ? 'is-done' : '' }}">
                    <div>
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px">
                            <span style="font-size:11px; font-weight:800; color:var(--accent); text-transform:uppercase; letter-spacing:0.04em">
                                PETUGAS #{{ $idx + 1 }}
                            </span>
                            @if ($s['has_reported'])
                                @if ($s['report']['status_kehadiran'] === 'Hadir')
                                    <span class="badge badge-success">Hadir</span>
                                @else
                                    <span class="badge badge-danger">Tidak Hadir</span>
                                @endif
                            @else
                                <span class="badge badge-warn">Menunggu Input</span>
                            @endif
                        </div>

                        <div class="student-info-name">{{ $s['name'] }}</div>
                        <div style="color:var(--text-muted); font-size:12.5px">NIS: {{ $s['nis'] }} · {{ $s['rombel'] }}</div>
                    </div>

                    @if ($s['has_reported'])
                        <div class="report-summary-box">
                            <div style="display:flex; justify-content:space-between; margin-bottom:4px">
                                @if ($s['report']['status_kehadiran'] === 'Tidak Hadir')
                                    <span style="color:var(--status-danger); font-weight:600; display:flex; align-items:center; gap:6px">
                                        <i data-lucide="user-x" style="width:14px; height:14px"></i>
                                        Siswa Tidak Masuk (Tidak Bertugas)
                                    </span>
                                @else
                                    <span>Kondisi Toilet: <strong>{{ $s['report']['status_kebersihan'] }}</strong></span>
                                @endif
                                <span style="color:var(--text-muted); font-size:11.5px">{{ $s['report']['waktu_kirim'] }} WIB</span>
                            </div>
                            @if ($s['report']['catatan'])
                                <div style="color:var(--text-secondary); margin-top:4px">
                                    <em>"{{ $s['report']['catatan'] }}"</em>
                                </div>
                            @endif
                            <div style="display:flex; align-items:center; gap:4px; margin-top:8px; font-size:11.5px; color:var(--text-muted)">
                                <i data-lucide="lock" style="width:12px; height:12px"></i>Laporan terkunci
                            </div>
                        </div>
                    @else
                        <div>
                            <a href="{{ route('petugas.inspect', ['schedule_id' => $activeSchedule->id, 'student_id' => $s['id']]) }}" class="button button-primary" style="width:100%; justify-content:center; padding:12px 16px; font-size:13.5px">
                                Periksa & Isi Laporan Siswa Ini →
                            </a>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if (collect($studentList)->every('has_reported', true))
            <div class="notice" style="background:var(--surface); border:1px solid var(--line); color:var(--text-secondary)">
                <i data-lucide="check-circle-2"></i>
                <span>
                    <strong>Pemeriksaan selesai (2/2 Siswa).</strong>
                    Semua laporan untuk {{ $activeSchedule->toilet->nama_toilet }} sudah tersimpan. Silakan pilih lokasi toilet berikutnya melalui dropdown di atas.
                </span>
            </div>
        @endif
    </main>

    <script>
        if (window.lucide) window.lucide.createIcons();
    </script>
</body>
</html>
