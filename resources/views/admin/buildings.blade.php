@extends('layouts.app')

@section('title', 'Master Gedung')
@section('page-title', 'Manajemen Master Gedung')

@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">DATA FASILITAS</p>
        <h1>Master Gedung</h1>
        <p>Kelola data gedung sekolah sebagai induk lokasi toilet.</p>
    </div>
    <a class="button button-secondary" href="{{ route('buildings.template') }}">
        <i data-lucide="download"></i>Template Excel (.xlsx)
    </a>
</div>

<section class="content-grid" style="margin-bottom:20px">
    <div class="surface surface-pad">
        <div class="surface-heading">
            <div>
                <h2>Tambah Gedung</h2>
                <p>Tambah satu gedung baru secara manual.</p>
            </div>
        </div>
        <form class="form-stack" method="post" action="{{ route('buildings.store') }}">
            @csrf
            <label class="field">
                <span>Nama Gedung</span>
                <input name="name" value="{{ old('name') }}" placeholder="Contoh: Gedung Graha Widya" required maxlength="80">
            </label>
            <label class="field">
                <span>Deskripsi / Keterangan</span>
                <input name="description" value="{{ old('description') }}" placeholder="Area pembelajaran dan lab" maxlength="180">
            </label>
            <button class="button button-primary" type="submit">
                <i data-lucide="plus"></i>Simpan Gedung
            </button>
        </form>
    </div>

    <div class="surface surface-pad">
        <div class="surface-heading">
            <div>
                <h2>Impor Gedung (Excel / CSV)</h2>
                <p>Kolom wajib: <code>nama_gedung, deskripsi</code>.</p>
            </div>
            <i data-lucide="file-spreadsheet"></i>
        </div>
        <form class="form-stack" method="post" action="{{ route('buildings.import') }}" enctype="multipart/form-data">
            @csrf
            <label class="file-dropzone" for="building-file">
                <input class="file-input-hidden" id="building-file" name="building_file" type="file" accept=".xlsx,.xls,.csv" required>
                <i data-lucide="upload-cloud"></i>
                <strong id="building-file-label">Pilih file Excel / CSV gedung</strong>
                <span>Format XLSX, XLS, atau CSV · Maksimal 5 MB</span>
            </label>
            <button class="button button-secondary" type="submit">
                <i data-lucide="upload"></i>Impor Data Gedung
            </button>
        </form>
    </div>
</section>

<section class="surface surface-pad">
    <div class="surface-heading">
        <div>
            <h2>Daftar Gedung Terdaftar</h2>
            <p>{{ count($buildings) }} gedung tercatat dalam sistem.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table js-datatable">
            <thead>
                <tr>
                    <th style="width:40px">No</th>
                    <th>Nama Gedung</th>
                    <th>Deskripsi</th>
                    <th>Jumlah Toilet</th>
                    <th style="width:100px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($buildings as $index => $building)
                    @php($toiletCount = collect($toilets)->where('building', $building['name'])->count())
                    <tr>
                        <td>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</td>
                        <td><strong>{{ $building['name'] }}</strong></td>
                        <td>{{ $building['description'] ?: 'Belum ada keterangan' }}</td>
                        <td>{{ $toiletCount }} toilet</td>
                        <td>
                            <div class="table-actions">
                                <button class="row-link" type="button" data-open-dialog="edit-building-{{ $building['id'] }}" data-dialog-key="building-{{ $building['id'] }}" aria-label="Edit {{ $building['name'] }}">
                                    <i data-lucide="pencil"></i>
                                </button>
                                <form method="post" action="{{ route('buildings.delete', $building['id']) }}" data-confirm="Hapus {{ $building['name'] }}? Pastikan toilet terkait sudah dipindahkan.">
                                    @csrf @method('DELETE')
                                    <button class="row-link" type="submit" aria-label="Hapus gedung">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5"><div class="empty-state">Belum ada data gedung. Tambahkan gedung baru di atas.</div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @foreach ($buildings as $building)
        <dialog class="edit-dialog" id="edit-building-{{ $building['id'] }}" data-edit-dialog data-dialog-key="building-{{ $building['id'] }}">
            <div class="dialog-head">
                <div>
                    <p class="eyebrow">MASTER GEDUNG</p>
                    <h2>Edit Data Gedung</h2>
                </div>
                <button class="icon-button" type="button" data-dialog-close aria-label="Tutup"><i data-lucide="x"></i></button>
            </div>
            <form class="dialog-form" method="post" action="{{ route('buildings.update', $building['id']) }}">
                @csrf @method('PUT')
                <div class="dialog-body">
                    <label class="field">
                        <span>Nama Gedung</span>
                        <input name="name" value="{{ old('name', $building['name']) }}" required maxlength="80">
                    </label>
                    <label class="field">
                        <span>Deskripsi / Keterangan</span>
                        <input name="description" value="{{ old('description', $building['description']) }}" maxlength="180">
                    </label>
                </div>
                <div class="dialog-footer">
                    <button class="button button-secondary" type="button" data-dialog-close>Batal</button>
                    <button class="button button-primary" type="submit"><i data-lucide="check"></i>Simpan Perubahan</button>
                </div>
            </form>
        </dialog>
    @endforeach
</section>

<script>
    const bFile = document.getElementById('building-file');
    const bLabel = document.getElementById('building-file-label');
    bFile?.addEventListener('change', () => {
        bLabel.textContent = bFile.files[0]?.name ?? 'Pilih file Excel / CSV gedung';
    });
</script>
@endsection
