<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pemeriksaan: {{ $student->nama_lengkap }} · ResikKita</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
    <style>
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
        }
        .inspect-shell {
            max-width: 720px;
            margin: 0 auto;
            padding: 28px 16px 80px;
        }
        .inspect-top-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .inspect-header-card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .inspect-form-card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 28px;
        }
        .form-section {
            margin-bottom: 24px;
        }
        .form-section-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
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

    <main class="inspect-shell">
        <div class="inspect-top-nav">
            <a href="{{ route('petugas.dashboard', ['schedule_id' => $schedule->id]) }}" class="button button-secondary button-small">
                <i data-lucide="arrow-left"></i>Kembali ke Daftar Siswa & Toilet
            </a>
            <span style="font-size:12px; color:var(--text-muted); font-weight:600">
                Langkah 3 dari 3 · Pengisian Laporan
            </span>
        </div>

        @if (session('error'))
            <div class="alert alert-error"><i data-lucide="alert-circle"></i><span>{{ session('error') }}</span></div>
        @endif

        <!-- Kartu Identitas Siswa & Lokasi -->
        <div class="inspect-header-card">
            <div>
                <p class="eyebrow">Formulir pemeriksaan</p>
                <h1 style="margin:2px 0 6px; font-family:'Manrope',sans-serif; font-size:22px; font-weight:800; color:var(--text-primary)">
                    {{ $student->nama_lengkap }}
                </h1>
                <div style="display:flex; align-items:center; gap:10px; font-size:13px; color:var(--text-secondary); margin-bottom:6px">
                    <span>NIS: <strong>{{ $student->nis }}</strong></span>
                    <span>·</span>
                    <span>Rombel: <strong>{{ $student->rombel }}</strong></span>
                </div>
                <div style="display:flex; align-items:center; gap:6px; font-size:12px; color:var(--text-muted)">
                    <i data-lucide="map-pin" style="width:14px; height:14px"></i>
                    <span>{{ $buildingName }} · <strong>{{ $toilet->nama_toilet }} ({{ $toilet->lantai }})</strong></span>
                </div>
            </div>
            <span class="avatar" style="width:52px; height:52px; font-size:18px">
                {{ substr($student->nama_lengkap, 0, 2) }}
            </span>
        </div>

        <!-- Form Pemeriksaan Luas & Nyaman -->
        <div class="inspect-form-card">
            <form class="form-stack" method="post" action="{{ route('petugas.report') }}">
                @csrf
                <input type="hidden" name="schedule_id" value="{{ $schedule->id }}">
                <input type="hidden" name="student_id" value="{{ $student->id }}">

                <!-- 1. Presensi Kehadiran Siswa (2 Tombol: Hadir / Tidak Hadir) -->
                <div class="form-section">
                    <label class="form-section-title">
                        <i data-lucide="user-check"></i>
                        1. Status Kehadiran Siswa
                    </label>
                    <div class="attendance-choices">
                        <label class="attendance-choice">
                            <input type="radio" name="status_kehadiran" value="Hadir" required checked>
                            <div class="choice-box choice-present">
                                <div class="choice-icon">
                                    <i data-lucide="check"></i>
                                </div>
                                <div>
                                    <strong style="display:block; font-size:14px">Hadir</strong>
                                    <small style="color:var(--text-muted); font-size:12px">Siswa hadir dan menjalankan piket</small>
                                </div>
                            </div>
                        </label>
                        <label class="attendance-choice">
                            <input type="radio" name="status_kehadiran" value="Tidak Hadir" required>
                            <div class="choice-box choice-absent">
                                <div class="choice-icon">
                                    <i data-lucide="x"></i>
                                </div>
                                <div>
                                    <strong style="display:block; font-size:14px">Tidak Hadir</strong>
                                    <small style="color:var(--text-muted); font-size:12px">Siswa absen / tidak ada di lokasi</small>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 2. Status Kebersihan Toilet (Dropdown) -->
                <div class="form-section" id="section_kebersihan">
                    <label class="form-section-title" for="status_kebersihan">
                        <i data-lucide="clipboard-check"></i>
                        <span id="title_kebersihan">2. Kondisi Kebersihan Toilet</span>
                    </label>
                    <div id="wrapper_kebersihan">
                        <select id="status_kebersihan" name="status_kebersihan" class="toilet-dropdown-select" required>
                            <option value="Bersih" selected>Bersih — Toilet bersih, harum, dan layak pakai</option>
                            <option value="Perlu dibersihkan">Perlu dibersihkan — Masih ada kotoran atau genangan air</option>
                            <option value="Rusak">Rusak — Fasilitas rusak (keran patah, pintu macet, dll)</option>
                        </select>
                    </div>
                    <div id="notice_tidak_hadir" style="display:none; margin-top:8px; padding:12px 14px; background:var(--surface-subtle); border:1px solid var(--line); border-left:3px solid var(--status-danger); border-radius:6px; color:var(--text-secondary); font-size:13px; align-items:center; gap:8px">
                        <i data-lucide="info" style="width:16px; height:16px; flex-shrink:0"></i>
                        <span>Siswa <strong>Tidak Hadir</strong> sehingga tidak menjalankan tugas piket di toilet ini. Status kebersihan diset <em>"Tidak Bertugas"</em>.</span>
                    </div>
                </div>

                <!-- 3. Catatan Pemeriksaan (Opsional / Wajib jika Absen) -->
                <div class="form-section">
                    <label class="form-section-title" for="catatan">
                        <i data-lucide="message-square"></i>
                        <span id="label_catatan">3. Catatan Pemeriksaan (Opsional)</span>
                    </label>
                    <div class="field">
                        <textarea id="catatan" name="catatan" placeholder="Contoh: Lantai sudah dipel tapi sabun cuci tangan habis, keran air bilik 2 bocor halus..." maxlength="500" style="min-height:90px; font-size:13.5px"></textarea>
                    </div>
                </div>

                <!-- Tombol Aksi Submit -->
                <div style="border-top:1px solid var(--line); padding-top:20px; margin-top:8px; display:flex; align-items:center; justify-content:space-between; gap:16px">
                    <span style="font-size:12px; color:var(--text-muted); display:flex; align-items:center; gap:6px">
                        <i data-lucide="lock" style="width:14px; height:14px"></i>
                        Laporan langsung mengunci presensi siswa ini.
                    </span>
                    <button class="button button-primary" type="submit" style="padding:12px 24px; font-size:14px">
                        <i data-lucide="send"></i>Kirim & Kunci Laporan
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        if (window.lucide) window.lucide.createIcons();

        const radios = document.querySelectorAll('input[name="status_kehadiran"]');
        const selectKebersihan = document.getElementById('status_kebersihan');
        const wrapperKebersihan = document.getElementById('wrapper_kebersihan');
        const noticeTidakHadir = document.getElementById('notice_tidak_hadir');
        const labelCatatan = document.getElementById('label_catatan');
        const textareaCatatan = document.getElementById('catatan');

        function syncAttendanceState() {
            const isTidakHadir = document.querySelector('input[name="status_kehadiran"]:checked')?.value === 'Tidak Hadir';
            if (isTidakHadir) {
                if (!document.getElementById('opt_tidak_bertugas')) {
                    const opt = document.createElement('option');
                    opt.id = 'opt_tidak_bertugas';
                    opt.value = 'Tidak Bertugas';
                    opt.text = '⛔ Tidak Bertugas (Siswa Tidak Masuk/Absen)';
                    selectKebersihan.appendChild(opt);
                }
                selectKebersihan.value = 'Tidak Bertugas';
                wrapperKebersihan.style.display = 'none';
                noticeTidakHadir.style.display = 'flex';
                labelCatatan.textContent = '3. Alasan Ketidakhadiran (Disarankan / Opsional)';
                textareaCatatan.placeholder = 'Contoh: Sakit demam, izin ada acara keluarga, alpa tanpa keterangan...';
            } else {
                const opt = document.getElementById('opt_tidak_bertugas');
                if (opt) opt.remove();
                if (selectKebersihan.value === 'Tidak Bertugas') {
                    selectKebersihan.value = 'Bersih';
                }
                wrapperKebersihan.style.display = 'block';
                noticeTidakHadir.style.display = 'none';
                labelCatatan.textContent = '3. Catatan Pemeriksaan (Opsional)';
                textareaCatatan.placeholder = 'Contoh: Lantai sudah dipel tapi sabun cuci tangan habis, keran air bilik 2 bocor halus...';
            }
        }

        radios.forEach(r => r.addEventListener('change', syncAttendanceState));
        syncAttendanceState();
    </script>
</body>
</html>
