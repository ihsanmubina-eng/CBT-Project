@extends('layouts.app')

@section('title', 'Histori Data')
@section('page-title', 'Histori Data')

@section('content')
<div class="page-heading"><div><p class="eyebrow">ARSIP SISWA</p><h1>Histori data</h1><p>Arsip siswa mutasi dan catatan piket yang tetap tersimpan saat data aktif berubah.</p></div><a class="button button-secondary" href="{{ route('public.export') }}"><i data-lucide="download"></i>Ekspor arsip CSV</a></div>
<div class="notice"><i data-lucide="archive"></i><span>Arsip menyimpan catatan mutasi pada data siswa. Jadwal dan laporan piket yang terhubung tetap dipertahankan.</span></div>
<section class="metric-grid" aria-label="Ringkasan arsip">
    <div class="metric"><div><div class="metric-label">Siswa diarsipkan</div><div class="metric-value">{{ $totalRecords }}</div></div><div class="metric-icon"><i data-lucide="users-round"></i></div></div>
    <div class="metric"><div><div class="metric-label">Riwayat piket</div><div class="metric-value">{{ collect($records)->sum('pickets') }}</div></div><div class="metric-icon"><i data-lucide="calendar-check"></i></div></div>
    <div class="metric"><div><div class="metric-label">Jenis mutasi</div><div class="metric-value">{{ collect($records)->pluck('type')->unique()->count() }}</div></div><div class="metric-icon"><i data-lucide="list-filter"></i></div></div>
    <div class="metric"><div><div class="metric-label">Hasil pencarian</div><div class="metric-value">{{ count($records) }}</div></div><div class="metric-icon"><i data-lucide="search"></i></div></div>
</section>
<section class="surface surface-pad">
    <div class="toolbar">
        <div><h2 class="section-title">Arsip siswa mutasi</h2><p class="section-subtitle">Pilih siswa untuk memperbarui detail arsipnya.</p></div>
    </div>
    <div class="table-wrap"><table class="data-table js-datatable"><thead><tr><th>Siswa</th><th>Rombel terakhir</th><th>Tanggal</th><th>Jenis mutasi</th><th>Riwayat piket</th><th>Aksi</th></tr></thead><tbody>
        @forelse ($records as $record)
            <tr><td><strong>{{ $record['name'] }}</strong><small>NIS {{ $record['nis'] }}</small></td><td>{{ $record['class'] }}</td><td>{{ \Carbon\Carbon::parse($record['date'])->format('d M Y') }}</td><td><span class="badge badge-blue">{{ $record['type'] }}</span></td><td>{{ $record['pickets'] }} tugas</td><td><div class="table-actions"><button class="row-link" type="button" data-open-dialog="edit-history-{{ $record['id'] }}" data-dialog-key="history-{{ $record['id'] }}" aria-label="Edit arsip {{ $record['name'] }}" aria-haspopup="dialog"><i data-lucide="pencil"></i></button><form method="post" action="{{ route('history.delete', $record['id']) }}" data-confirm="Kembalikan {{ $record['name'] }} ke roster aktif?">@csrf @method('DELETE')<button class="row-link" type="submit" aria-label="Kembalikan siswa ke roster aktif"><i data-lucide="rotate-ccw"></i></button></form></div></td></tr>
        @empty
            <tr><td colspan="6"><div class="empty-state">Tidak ada arsip yang cocok dengan pencarian.</div></td></tr>
        @endforelse
    </tbody></table></div>
    @foreach ($records as $record)
        <dialog class="edit-dialog" id="edit-history-{{ $record['id'] }}" data-edit-dialog data-dialog-key="history-{{ $record['id'] }}" aria-labelledby="history-dialog-title-{{ $record['id'] }}">
            <div class="dialog-head"><div><p class="eyebrow">HISTORI DATA</p><h2 id="history-dialog-title-{{ $record['id'] }}">Edit arsip siswa</h2></div><button class="icon-button" type="button" data-dialog-close aria-label="Tutup dialog"><i data-lucide="x"></i></button></div>
            <form class="dialog-form" method="post" action="{{ route('history.update', $record['id']) }}">
                @csrf @method('PUT')
                <div class="dialog-body">
                    <label class="field"><span>Nama lengkap</span><input name="name" value="{{ old('name', $record['name']) }}" required maxlength="100"></label>
                    <label class="field"><span>NIS</span><input name="nis" value="{{ old('nis', $record['nis']) }}" required maxlength="30"></label>
                    <label class="field"><span>Rombel terakhir</span><input name="class" value="{{ old('class', $record['class']) }}" required maxlength="40"></label>
                    <label class="field"><span>Tanggal arsip</span><input name="date" type="date" value="{{ old('date', $record['date']) }}" required></label>
                    <label class="field"><span>Jenis mutasi</span><select name="type" required>@foreach (['Pindah Sekolah','Mutasi Kelas','Lulus'] as $type)<option @selected(old('type', $record['type']) === $type)>{{ $type }}</option>@endforeach</select></label>
                    <label class="field"><span>Keterangan</span><input name="note" value="{{ old('note', $record['note']) }}" maxlength="180"></label>
                </div>
                <div class="dialog-footer"><button class="button button-secondary" type="button" data-dialog-close>Batal</button><button class="button button-primary" type="submit"><i data-lucide="save"></i>Simpan perubahan</button></div>
            </form>
        </dialog>
    @endforeach
</section>
@endsection