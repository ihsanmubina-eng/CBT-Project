@extends('layouts.app')

@section('title', 'Master Toilet')
@section('page-title', 'Manajemen Master Toilet')

@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">DATA FASILITAS</p>
        <h1>Master Toilet</h1>
        <p>Kelola data toilet, jenis, kapasitas bilik, dan status operasional.</p>
    </div>
    <a class="button button-secondary" href="{{ route('toilets.template') }}">
        <i data-lucide="download"></i>Template Excel (.xlsx)
    </a>
</div>

@php
    $activeCount = collect($toilets)->where('status', 'Aktif')->count();
    $cubicleCount = collect($toilets)->sum('cubicles');
@endphp
<section class="metric-grid" aria-label="Statistik toilet">
    <div class="metric">
        <div>
            <div class="metric-label">Total Lokasi Toilet</div>
            <div class="metric-value">{{ count($toilets) }}</div>
        </div>
        <div class="metric-icon"><i data-lucide="bath"></i></div>
    </div>
    <div class="metric">
        <div>
            <div class="metric-label">Toilet Aktif</div>
            <div class="metric-value">{{ $activeCount }}</div>
        </div>
        <div class="metric-icon"><i data-lucide="check-circle-2"></i></div>
    </div>
    <div class="metric">
        <div>
            <div class="metric-label">Nonaktif / Perbaikan</div>
            <div class="metric-value">{{ count($toilets) - $activeCount }}</div>
        </div>
        <div class="metric-icon"><i data-lucide="wrench"></i></div>
    </div>
    <div class="metric">
        <div>
            <div class="metric-label">Jumlah Total Bilik</div>
            <div class="metric-value">{{ $cubicleCount }}</div>
        </div>
        <div class="metric-icon"><i data-lucide="door-closed"></i></div>
    </div>
</section>

