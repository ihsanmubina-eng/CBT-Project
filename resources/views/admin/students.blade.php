@extends('layouts.app')

@section('title', 'Data Siswa')
@section('page-title', 'Manajemen Data Siswa')

@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">ROSTER SEKOLAH</p>
        <h1>Data Siswa</h1>
        <p>Kelola roster siswa aktif dan mutasi untuk penugasan piket kebersihan.</p>
    </div>
    <a class="button button-secondary" href="{{ route('students.template') }}">
        <i data-lucide="download"></i>Template Excel (.xlsx)
    </a>
</div>

<section class="content-grid" style="margin-bottom:20px">
    <div class="surface surface-pad">
        <div class="surface-heading">
            <div>
                <h2>Tambah Siswa Manual</h2>
                <p>Tambah satu siswa ke roster aktif.</p>
            </div>
        </div>
        <form class="form-stack" method="post" action="{{ route('students.store') }}">
            @csrf
            <label class="field">
                <span>NIS (Nomor Induk Siswa)</span>
                <input name="nis" value="{{ old('nis') }}" placeholder="Contoh: 12400101" required maxlength="30">
            </label>
            <label class="field">
                <span>Nama Lengkap</span>
                <input name="name" value="{{ old('name') }}" placeholder="Contoh: Ahmad Fauzan Pratama" required maxlength="100">
            </label>
            <label class="field">
                <span>Rombel (Rombongan Belajar)</span>
                <input name="class" value="{{ old('class') }}" placeholder="Contoh: XII PPLG 1" required maxlength="40">
            </label>
            <button class="button button-primary" type="submit">
                <i data-lucide="user-plus"></i>Simpan Data Siswa
            </button>
        </form>
    </div>

    <div class="surface surface-pad">
        <div class="surface-heading">
            <div>
                <h2>Impor Siswa (Excel / CSV)</h2>
                <p>Kolom wajib: <code>nis, nama_lengkap, rombel</code>.</p>
            </div>
            <i data-lucide="file-spreadsheet"></i>
        </div>
        <form class="form-stack" method="post" action="{{ route('students.import') }}" enctype="multipart/form-data">
            @csrf
            <label class="file-dropzone" for="student-file">
                <input class="file-input-hidden" id="student-file" name="student_file" type="file" accept=".xlsx,.xls,.csv" required>
                <i data-lucide="upload-cloud"></i>
                <strong id="student-file-label">Pilih file Excel / CSV data siswa</strong>
                <span>Format XLSX, XLS, atau CSV · Maksimal 5 MB</span>
            </label>
            <button class="button button-secondary" type="submit">
                <i data-lucide="upload"></i>Impor Roster Siswa
            </button>
        </form>
    </div>
</section>

<section class="surface surface-pad">
    <div class="surface-heading">
        <div>
            <h2>Daftar Roster Siswa</h2>
            <p>{{ count($students) }} siswa terdaftar.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table js-datatable">
            <thead>
                <tr>
                    <th style="width:110px">NIS</th>
                    <th>Nama Lengkap</th>
                    <th>Rombel</th>
                    <th>Status</th>
                    <th>Total Tugas</th>
                    <th style="width:100px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $student)
                    <tr>
                        <td><code>{{ $student['nis'] }}</code></td>
                        <td><strong>{{ $student['name'] }}</strong></td>
                        <td>{{ $student['class'] }}</td>
                        <td>
                            <span class="badge {{ $student['status'] === 'Aktif' ? 'badge-success' : 'badge-warn' }}">
                                {{ $student['status'] }}
                            </span>
                        </td>
                        <td>{{ $student['pickets'] }} tugas</td>
                        <td>
                            <div class="table-actions">
                                @if ($student['status'] === 'Aktif')
                                    <button class="row-link" type="button" data-open-dialog="archive-student-{{ $student['id'] }}" data-dialog-key="student-{{ $student['id'] }}" aria-label="Arsipkan {{ $student['name'] }}" title="Mutasikan / Arsipkan">
                                        <i data-lucide="archive"></i>
                                    </button>
                                @else
                                    <form method="post" action="{{ route('history.delete', $student['id']) }}" data-confirm="Kembalikan {{ $student['name'] }} ke roster aktif?">
                                        @csrf @method('DELETE')
                                        <button class="row-link" type="submit" aria-label="Aktifkan kembali" title="Pulihkan ke aktif">
                                            <i data-lucide="rotate-ccw"></i>
                                        </button>
                                    </form>
                                @endif
                                <form method="post" action="{{ route('students.delete', $student['id']) }}" data-confirm="Hapus {{ $student['name'] }}? Siswa dengan histori penugasan tidak bisa dihapus permanen.">
                                    @csrf @method('DELETE')
                                    <button class="row-link" type="submit" aria-label="Hapus siswa">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6"><div class="empty-state">Belum ada data siswa. Tambahkan manual atau impor file roster.</div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @foreach ($students as $student)
        @if ($student['status'] === 'Aktif')
            <dialog class="edit-dialog" id="archive-student-{{ $student['id'] }}" data-edit-dialog data-dialog-key="student-{{ $student['id'] }}">
                <div class="dialog-head">
                    <div>
                        <p class="eyebrow">MUTASI SISWA</p>
                        <h2>Mutasikan {{ $student['name'] }}</h2>
                    </div>
                    <button class="icon-button" type="button" data-dialog-close aria-label="Tutup"><i data-lucide="x"></i></button>
                </div>
                <form class="dialog-form" method="post" action="{{ route('students.archive', $student['id']) }}">
                    @csrf @method('PATCH')
                    <div class="dialog-body">
                        <label class="field">
                            <span>Tanggal Mutasi</span>
                            <input name="tanggal_mutasi" type="date" value="{{ now()->toDateString() }}" required>
                        </label>
                        <label class="field">
                            <span>Jenis Mutasi</span>
                            <select name="jenis_mutasi" required>
                                <option>Pindah Sekolah</option>
                                <option>Mutasi Kelas</option>
                                <option>Lulus</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Keterangan Mutasi</span>
                            <input name="catatan_mutasi" placeholder="Contoh: Pindah ke luar kota" maxlength="500">
                        </label>
                    </div>
                    <div class="dialog-footer">
                        <button class="button button-secondary" type="button" data-dialog-close>Batal</button>
                        <button class="button button-primary" type="submit"><i data-lucide="archive"></i>Simpan Mutasi</button>
                    </div>
                </form>
            </dialog>
        @endif
    @endforeach
</section>

<script>
    const sFile = document.getElementById('student-file');
    const sLabel = document.getElementById('student-file-label');
    sFile?.addEventListener('change', () => {
        sLabel.textContent = sFile.files[0]?.name ?? 'Pilih file Excel / CSV data siswa';
    });
</script>
@endsection
