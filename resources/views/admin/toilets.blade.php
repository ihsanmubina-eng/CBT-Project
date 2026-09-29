@extends('layouts.app')

@section('title', 'Master Toilet')
@section('page-title', 'Manajemen Master Toilet')

@section('content')
<div class="page-heading"><div><p class="eyebrow">DATA FASILITAS</p><h1>Master toilet</h1><p>Kelola lokasi, kapasitas bilik, dan status toilet sekolah.</p></div></div>
@php
    $activeCount = collect($toilets)->where('status', 'Aktif')->count();
    $cubicleCount = collect($toilets)->sum('cubicles');
@endphp
<section class="metric-grid" aria-label="Statistik toilet">
    <div class="metric"><div><div class="metric-label">Total lokasi toilet</div><div class="metric-value">{{ count($toilets) }}</div></div><div class="metric-icon"><i data-lucide="toilet"></i></div></div>
    <div class="metric"><div><div class="metric-label">Toilet aktif</div><div class="metric-value">{{ $activeCount }}</div></div><div class="metric-icon"><i data-lucide="circle-check"></i></div></div>
    <div class="metric"><div><div class="metric-label">Perlu perbaikan</div><div class="metric-value">{{ count($toilets) - $activeCount }}</div></div><div class="metric-icon"><i data-lucide="wrench"></i></div></div>
    <div class="metric"><div><div class="metric-label">Jumlah bilik</div><div class="metric-value">{{ $cubicleCount }}</div></div><div class="metric-icon"><i data-lucide="door-open"></i></div></div>
</section>
<section class="surface surface-pad" style="margin-bottom:16px">
    <div class="surface-heading"><div><h2>Tambah lokasi toilet</h2><p>Pilih gedung yang sudah tersedia di master gedung.</p></div></div>
    <form class="form-grid" method="post" action="{{ route('toilets.store') }}">
        @csrf
        <label class="field"><span>Nama toilet</span><input name="name" placeholder="Contoh: Toilet Putra" required maxlength="80"></label>
        <label class="field"><span>Nama gedung</span><select name="building" required>@foreach ($buildings as $building)<option value="{{ $building['name'] }}">{{ $building['name'] }}</option>@endforeach</select></label>
        <label class="field"><span>Lantai</span><input name="floor" placeholder="Lantai 1" required maxlength="30"></label>
        <label class="field"><span>Jenis toilet</span><select name="type" required><option>Pria</option><option>Wanita</option><option>Bersama</option></select></label>
        <label class="field"><span>Jumlah bilik</span><input name="cubicles" type="number" min="1" max="100" value="3" required></label>
        <label class="field"><span>Status</span><select name="status"><option>Aktif</option><option>Tidak aktif</option></select></label>
        <div class="form-actions"><button class="button button-primary" type="submit"><i data-lucide="plus"></i>Tambah toilet</button></div>
    </form>
</section>
<section class="surface surface-pad">
    <div class="toolbar">
        <div><h2 class="section-title">Lokasi terdaftar</h2><p class="section-subtitle">{{ count($toilets) }} lokasi toilet.</p></div>
    </div>
    <div class="table-wrap"><table class="data-table js-datatable"><thead><tr><th>Nama toilet</th><th>Lantai</th><th>Jenis</th><th>Jumlah bilik</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        @forelse ($toilets as $toilet)
            <tr><td><strong>{{ $toilet['name'] }}</strong><small>{{ $toilet['building'] }}</small></td><td>{{ $toilet['floor'] }}</td><td>{{ $toilet['type'] }}</td><td>{{ $toilet['cubicles'] }} bilik</td><td><span class="badge {{ $toilet['status'] === 'Aktif' ? '' : 'badge-danger' }}">{{ $toilet['status'] }}</span></td><td><div class="table-actions"><button class="row-link" type="button" data-open-dialog="edit-toilet-{{ $toilet['id'] }}" data-dialog-key="toilet-{{ $toilet['id'] }}" aria-label="Edit toilet {{ $toilet['name'] }}" aria-haspopup="dialog"><i data-lucide="pencil"></i></button><form method="post" action="{{ route('toilets.delete', $toilet['id']) }}" data-confirm="Hapus lokasi toilet {{ $toilet['name'] }} · {{ $toilet['floor'] }}?">@csrf @method('DELETE')<button class="row-link" type="submit" aria-label="Hapus toilet"><i data-lucide="trash-2"></i></button></form></div></td></tr>
        @empty
            <tr><td colspan="6"><div class="empty-state">Belum ada lokasi toilet.</div></td></tr>
        @endforelse
    </tbody></table></div>
    @foreach ($toilets as $toilet)
        <dialog class="edit-dialog" id="edit-toilet-{{ $toilet['id'] }}" data-edit-dialog data-dialog-key="toilet-{{ $toilet['id'] }}" aria-labelledby="toilet-dialog-title-{{ $toilet['id'] }}">
            <div class="dialog-head"><div><p class="eyebrow">MASTER TOILET</p><h2 id="toilet-dialog-title-{{ $toilet['id'] }}">Edit lokasi toilet</h2></div><button class="icon-button" type="button" data-dialog-close aria-label="Tutup dialog"><i data-lucide="x"></i></button></div>
            <form class="dialog-form" method="post" action="{{ route('toilets.update', $toilet['id']) }}">
                @csrf @method('PUT')
                <div class="dialog-body">
                    <label class="field"><span>Nama toilet</span><input name="name" value="{{ old('name', $toilet['name']) }}" required maxlength="80"></label>
                    <label class="field"><span>Nama gedung</span><select name="building" required>@foreach ($buildings as $building)<option value="{{ $building['name'] }}" @selected(old('building', $toilet['building']) === $building['name'])>{{ $building['name'] }}</option>@endforeach</select></label>
                    <label class="field"><span>Lantai</span><input name="floor" value="{{ old('floor', $toilet['floor']) }}" required maxlength="30"></label>
                    <label class="field"><span>Jenis toilet</span><select name="type" required>@foreach (['Pria','Wanita','Bersama'] as $type)<option @selected(old('type', $toilet['type']) === $type)>{{ $type }}</option>@endforeach</select></label>
                    <label class="field"><span>Jumlah bilik</span><input name="cubicles" type="number" min="1" max="100" value="{{ old('cubicles', $toilet['cubicles']) }}" required></label>
                    <label class="field"><span>Status</span><select name="status"><option @selected(old('status', $toilet['status']) === 'Aktif')>Aktif</option><option @selected(old('status', $toilet['status']) === 'Tidak aktif')>Tidak aktif</option></select></label>
                </div>
                <div class="dialog-footer"><button class="button button-secondary" type="button" data-dialog-close>Batal</button><button class="button button-primary" type="submit"><i data-lucide="save"></i>Simpan perubahan</button></div>
            </form>
        </dialog>
    @endforeach
</section>
@endsection
