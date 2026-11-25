@extends('layouts.app')

@section('title', 'Edit Master Regu')
@section('page-title', 'Edit Master Regu Piket')

@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">PENGORGANISASIAN PIKET</p>
        <h1>Edit {{ $team->nama_regu }}</h1>
        <p>Ubah gedung penugasan atau ketua regu untuk regu ini.</p>
    </div>
    <a class="button button-secondary" href="{{ route('teams.index') }}">
        <i data-lucide="arrow-left"></i>Kembali ke Daftar Regu
    </a>
</div>

<div class="surface surface-pad" style="max-width: 680px">
    <div class="surface-heading">
        <div>
            <h2>Perbarui Data Regu</h2>
            <p>Pastikan penugasan gedung sudah sesuai dengan area toilet.</p>
        </div>
        <i data-lucide="shield-alert"></i>
    </div>

    <form class="form-stack" method="post" action="{{ route('teams.update', $team->id) }}">
        @csrf
        @method('put')

        <label class="field">
            <span>Nama Regu</span>
            <input name="nama_regu" value="{{ old('nama_regu', $team->nama_regu) }}" required maxlength="100">
        </label>

        <label class="field">
            <span>Gedung Tanggung Jawab</span>
            <select name="gedung_id" required>
                @foreach ($buildings as $b)
                    <option value="{{ $b->id }}" {{ old('gedung_id', $team->gedung_id) == $b->id ? 'selected' : '' }}>
                        {{ $b->nama_gedung }}
                    </option>
                @endforeach
            </select>
        </label>

        <label class="field">
            <span>Ketua Regu (Siswa)</span>
            <select name="ketua_siswa_id">
                <option value="">-- Tidak Ada Ketua --</option>
                @foreach ($students as $s)
                    <option value="{{ $s->id }}" {{ old('ketua_siswa_id', $team->ketua_siswa_id) == $s->id ? 'selected' : '' }}>
                        {{ $s->nama_lengkap }} ({{ $s->rombel }})
                    </option>
                @endforeach
            </select>
        </label>

        <label class="field">
            <span>Keterangan / Deskripsi Tugas</span>
            <input name="keterangan" value="{{ old('keterangan', $team->keterangan) }}" maxlength="255">
        </label>

        <div style="display:flex; gap:10px; margin-top:8px">
            <button class="button button-primary" type="submit">
                <i data-lucide="save"></i>Simpan Perubahan
            </button>
            <a href="{{ route('teams.index') }}" class="button button-secondary">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
