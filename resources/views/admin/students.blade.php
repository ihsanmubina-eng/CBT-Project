@extends('layouts.app')

@section('title', 'Data Siswa')
@section('page-title', 'Data Siswa')

@section('content')
<div class="page-heading"><div><p class="eyebrow">ROSTER SEKOLAH</p><h1>Data siswa</h1><p>Kelola roster aktif dan arsip mutasi untuk jadwal piket.</p></div><a class="button button-secondary" href="{{ route('students.template') }}"><i data-lucide="download"></i>Template CSV siswa</a></div>
<section class="content-grid">
    <div class="surface surface-pad">
        <div class="surface-heading"><div><h2>Tambah siswa</h2><p>Tambahkan satu siswa ke roster.</p></div></div>
        <form class="form-stack" method="post" action="{{ route('students.store') }}">
            @csrf
            <label class="field"><span>NIS</span><input name="nis" value="{{ old('nis') }}" required maxlength="30"></label>
            <label class="field"><span>Nama lengkap</span><input name="name" value="{{ old('name') }}" required maxlength="100"></label>
            <label class="field"><span>Rombel</span><input name="class" value="{{ old('class') }}" placeholder="XI PPLG 1" required maxlength="40"></label>
            <button class="button button-primary" type="submit"><i data-lucide="user-round-plus"></i>Simpan siswa</button>
        </form>
    </div>
    <div class="surface surface-pad">
        <div class="surface-heading"><div><h2>Impor roster CSV</h2><p>Kolom wajib: `nis,nama_lengkap,rombel`.</p></div><i data-lucide="file-spreadsheet"></i></div>
        <form class="student-import-form" method="post" action="{{ route('students.import') }}" enctype="multipart/form-data">
            @csrf
            <label class="file-dropzone" data-file-dropzone for="student-file">
                <input class="file-input-hidden" id="student-file" name="student_file" type="file" accept=".csv,text/csv" required data-schedule-file>
                <i data-lucide="cloud-upload"></i><strong data-file-name>Pilih file CSV siswa</strong><span>Format CSV · Maksimal 5 MB</span>
            </label>
            <button class="button button-secondary" type="submit"><i data-lucide="upload"></i>Impor siswa</button>
        </form>
    </div>
</section>

<section class="surface surface-pad" style="margin-top:16px">
    <div class="toolbar"><div><h2 class="section-title">Roster siswa</h2><p class="section-subtitle">{{ count($students) }} siswa tercatat.</p></div></div>
    <div class="table-wrap"><table class="data-table js-datatable"><thead><tr><th>NIS</th><th>Nama lengkap</th><th>Rombel</th><th>Status</th><th>Histori piket</th><th>Aksi</th></tr></thead><tbody>
        @forelse ($students as $student)
            <tr><td>{{ $student['nis'] }}</td><td><strong>{{ $student['name'] }}</strong></td><td>{{ $student['class'] }}</td><td><span class="badge {{ $student['status'] === 'Aktif' ? '' : 'badge-warn' }}">{{ $student['status'] }}</span></td><td>{{ $student['pickets'] }} tugas</td><td><div class="table-actions">
                @if ($student['status'] === 'Aktif')
                    <button class="row-link" type="button" data-open-dialog="archive-student-{{ $student['id'] }}" data-dialog-key="student-{{ $student['id'] }}" aria-label="Arsipkan {{ $student['name'] }}" aria-haspopup="dialog"><i data-lucide="archive"></i></button>
                @else
                    <form method="post" action="{{ route('history.delete', $student['id']) }}" data-confirm="Kembalikan {{ $student['name'] }} ke roster aktif?">@csrf @method('DELETE')<button class="row-link" type="submit" aria-label="Aktifkan kembali {{ $student['name'] }}"><i data-lucide="rotate-ccw"></i></button></form>
                @endif
                <form method="post" action="{{ route('students.delete', $student['id']) }}" data-confirm="Hapus data siswa {{ $student['name'] }} secara permanen? Riwayat yang sudah terhubung tidak dapat dihapus.">@csrf @method('DELETE')<button class="row-link" type="submit" aria-label="Hapus siswa {{ $student['name'] }}"><i data-lucide="trash-2"></i></button></form>
            </div></td></tr>
        @empty
            <tr><td colspan="6"><div class="empty-state">Belum ada data siswa. Tambahkan manual atau impor file roster.</div></td></tr>
        @endforelse
    </tbody></table></div>
    @foreach ($students as $student)
        @if ($student['status'] === 'Aktif')
            <dialog class="edit-dialog" id="archive-student-{{ $student['id'] }}" data-edit-dialog data-dialog-key="student-{{ $student['id'] }}" aria-labelledby="archive-title-{{ $student['id'] }}">
                <div class="dialog-head"><div><p class="eyebrow">ARSIP SISWA</p><h2 id="archive-title-{{ $student['id'] }}">Mutasikan siswa</h2></div><button class="icon-button" type="button" data-dialog-close aria-label="Tutup dialog"><i data-lucide="x"></i></button></div>
                <form class="dialog-form" method="post" action="{{ route('students.archive', $student['id']) }}">
                    @csrf @method('PATCH')
                    <div class="dialog-body">
                        <label class="field"><span>Tanggal mutasi</span><input name="tanggal_mutasi" type="date" value="{{ now()->toDateString() }}" required></label>
                        <label class="field"><span>Jenis mutasi</span><select name="jenis_mutasi" required><option>Pindah Sekolah</option><option>Mutasi Kelas</option><option>Lulus</option></select></label>
                        <label class="field"><span>Keterangan</span><input name="catatan_mutasi" maxlength="500"></label>
                    </div>
                    <div class="dialog-footer"><button class="button button-secondary" type="button" data-dialog-close>Batal</button><button class="button button-primary" type="submit"><i data-lucide="archive"></i>Simpan arsip</button></div>
                </form>
            </dialog>
        @endif
    @endforeach
</section>
<script>
    const studentFile = document.querySelector('#student-file');
    const studentFileName = document.querySelector('[data-file-name]');
    studentFile?.addEventListener('change', () => {
        studentFileName.textContent = studentFile.files[0]?.name ?? 'Pilih file CSV siswa';
    });
</script>
@endsection