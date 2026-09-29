@extends('layouts.app')

@section('title', 'Edit Gedung')
@section('page-title', 'Edit Data Gedung')

@section('content')
<div class="edit-wrap">
    <div class="page-heading"><div><p class="eyebrow">MASTER GEDUNG</p><h1>Edit gedung</h1><p>Perubahan disimpan ke database sekolah.</p></div></div>
    <section class="surface">
        <form class="form-stack" method="post" action="{{ route('buildings.update', $building['id']) }}">
            @csrf @method('PUT')
            <label class="field"><span>Nama gedung</span><input name="name" value="{{ old('name', $building['name']) }}" required maxlength="80"></label>
            <label class="field"><span>Deskripsi</span><textarea name="description" maxlength="180">{{ old('description', $building['description']) }}</textarea></label>
            <div class="edit-actions"><a class="button button-secondary" href="{{ route('buildings.index') }}">Batal</a><button class="button button-primary" type="submit"><i data-lucide="save"></i>Simpan perubahan</button></div>
        </form>
    </section>
</div>
@endsection