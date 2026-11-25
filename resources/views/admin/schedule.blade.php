@extends('layouts.app')

@section('title', 'Jadwal Piket')
@section('page-title', 'Pengaturan Jadwal Piket')

@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">PENUGASAN SISWA</p>
        <h1>Jadwal Piket</h1>
        <p>Atur jadwal siklus 4 mingguan dan pembagian regu piket toilet.</p>
    </div>
    <a class="button button-secondary" href="{{ route('schedule.template') }}">
        <i data-lucide="download"></i>Template Excel (.xlsx)
    </a>
</div>

<section class="content-grid" style="margin-bottom:20px">
    <div class="surface surface-pad">
        <div class="surface-heading">
            <div>
                <h2>Tambah Jadwal Manual</h2>
                <p>Tugaskan satu regu ke toilet tertentu.</p>
            </div>
        </div>
        <form class="form-grid" method="post" action="{{ route('schedule.store') }}">
            @csrf
            <label class="field">
                <span>Hari Piket</span>
                <select name="day" required>
                    @foreach ($allowedDays as $day)
                        <option value="{{ $day }}">{{ $day }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field">
                <span>Minggu Ke (1–4)</span>
                <select name="week" required>
                    @foreach (range(1, 4) as $w)
                        <option value="{{ $w }}">Minggu ke-{{ $w }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field">
                <span>Regu Piket</span>
                <select name="team" required>
                    @foreach (range(1, 4) as $t)
                        <option value="{{ $t }}">Regu {{ $t }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field">
                <span>Gedung</span>
                <select name="building" required id="schedule-building-select">
                    @foreach ($buildings as $b)
                        <option value="{{ $b['name'] }}">{{ $b['name'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field" style="grid-column: 1 / -1">
                <span>Ruangan Toilet</span>
                <input name="room" placeholder="Contoh: Toilet Siswa Putra Lt. 1" required maxlength="100">
            </label>
            <label class="field" style="grid-column: 1 / -1">
                <span>Nama Siswa (Pisahkan titik koma ;)</span>
                <input name="students" placeholder="Contoh: Ahmad Fauzan Pratama; Alifia Zahra Khairunnisa" required maxlength="500">
            </label>
            <label class="field" style="grid-column: 1 / -1">
                <span>Rombel Siswa</span>
                <input name="class" placeholder="Contoh: XII PPLG 1" required maxlength="40">
            </label>
            <div class="form-actions">
                <button class="button button-primary" type="submit">
                    <i data-lucide="plus"></i>Simpan Jadwal
                </button>
            </div>
        </form>
    </div>

    <div class="surface surface-pad">
        <div class="surface-heading">
            <div>
                <h2>Upload Jadwal Semester</h2>
                <p>Siklus rotasi 4 mingguan via Excel / CSV.</p>
            </div>
            <i data-lucide="file-spreadsheet"></i>
        </div>
        <div class="notice">
            <i data-lucide="info"></i>
            <span>Gedung, toilet, dan siswa harus sudah terdaftar sebelum mengimpor.</span>
        </div>
        <form class="form-stack" method="post" action="{{ route('schedule.import') }}" enctype="multipart/form-data" data-confirm="Jadwal aktif lama akan diarsipkan dan diganti dengan jadwal baru. Lanjutkan?">
            @csrf
            <label class="file-dropzone" for="schedule-file">
                <input class="file-input-hidden" id="schedule-file" name="schedule_file" type="file" accept=".xlsx,.xls,.csv" required>
                <i data-lucide="upload-cloud"></i>
                <strong id="schedule-file-label">Pilih file Excel / CSV jadwal</strong>
                <span>Format XLSX, XLS, atau CSV · Maksimal 5 MB</span>
            </label>
            <button class="button button-secondary" type="submit">
                <i data-lucide="calendar-check"></i>Aktifkan Jadwal Semester
            </button>
        </form>
    </div>
</section>

<section class="surface surface-pad">
    <div class="surface-heading">
        <div>
            <h2>Siklus Jadwal Aktif (Perorangan)</h2>
            <p>{{ count($schedules) }} penugasan siswa terdaftar pada siklus ini.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table js-datatable">
            <thead>
                <tr>
                    <th>Hari / Minggu</th>
                    <th>Regu</th>
                    <th>Lokasi Toilet</th>
                    <th>Siswa Petugas</th>
                    <th>Status Piket</th>
                    <th>Kehadiran</th>
                    <th style="width:145px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($schedules as $schedule)
                    <tr>
                        <td>
                            <strong>{{ $schedule['day'] }}</strong>
                            <small>Minggu ke-{{ $schedule['week'] }}</small>
                        </td>
                        <td>
                            <span class="badge badge-blue">Regu {{ $schedule['team'] }}</span>
                        </td>
                        <td>
                            <strong>{{ $schedule['building'] }}</strong>
                            <small>{{ $schedule['room'] }}</small>
                        </td>
                        <td>
                            <strong>{{ $schedule['student_name'] }}</strong>
                            @if (!empty($schedule['nis']))
                                <small style="color:var(--text-muted)">NIS: {{ $schedule['nis'] }} @if(!empty($schedule['class'])) · {{ $schedule['class'] }} @endif</small>
                            @else
                                <small style="color:var(--text-muted)">{{ $schedule['class'] }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $schedule['status'] === 'Selesai' ? 'badge-blue' : 'badge-warn' }}">
                                {{ $schedule['status'] }}
                            </span>
                        </td>
                        <td>
                            @if ($schedule['attendance'] === 'Hadir')
                                <span class="badge badge-success">Hadir</span>
                            @elseif (in_array($schedule['attendance'], ['Izin', 'Sakit']))
                                <span class="badge badge-warn">{{ $schedule['attendance'] }}</span>
                            @elseif (in_array($schedule['attendance'], ['Tidak hadir', 'Tidak Hadir']))
                                <span class="badge badge-danger">Tidak hadir</span>
                            @else
                                <span class="badge">{{ $schedule['attendance'] }}</span>
                            @endif
                        </td>
                        <td>
                            <form class="row-form" method="post" action="{{ route('schedule.update', $schedule['id']) }}">
                                @csrf @method('PATCH')
                                @if (!empty($schedule['student_id']))
                                    <input type="hidden" name="student_id" value="{{ $schedule['student_id'] }}">
                                @endif
                                <select name="status" aria-label="Status piket" title="Status Pemeriksaan">
                                    <option value="Belum diperiksa" @selected($schedule['status'] === 'Belum diperiksa')>Belum diperiksa</option>
                                    <option value="Selesai" @selected($schedule['status'] === 'Selesai')>Selesai</option>
                                </select>
                                <select name="attendance" aria-label="Kehadiran" title="Presensi Siswa">
                                    <option value="Hadir" @selected($schedule['attendance'] === 'Hadir')>Hadir</option>
                                    <option value="Izin" @selected($schedule['attendance'] === 'Izin')>Izin</option>
                                    <option value="Sakit" @selected($schedule['attendance'] === 'Sakit')>Sakit</option>
                                    <option value="Tidak hadir" @selected(in_array($schedule['attendance'], ['Tidak hadir', 'Tidak Hadir']))>Tidak hadir</option>
                                </select>
                                <button class="row-link" type="submit" aria-label="Simpan status" title="Simpan">
                                    <i data-lucide="check"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"><div class="empty-state">Belum ada jadwal piket aktif. Upload jadwal semester di atas.</div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<script>
    const scFile = document.getElementById('schedule-file');
    const scLabel = document.getElementById('schedule-file-label');
    scFile?.addEventListener('change', () => {
        scLabel.textContent = scFile.files[0]?.name ?? 'Pilih file Excel / CSV jadwal piket';
    });
</script>
@endsection
