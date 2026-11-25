@extends('layouts.app')

@section('title', 'Master Regu')
@section('page-title', 'Manajemen Master Regu Piket')

@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">PENGORGANISASIAN PIKET</p>
        <h1>Master Regu</h1>
        <p>Kelola pembagian regu piket, penetapan wilayah gedung tanggung jawab, dan ketua regu siswa.</p>
    </div>
</div>

<section class="content-grid" style="margin-bottom:24px">
    <div class="surface surface-pad">
        <div class="surface-heading">
            <div>
                <h2>Tambah Master Regu</h2>
                <p>Kunci regu ke gedung tertentu dan tetapkan ketua siswa.</p>
            </div>
            <i data-lucide="shield-plus"></i>
        </div>
        <form class="form-stack" method="post" action="{{ route('teams.store') }}">
            @csrf
            <label class="field">
                <span>Nama Regu</span>
                <input name="nama_regu" value="{{ old('nama_regu') }}" placeholder="Contoh: Regu 1" required maxlength="100">
            </label>

            <label class="field">
                <span>Gedung Tanggung Jawab</span>
                <select name="gedung_id" required>
                    <option value="" disabled selected>-- Pilih Gedung --</option>
                    @foreach ($buildings as $b)
                        <option value="{{ $b->id }}" {{ old('gedung_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->nama_gedung }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="field">
                <span>Ketua Regu (Siswa)</span>
                <select name="ketua_siswa_id">
                    <option value="">-- Pilih Ketua Regu (Opsional) --</option>
                    @foreach ($students as $s)
                        <option value="{{ $s->id }}" {{ old('ketua_siswa_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->nama_lengkap }} ({{ $s->rombel }})
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="field">
                <span>Keterangan / Deskripsi Tugas</span>
                <input name="keterangan" value="{{ old('keterangan') }}" placeholder="Contoh: Bertanggung jawab atas toilet lt 1 & lt 2" maxlength="255">
            </label>

            <button class="button button-primary" type="submit">
                <i data-lucide="plus"></i>Simpan Master Regu
            </button>
        </form>
    </div>

    <div class="surface surface-pad">
        <div class="surface-heading">
            <div>
                <h2>Informasi Relasi Regu</h2>
                <p>Prinsip keterikatan data sistem ResikKita.</p>
            </div>
            <i data-lucide="info"></i>
        </div>
        <div style="display:flex; flex-direction:column; gap:16px; font-size:13px; color:var(--text-secondary)">
            <div style="display:flex; gap:12px; align-items:flex-start">
                <i data-lucide="building-2" style="color:var(--text-muted); flex-shrink:0; margin-top:2px"></i>
                <div>
                    <strong>1 Regu = 1 Gedung</strong>
                    <p style="margin:2px 0 0; color:var(--text-muted); font-size:12.5px">Setiap regu hanya bertugas di 1 wilayah gedung tertentu untuk memudahkan kontrol dan pengawasan kebersihan.</p>
                </div>
            </div>
            <div style="display:flex; gap:12px; align-items:flex-start">
                <i data-lucide="user-check" style="color:var(--text-muted); flex-shrink:0; margin-top:2px"></i>
                <div>
                    <strong>Ketua Regu Bertanggung Jawab</strong>
                    <p style="margin:2px 0 0; color:var(--text-muted); font-size:12.5px">Ketua regu dipilih dari siswa aktif sebagai koordinator dan penanggung jawab kelengkapan laporan regu.</p>
                </div>
            </div>
            <div style="display:flex; gap:12px; align-items:flex-start">
                <i data-lucide="calendar-check" style="color:var(--text-muted); flex-shrink:0; margin-top:2px"></i>
                <div>
                    <strong>Terkunci ke Jadwal Piket</strong>
                    <p style="margin:2px 0 0; color:var(--text-muted); font-size:12.5px">Jadwal piket harian toilet secara otomatis terikat ke ID Master Regu ini melalui relasi database.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="surface surface-pad">
    <div class="surface-heading">
        <div>
            <h2>Daftar Master Regu</h2>
            <p>Total {{ $teams->count() }} regu terdaftar dalam sistem.</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table" id="teams-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Regu</th>
                    <th>Gedung Tanggung Jawab</th>
                    <th>Ketua Regu (Siswa)</th>
                    <th>Jadwal Terikat</th>
                    <th>Keterangan</th>
                    <th style="text-align:right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($teams as $t)
                    <tr>
                        <td><strong>#{{ $t->id }}</strong></td>
                        <td>
                            <span style="font-family:'Manrope',sans-serif; font-weight:700; color:var(--accent); font-size:14px">
                                {{ $t->nama_regu }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-blue">
                                <i data-lucide="building"></i>{{ $t->building->nama_gedung ?? '-' }}
                            </span>
                        </td>
                        <td>
                            @if ($t->leader)
                                <div style="display:flex; align-items:center; gap:6px">
                                    <i data-lucide="user-check" style="width:14px; height:14px; color:var(--accent)"></i>
                                    <strong>{{ $t->leader->nama_lengkap }}</strong>
                                    <small style="color:var(--text-muted)">({{ $t->leader->rombel }})</small>
                                </div>
                            @else
                                <span style="color:var(--text-muted)">- Belum diset -</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge">{{ $t->schedules_count }} Jadwal</span>
                        </td>
                        <td style="color:var(--text-muted); font-size:12.5px">
                            {{ $t->keterangan ?? '-' }}
                        </td>
                        <td style="text-align:right">
                            <div style="display:inline-flex; gap:6px">
                                <a href="{{ route('teams.edit', $t->id) }}" class="button button-secondary button-small" title="Edit Regu">
                                    <i data-lucide="pencil"></i>Edit
                                </a>
                                <form method="post" action="{{ route('teams.delete', $t->id) }}" onsubmit="return confirm('Hapus master regu ini?');">
                                    @csrf
                                    @method('delete')
                                    <button class="button button-danger button-small" type="submit" title="Hapus Regu" {{ $t->schedules_count > 0 ? 'disabled' : '' }}>
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:32px; color:var(--text-muted)">
                            Belum ada master regu yang ditambahkan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery && $.fn.DataTable) {
            $('#teams-table').DataTable({
                pageLength: 10,
                language: {
                    search: "Cari regu:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ regu",
                    infoEmpty: "Tidak ada data regu",
                    zeroRecords: "Regu tidak ditemukan",
                    paginate: { first: "Awal", last: "Akhir", next: "→", previous: "←" }
                }
            });
        }
    });
</script>
@endsection
