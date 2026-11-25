@extends('layouts.app')

@section('title', 'Histori Data')
@section('page-title', 'Histori Data & Mutasi')

@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">ARSIP KESISWAAN</p>
        <h1>Histori Data</h1>
        <p>Arsip siswa mutasi dan riwayat penugasan piket yang terjaga utuh.</p>
    </div>
    <a class="button button-secondary" href="{{ route('public.export') }}">
        <i data-lucide="download"></i>Ekspor Arsip (.xlsx)
    </a>
</div>

<div class="notice">
    <i data-lucide="shield-check"></i>
    <span>Histori pengerjaan dan detail jadwal tetap terhubung meskipun siswa pindah sekolah, mutasi kelas, atau telah lulus (proteksi integritas basis data).</span>
</div>

<section class="metric-grid" aria-label="Ringkasan arsip">
    <div class="metric">
        <div>
            <div class="metric-label">Total Siswa Diarsipkan</div>
            <div class="metric-value">{{ $totalRecords }}</div>
        </div>
        <div class="metric-icon"><i data-lucide="user-x"></i></div>
    </div>
    <div class="metric">
        <div>
            <div class="metric-label">Total Riwayat Piket Terjaga</div>
            <div class="metric-value">{{ collect($records)->sum('pickets') }}</div>
        </div>
        <div class="metric-icon"><i data-lucide="clipboard-check"></i></div>
    </div>
    <div class="metric">
        <div>
            <div class="metric-label">Kategori Mutasi</div>
            <div class="metric-value">{{ collect($records)->pluck('type')->unique()->count() }}</div>
        </div>
        <div class="metric-icon"><i data-lucide="tag"></i></div>
    </div>
    <div class="metric">
        <div>
            <div class="metric-label">Catatan Mutasi</div>
            <div class="metric-value">{{ count($records) }}</div>
        </div>
        <div class="metric-icon"><i data-lucide="file-text"></i></div>
    </div>
</section>

<section class="surface surface-pad">
    <div class="surface-heading">
        <div>
            <h2>Arsip Siswa Mutasi & Lulus</h2>
            <p>Data siswa yang telah dinonaktifkan dari jadwal reguler.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table js-datatable">
            <thead>
                <tr>
                    <th>Nama Siswa</th>
                    <th>Rombel Terakhir</th>
                    <th>Tanggal Arsip</th>
                    <th>Jenis Mutasi</th>
                    <th>Riwayat Piket</th>
                    <th style="width:100px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td>
                            <strong>{{ $record['name'] }}</strong>
                            <small>NIS: {{ $record['nis'] }}</small>
                        </td>
                        <td>{{ $record['class'] }}</td>
                        <td>{{ \Carbon\Carbon::parse($record['date'])->format('d M Y') }}</td>
                        <td>
                            <span class="badge badge-blue">{{ $record['type'] }}</span>
                        </td>
                        <td>{{ $record['pickets'] }} tugas tersimpan</td>
                        <td>
                            <div class="table-actions">
                                <button class="row-link" type="button" data-open-dialog="edit-history-{{ $record['id'] }}" data-dialog-key="history-{{ $record['id'] }}" aria-label="Edit arsip {{ $record['name'] }}">
                                    <i data-lucide="pencil"></i>
                                </button>
                                <form method="post" action="{{ route('history.delete', $record['id']) }}" data-confirm="Kembalikan {{ $record['name'] }} ke roster siswa aktif?">
                                    @csrf @method('DELETE')
                                    <button class="row-link" type="submit" aria-label="Pulihkan ke aktif" title="Pulihkan ke aktif">
                                        <i data-lucide="rotate-ccw"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6"><div class="empty-state">Belum ada siswa yang dimutasikan atau diarsipkan.</div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @foreach ($records as $record)
        <dialog class="edit-dialog" id="edit-history-{{ $record['id'] }}" data-edit-dialog data-dialog-key="history-{{ $record['id'] }}">
            <div class="dialog-head">
                <div>
                    <p class="eyebrow">HISTORI MUTASI</p>
                    <h2>Edit Keterangan Arsip</h2>
                </div>
                <button class="icon-button" type="button" data-dialog-close aria-label="Tutup"><i data-lucide="x"></i></button>
            </div>
            <form class="dialog-form" method="post" action="{{ route('history.update', $record['id']) }}">
                @csrf @method('PUT')
                <div class="dialog-body">
                    <label class="field">
                        <span>Nama Lengkap</span>
                        <input name="name" value="{{ old('name', $record['name']) }}" required maxlength="100">
                    </label>
                    <label class="field">
                        <span>NIS</span>
                        <input name="nis" value="{{ old('nis', $record['nis']) }}" required maxlength="30">
                    </label>
                    <label class="field">
                        <span>Rombel Terakhir</span>
                        <input name="class" value="{{ old('class', $record['class']) }}" required maxlength="40">
                    </label>
                    <label class="field">
                        <span>Tanggal Arsip / Mutasi</span>
                        <input name="date" type="date" value="{{ old('date', $record['date']) }}" required>
                    </label>
                    <label class="field">
                        <span>Jenis Mutasi</span>
                        <select name="type" required>
                            @foreach (['Pindah Sekolah','Mutasi Kelas','Lulus'] as $type)
                                <option @selected(old('type', $record['type']) === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field">
                        <span>Keterangan Tambahan</span>
                        <input name="note" value="{{ old('note', $record['note']) }}" maxlength="180">
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
@endsection
