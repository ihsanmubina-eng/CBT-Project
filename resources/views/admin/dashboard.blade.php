@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Monitoring Utama')

@section('content')
<div class="page-heading">
    <div><p class="eyebrow">RINGKASAN HARI INI</p><h1>Selamat datang kembali</h1><p>Pantau jadwal piket dan kondisi fasilitas sekolah.</p></div>
    <a class="button button-secondary" href="{{ route('public.dashboard') }}"><i data-lucide="external-link"></i>Lihat tampilan publik</a>
</div>

<section class="token-banner" aria-label="Kode akses piket">
    <div><div class="token-label">Kode akses piket siswa</div><div class="token-code">{{ $token ?? 'Belum dibuat' }}</div><div class="token-meta"><i data-lucide="radio"></i>{{ $token ? 'Aktif hari ini sampai 23.59 WIB · Minggu '.$tokenWeek : 'Buat token setelah jadwal hari ini tersedia' }}</div></div>
    <form class="token-create-form" method="post" action="{{ route('token.generate') }}">@csrf<label class="sr-only" for="token-week">Minggu jadwal token</label><select id="token-week" name="week" aria-label="Minggu jadwal token" required>@foreach (range(1, 4) as $week)<option value="{{ $week }}" @selected($week === $tokenWeek)>Minggu {{ $week }}</option>@endforeach</select><button class="button button-light" type="submit"><i data-lucide="key-round"></i>{{ $token ? 'Ganti token' : 'Buat token' }}</button></form>
</section>

@php
    $finished = collect($assignments)->where('status', 'Selesai')->count();
    $activeToilets = collect($toilets)->where('status', 'Aktif')->count();
@endphp
<section class="metric-grid" aria-label="Statistik monitoring">
    <div class="metric"><div><div class="metric-label">Tugas piket hari ini</div><div class="metric-value">{{ count($assignments) }}</div></div><div class="metric-icon"><i data-lucide="list-checks"></i></div></div>
    <div class="metric"><div><div class="metric-label">Sudah diperiksa</div><div class="metric-value">{{ $finished }}</div></div><div class="metric-icon"><i data-lucide="circle-check"></i></div></div>
    <div class="metric"><div><div class="metric-label">Lokasi toilet aktif</div><div class="metric-value">{{ $activeToilets }}</div></div><div class="metric-icon"><i data-lucide="door-open"></i></div></div>
    <div class="metric"><div><div class="metric-label">Gedung terdaftar</div><div class="metric-value">{{ count($buildings) }}</div></div><div class="metric-icon"><i data-lucide="building-2"></i></div></div>
</section>

<section class="surface surface-pad">
    <div class="surface-heading"><div><h2>Jadwal piket terbaru</h2><p>Penugasan yang perlu dipantau hari ini.</p></div><a class="button button-secondary button-small" href="{{ route('schedule.index') }}">Kelola jadwal <i data-lucide="arrow-right"></i></a></div>
    <div class="table-wrap"><table class="data-table">
        <thead><tr><th>Lokasi</th><th>Tim piket</th><th>Rombel</th><th>Status pemeriksaan</th></tr></thead>
        <tbody>
        @forelse (array_slice($assignments, 0, 5) as $assignment)
            <tr><td><strong>{{ $assignment['building'] }} · {{ $assignment['room'] }}</strong><small>{{ $assignment['day'] }} · Minggu {{ $assignment['week'] }}</small></td><td><span class="badge badge-blue">Regu {{ $assignment['team'] }}</span> {{ $assignment['students'] }}</td><td>{{ $assignment['class'] }}</td><td><span class="badge {{ $assignment['status'] === 'Selesai' ? '' : 'badge-warn' }}">{{ $assignment['status'] }}</span></td></tr>
        @empty
            <tr><td colspan="4"><div class="empty-state">Belum ada jadwal piket.</div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
<section class="surface surface-pad" style="margin-top:16px">
    <div class="surface-heading"><div><h2>Laporan petugas</h2><p>Laporan terkunci setelah regu mengirim hasil pemeriksaan.</p></div></div>
    <div class="table-wrap"><table class="data-table"><thead><tr><th>Regu / lokasi</th><th>Status kebersihan</th><th>Catatan</th><th>Status laporan</th><th>Waktu</th></tr></thead><tbody>
        @forelse ($reports as $report)
            <tr><td><strong>Regu {{ $report['team'] }} · {{ $report['building'] }}</strong><small>{{ $report['room'] }}</small></td><td>{{ $report['status_kebersihan'] }}</td><td>{{ $report['catatan'] ?: 'Tanpa catatan' }}</td><td><span class="badge">Terkunci</span></td><td>{{ $report['waktu_kirim'] }}</td></tr>
        @empty
            <tr><td colspan="5"><div class="empty-state">Belum ada laporan yang dikirim.</div></td></tr>
        @endforelse
    </tbody></table></div>
</section>
@endsection
