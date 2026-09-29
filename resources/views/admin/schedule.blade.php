@extends('layouts.app')

@section('title', 'Jadwal Piket')
@section('page-title', 'Pengaturan Jadwal Piket')

@section('content')
<div class="page-heading"><div><p class="eyebrow">PENUGASAN SISWA</p><h1>Jadwal piket</h1><p>Atur jadwal satu semester dan pembagian lokasi untuk setiap regu.</p></div></div>
<section class="surface surface-pad" style="margin-bottom:16px">
    <div class="surface-heading"><div><h2>Upload jadwal piket semester</h2><p>Siklus piket memakai rotasi empat minggu. Gunakan template agar pembagian regu terbaca dengan benar.</p></div></div>
    <a class="template-link" href="{{ route('schedule.template') }}"><i data-lucide="download"></i>Unduh template jadwal (.csv)</a>
    <div class="notice"><i data-lucide="info"></i><span>Pastikan gedung, toilet, dan siswa sudah terdaftar sebelum mengimpor. Nama siswa pada CSV harus cocok dengan Data Siswa, dan satu regu hanya menjaga satu toilet per hari.</span></div>
    <form class="schedule-upload-form" method="post" action="{{ route('schedule.import') }}" enctype="multipart/form-data" data-confirm="Jadwal aktif dan laporan piket lama akan diganti setelah CSV berhasil divalidasi. Lanjutkan?">
        @csrf
        <label class="file-dropzone" data-file-dropzone for="schedule-file">
            <input class="file-input-hidden" id="schedule-file" name="schedule_file" type="file" accept=".csv,text/csv" required data-schedule-file>
            <i data-lucide="cloud-upload"></i>
            <strong data-file-name>Seret & taruh file CSV jadwal piket di sini</strong>
            <span>atau klik untuk memilih file dari perangkat</span>
            <small>Format CSV · Maksimal 5 MB · Maksimal 500 baris</small>
        </label>
        <p class="upload-note"><i data-lucide="info"></i>Setiap Regu hanya boleh mendapat satu lokasi toilet pada hari dan minggu yang sama. Jadwal aktif diganti setelah seluruh isi CSV lolos validasi.</p>
        <button class="button button-primary upload-submit" type="submit"><i data-lucide="calendar-check"></i>Upload & aktifkan jadwal semester</button>
    </form>
</section>
<section class="surface surface-pad">
    <div class="surface-heading"><div><h2>Siklus jadwal aktif</h2><p>{{ count($schedules) }} penugasan tercatat.</p></div></div>
    <div class="table-wrap"><table class="data-table js-datatable"><thead><tr><th>Hari / Minggu</th><th>Regu</th><th>Lokasi</th><th>Siswa / Rombel</th><th>Status piket</th><th>Kehadiran</th><th>Aksi</th></tr></thead><tbody>
        @forelse ($schedules as $schedule)
            <tr><td><strong>{{ $schedule['day'] }}</strong><small>Minggu {{ $schedule['week'] }}</small></td><td><span class="badge badge-blue">Regu {{ $schedule['team'] }}</span></td><td><strong>{{ $schedule['building'] }}</strong><small>{{ $schedule['room'] }}</small></td><td>{{ $schedule['students'] }}<small>{{ $schedule['class'] }}</small></td><td><span class="badge {{ $schedule['status'] === 'Selesai' ? '' : 'badge-warn' }}">{{ $schedule['status'] }}</span></td><td><span class="badge {{ $schedule['attendance'] === 'Hadir' ? '' : 'badge-warn' }}">{{ $schedule['attendance'] }}</span></td><td><form class="row-form" method="post" action="{{ route('schedule.update', $schedule['id']) }}">@csrf @method('PATCH')<select name="status" aria-label="Status piket"><option @selected($schedule['status'] === 'Belum diperiksa')>Belum diperiksa</option><option @selected($schedule['status'] === 'Selesai')>Selesai</option></select><select name="attendance" aria-label="Kehadiran"><option @selected($schedule['attendance'] === 'Hadir')>Hadir</option><option @selected($schedule['attendance'] === 'Izin')>Izin</option><option @selected($schedule['attendance'] === 'Sakit')>Sakit</option><option @selected($schedule['attendance'] === 'Tidak hadir')>Tidak hadir</option></select><button class="row-link" type="submit" aria-label="Simpan status"><i data-lucide="save"></i></button></form></td></tr>
        @empty
            <tr><td colspan="7"><div class="empty-state">Belum ada jadwal piket.</div></td></tr>
        @endforelse
    </tbody></table></div>
</section>
<script>
    const scheduleFile = document.querySelector('[data-schedule-file]');
    const fileDropzone = document.querySelector('[data-file-dropzone]');
    const fileName = document.querySelector('[data-file-name]');
    const defaultFileLabel = fileName?.textContent;
    const showSelectedFile = () => {
        fileName.textContent = scheduleFile.files[0]?.name ?? defaultFileLabel;
        fileDropzone.classList.toggle('file-selected', scheduleFile.files.length > 0);
    };
    scheduleFile?.addEventListener('change', showSelectedFile);
    ['dragenter', 'dragover'].forEach((eventName) => fileDropzone?.addEventListener(eventName, (event) => {
        event.preventDefault();
        fileDropzone.classList.add('file-dragging');
    }));
    ['dragleave', 'drop'].forEach((eventName) => fileDropzone?.addEventListener(eventName, (event) => {
        event.preventDefault();
        fileDropzone.classList.remove('file-dragging');
        if (eventName === 'drop' && event.dataTransfer.files.length) {
            scheduleFile.files = event.dataTransfer.files;
            showSelectedFile();
        }
    }));
</script>
@endsection
