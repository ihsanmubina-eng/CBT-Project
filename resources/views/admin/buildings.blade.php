@extends('layouts.app')

@section('title', 'Master Gedung')
@section('page-title', 'Manajemen Master Data Gedung')

@section('content')
<div class="page-heading"><div><p class="eyebrow">DATA FASILITAS</p><h1>Master gedung</h1><p>Kelompokkan toilet berdasarkan gedung sekolah.</p></div></div>
<section class="surface surface-pad" style="margin-bottom:16px">
    <div class="surface-heading"><div><h2>Tambah gedung</h2><p>Data ini akan tersedia di pilihan lokasi toilet.</p></div></div>
    <form class="form-grid" method="post" action="{{ route('buildings.store') }}">
        @csrf
        <label class="field"><span>Nama gedung</span><input name="name" value="{{ old('name') }}" placeholder="Contoh: Gedung D" required maxlength="80"></label>
        <label class="field"><span>Deskripsi / keterangan</span><input name="description" value="{{ old('description') }}" placeholder="Area kelas dan laboratorium" maxlength="180"></label>
        <div class="form-actions"><button class="button button-primary" type="submit"><i data-lucide="plus"></i>Simpan gedung</button></div>
    </form>
</section>
<section class="surface surface-pad">
    <div class="toolbar">
        <div><h2 class="section-title">Gedung terdaftar</h2><p class="section-subtitle">{{ count($buildings) }} lokasi gedung.</p></div>
    </div>
    <div class="table-wrap"><table class="data-table js-datatable"><thead><tr><th>No</th><th>Nama gedung</th><th>Deskripsi</th><th>Lokasi toilet</th><th>Aksi</th></tr></thead><tbody>
        @forelse ($buildings as $index => $building)
            @php($toiletCount = collect($toilets)->where('building', $building['name'])->count())
            <tr><td>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</td><td><strong>{{ $building['name'] }}</strong></td><td>{{ $building['description'] ?: 'Belum ada keterangan' }}</td><td>{{ $toiletCount }} toilet</td><td><div class="table-actions"><button class="row-link" type="button" data-open-dialog="edit-building-{{ $building['id'] }}" data-dialog-key="building-{{ $building['id'] }}" aria-label="Edit gedung {{ $building['name'] }}" aria-haspopup="dialog"><i data-lucide="pencil"></i></button><form method="post" action="{{ route('buildings.delete', $building['id']) }}" data-confirm="Hapus {{ $building['name'] }}? Data toilet pada gedung ini perlu dipindahkan lebih dulu.">@csrf @method('DELETE')<button class="row-link" type="submit" aria-label="Hapus gedung"><i data-lucide="trash-2"></i></button></form></div></td></tr>
        @empty
            <tr><td colspan="5"><div class="empty-state">Belum ada gedung terdaftar.</div></td></tr>
        @endforelse
    </tbody></table></div>
    @foreach ($buildings as $building)
        <dialog class="edit-dialog" id="edit-building-{{ $building['id'] }}" data-edit-dialog data-dialog-key="building-{{ $building['id'] }}" aria-labelledby="building-dialog-title-{{ $building['id'] }}">
            <div class="dialog-head"><div><p class="eyebrow">MASTER GEDUNG</p><h2 id="building-dialog-title-{{ $building['id'] }}">Edit data gedung</h2></div><button class="icon-button" type="button" data-dialog-close aria-label="Tutup dialog"><i data-lucide="x"></i></button></div>
            <form class="dialog-form" method="post" action="{{ route('buildings.update', $building['id']) }}">
                @csrf @method('PUT')
                <div class="dialog-body"><label class="field"><span>Nama gedung</span><input name="name" value="{{ old('name', $building['name']) }}" required maxlength="80"></label><label class="field"><span>Deskripsi / keterangan</span><input name="description" value="{{ old('description', $building['description']) }}" maxlength="180"></label></div>
                <div class="dialog-footer"><button class="button button-secondary" type="button" data-dialog-close>Batal</button><button class="button button-primary" type="submit"><i data-lucide="save"></i>Simpan perubahan</button></div>
            </form>
        </dialog>
    @endforeach
</section>
@endsection
