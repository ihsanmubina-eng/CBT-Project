@extends('layouts.app')

@section('title', 'Edit Toilet')
@section('page-title', 'Edit Data Toilet')

@section('content')
<div class="edit-wrap">
    <div class="page-heading"><div><p class="eyebrow">MASTER TOILET</p><h1>Edit lokasi toilet</h1><p>Perbarui fasilitas dan status operasionalnya.</p></div></div>
    <section class="surface">
        <form class="form-grid" method="post" action="{{ route('toilets.update', $toilet['id']) }}">
            @csrf @method('PUT')
            <label class="field"><span>Nama toilet</span><input name="name" value="{{ old('name', $toilet['name']) }}" required maxlength="80"></label>
            <label class="field"><span>Nama gedung</span><select name="building" required>@foreach ($buildings as $building)<option value="{{ $building['name'] }}" @selected(old('building', $toilet['building']) === $building['name'])>{{ $building['name'] }}</option>@endforeach</select></label>
            <label class="field"><span>Lantai</span><input name="floor" value="{{ old('floor', $toilet['floor']) }}" required maxlength="30"></label>
            <label class="field"><span>Jenis toilet</span><select name="type" required>@foreach (['Pria','Wanita','Bersama'] as $type)<option @selected(old('type', $toilet['type']) === $type)>{{ $type }}</option>@endforeach</select></label>
            <label class="field"><span>Jumlah bilik</span><input name="cubicles" type="number" min="1" max="100" value="{{ old('cubicles', $toilet['cubicles']) }}" required></label>
            <label class="field"><span>Status</span><select name="status"><option @selected(old('status', $toilet['status']) === 'Aktif')>Aktif</option><option @selected(old('status', $toilet['status']) === 'Tidak aktif')>Tidak aktif</option></select></label>
            <div class="edit-actions"><a class="button button-secondary" href="{{ route('toilets.index') }}">Batal</a><button class="button button-primary" type="submit"><i data-lucide="save"></i>Simpan perubahan</button></div>
        </form>
    </section>
</div>
@endsection