<section class="content-grid" style="margin-bottom:20px">
    <div class="surface surface-pad">
        <div class="surface-heading">
            <div>
                <h2>Tambah Toilet Manual</h2>
                <p>Pilih gedung yang sudah terdaftar.</p>
            </div>
        </div>
        <form class="form-grid" method="post" action="{{ route('toilets.store') }}">
            @csrf
            <label class="field">
                <span>Nama Toilet</span>
                <input name="name" placeholder="Contoh: Toilet Siswa Putra Lt. 1" required maxlength="80">
            </label>
            <label class="field">
                <span>Nama Gedung</span>
                <select name="building" required>
                    @foreach ($buildings as $b)
                        <option value="{{ $b['name'] }}">{{ $b['name'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field">
                <span>Lantai</span>
                <input name="floor" placeholder="Lantai 1" required maxlength="30">
            </label>
            <label class="field">
                <span>Jenis Toilet</span>
                <select name="type" required>
                    <option>Pria</option>
                    <option>Wanita</option>
                    <option>Bersama</option>
                </select>
            </label>
            <label class="field">
                <span>Jumlah Bilik</span>
                <input name="cubicles" type="number" min="1" max="100" value="3" required>
            </label>
            <label class="field">
                <span>Status Operasional</span>
                <select name="status">
                    <option>Aktif</option>
                    <option>Tidak aktif</option>
                </select>
            </label>
            <div class="form-actions">
                <button class="button button-primary" type="submit">
                    <i data-lucide="plus"></i>Tambah Toilet
                </button>
            </div>
        </form>
    </div>

    <div class="surface surface-pad">
        <div class="surface-heading">
            <div>
                <h2>Impor Toilet (Excel / CSV)</h2>
                <p>Kolom: <code>nama_toilet, nama_gedung, lantai, jenis_toilet, jumlah_bilik, status_toilet</code>.</p>
            </div>
            <i data-lucide="file-spreadsheet"></i>
        </div>
        <form class="form-stack" method="post" action="{{ route('toilets.import') }}" enctype="multipart/form-data">
            @csrf
            <label class="file-dropzone" for="toilet-file">
                <input class="file-input-hidden" id="toilet-file" name="toilet_file" type="file" accept=".xlsx,.xls,.csv" required>
                <i data-lucide="upload-cloud"></i>
                <strong id="toilet-file-label">Pilih file Excel / CSV toilet</strong>
                <span>Format XLSX, XLS, atau CSV · Maksimal 5 MB</span>
            </label>
            <button class="button button-secondary" type="submit">
                <i data-lucide="upload"></i>Impor Data Toilet
            </button>
        </form>
    </div>
</section>

<section class="surface surface-pad">
    <div class="surface-heading">
        <div>
            <h2>Daftar Toilet Sekolah</h2>
            <p>{{ count($toilets) }} lokasi toilet tercatat.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table js-datatable">
            <thead>
                <tr>
                    <th>Nama Toilet</th>
                    <th>Gedung</th>
                    <th>Lantai</th>
                    <th>Jenis</th>
                    <th>Bilik</th>
                    <th>Status</th>
                    <th style="width:100px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($toilets as $toilet)
                    <tr>
                        <td><strong>{{ $toilet['name'] }}</strong></td>
                        <td>{{ $toilet['building'] }}</td>
                        <td>{{ $toilet['floor'] }}</td>
                        <td>{{ $toilet['type'] }}</td>
                        <td>{{ $toilet['cubicles'] }} bilik</td>
                        <td>
                            <span class="badge {{ $toilet['status'] === 'Aktif' ? 'badge-success' : 'badge-danger' }}">
                                {{ $toilet['status'] }}
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <button class="row-link" type="button" data-open-dialog="edit-toilet-{{ $toilet['id'] }}" data-dialog-key="toilet-{{ $toilet['id'] }}" aria-label="Edit toilet {{ $toilet['name'] }}">
                                    <i data-lucide="pencil"></i>
                                </button>
                                <form method="post" action="{{ route('toilets.delete', $toilet['id']) }}" data-confirm="Hapus {{ $toilet['name'] }}?">
                                    @csrf @method('DELETE')
                                    <button class="row-link" type="submit" aria-label="Hapus toilet">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"><div class="empty-state">Belum ada data toilet. Tambahkan lokasi toilet di atas.</div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @foreach ($toilets as $toilet)
        <dialog class="edit-dialog" id="edit-toilet-{{ $toilet['id'] }}" data-edit-dialog data-dialog-key="toilet-{{ $toilet['id'] }}">
            <div class="dialog-head">
                <div>
                    <p class="eyebrow">MASTER TOILET</p>
                    <h2>Edit Lokasi Toilet</h2>
                </div>
                <button class="icon-button" type="button" data-dialog-close aria-label="Tutup"><i data-lucide="x"></i></button>
            </div>
            <form class="dialog-form" method="post" action="{{ route('toilets.update', $toilet['id']) }}">
                @csrf @method('PUT')
                <div class="dialog-body">
                    <label class="field">
                        <span>Nama Toilet</span>
                        <input name="name" value="{{ old('name', $toilet['name']) }}" required maxlength="80">
                    </label>
                    <label class="field">
                        <span>Nama Gedung</span>
                        <select name="building" required>
                            @foreach ($buildings as $b)
                                <option value="{{ $b['name'] }}" @selected(old('building', $toilet['building']) === $b['name'])>{{ $b['name'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field">
                        <span>Lantai</span>
                        <input name="floor" value="{{ old('floor', $toilet['floor']) }}" required maxlength="30">
                    </label>
                    <label class="field">
                        <span>Jenis Toilet</span>
                        <select name="type" required>
                            @foreach (['Pria','Wanita','Bersama'] as $type)
                                <option @selected(old('type', $toilet['type']) === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field">
                        <span>Jumlah Bilik</span>
                        <input name="cubicles" type="number" min="1" max="100" value="{{ old('cubicles', $toilet['cubicles']) }}" required>
                    </label>
                    <label class="field">
                        <span>Status Operasional</span>
                        <select name="status">
                            <option @selected(old('status', $toilet['status']) === 'Aktif')>Aktif</option>
                            <option @selected(old('status', $toilet['status']) === 'Tidak aktif')>Tidak aktif</option>
                        </select>
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
    const tFile = document.getElementById('toilet-file');
    const tLabel = document.getElementById('toilet-file-label');
    tFile?.addEventListener('change', () => {
        tLabel.textContent = tFile.files[0]?.name ?? 'Pilih file Excel / CSV toilet';
    });
</script>
@endsection
