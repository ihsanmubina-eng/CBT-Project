@extends('layouts.app')

@section('title', 'Edit Arsip Siswa')
@section('page-title', 'Edit Arsip Siswa')

@section('content')
<div class="edit-wrap">
    <div class="page-heading"><div><p class="eyebrow">HISTORI DATA</p><h1>Edit arsip siswa</h1><p>Perbarui identitas serta keterangan mutasi.</p></div></div>
    <section class="surface">
        <form class="form-grid" method="post" action="{{ route('history.update', $record['id']) }}">
            @csrf @method('PUT')
            <label class="field"><span>Nama lengkap</span><input name="name" value="{{ old('name', $record['name']) }}" required maxlength="100"></label>
            <label class="field"><span>NIS</span><input name="nis" value="{{ old('nis', $record['nis']) }}" required maxlength="30"></label>
            <label class="field"><span>Rombel terakhir</span><input name="class" value="{{ old('class', $record['class']) }}" required maxlength="40"></label>
            <label class="field"><span>Tanggal arsip</span><input name="date" type="date" value="{{ old('date', $record['date']) }}" required></label>
            <label class="field"><span>Jenis mutasi</span><select name="type" required>@foreach (['Pindah Sekolah','Mutasi Kelas','Lulus'] as $type)<option @selected(old('type', $record['type']) === $type)>{{ $type }}</option>@endforeach</select></label>
            <label class="field"><span>Keterangan</span><input name="note" value="{{ old('note', $record['note']) }}" maxlength="180"></label>
            <div class="edit-actions"><a class="button button-secondary" href="{{ route('history.index') }}">Batal</a><button class="button button-primary" type="submit"><i data-lucide="save"></i>Simpan perubahan</button></div>
        </form>
    </section>
</div>
@endsection