# 📋 DOKUMENTASI PROGRESS PEMINDAHAN LOGIC (CBT ORIGINAL ➔ CBTv2)

**Nama Proyek:** ResikKita - Sistem Monitoring & Piket Toilet Sekolah  
**Direktori Proyek:** `/home/ihsan/Downloads/3_Projects_Work/CBT/CBT-Project`  
**Waktu Pembaruan:** November 2026  
**Status:** 100% Logic V2 Terpasang & Sinkron

---

## 📌 1. Latar Belakang & Ringkasan Perubahan
Dokumentasi ini mencatat proses pemindahan logic bertahap dari **CBTv2** ke **CBT-Project (Original)**, termasuk perbaikan logika penting:
* **Penilaian Presensi Individu:** Siswa yang hadir dinilai kebersihannya, sedangkan siswa yang **Tidak Hadir** secara otomatis ditandai statusnya sebagai *"Tidak Bertugas (Absen)"*, bukan dinilai bersih secara ambigu.
* **Transparansi di Dashboard Admin:** Tabel laporan petugas kini memiliki kolom identitas **Nama Siswa** dan badge **Status Presensi** (`Hadir` / `Tidak Hadir`).
* **Manajemen Master Regu:** Struktur regu mandiri dengan relasi ke gedung dan ketua siswa.
* **Profil Staf:** Fitur ganti foto avatar dan ubah password dengan verifikasi password lama.

---

## 📸 2. Koleksi Screenshot Langsung dari VS Code (Siap Dipakai di Notion)

Seluruh screenshot di bawah ini diambil langsung dari jendela **Visual Studio Code** asli di sistem, menampilkan file explorer, tab file, struktur baris kode, dan tema syntax asli:

| No | Nama File Screenshot | Path File di VS Code | Fokus Kode yang Didokumentasikan |
| :---: | :--- | :--- | :--- |
| **01** | `01_migrasi_update_histori_pengerjaan.png` | `database/migrations/2026_11_20_000004_update_histori_pengerjaan_for_individual_students.php` | Composite unique `['jadwal_id', 'pelapor_id']` & kolom `status_kehadiran`. |
| **02** | `02_migrasi_create_master_regu.png` | `database/migrations/2026_11_20_000005_create_master_regu_table.php` | Pembuatan tabel `master_regu` & foreign key `regu_id` pada `jadwal_piket`. |
| **03** | `03_model_master_regu.png` | `app/Models/MasterRegu.php` | Model Eloquent dengan relasi `building`, `leader`, dan `schedules`. |
| **04** | `04_model_histori_pengerjaan.png` | `app/Models/HistoriPengerjaan.php` | Relasi laporan pengerjaan dengan siswa pelapor (`pelapor`) & token aktif. |
| **05** | `05_controller_petugas_submit_report.png` | `app/Http/Controllers/PetugasController.php` | Logic penanganan siswa **Tidak Hadir** (`status_kebersihan = 'Tidak Bertugas'`). |
| **06** | `06_view_petugas_inspect_blade.png` | `resources/views/petugas/inspect.blade.php` | Form radio presensi siswa (`Hadir` / `Tidak Hadir`) interaktif. |
| **07** | `07_view_petugas_dashboard_blade.png` | `resources/views/petugas/dashboard.blade.php` | Tampilan kartu siswa di portal petugas dengan peringatan merah jika tidak masuk. |
| **08** | `08_view_admin_dashboard_blade.png` | `resources/views/admin/dashboard.blade.php` | Tabel dashboard admin: kolom Siswa Petugas, badge presensi, dan status kebersihan. |
| **09** | `09_service_resikkita_format_report.png` | `app/Services/ResikKitaService.php` | Format data laporan API/monitoring mencakup NIS, rombel, dan status kehadiran. |
| **10** | `10_routes_web.png` | `routes/web.php` | Routing lengkap untuk modul petugas, regu, profil, dan token login. |

Semua file gambar tersimpan di folder:
📁 `dokumentasi_progress/`

---

## 🛠️ 3. Rincian Perubahan Logika Terbaru (Fix Alur Presensi)

### A. Penanganan Siswa "Tidak Hadir" di Form Petugas (`inspect.blade.php`)
* Ketika petugas memilih radio **Tidak Hadir**:
  * Dropdown kondisi kebersihan toilet otomatis disembunyikan dan nilainya diset ke **`Tidak Bertugas`**.
  * Muncul pesan info merah: *"Siswa Tidak Hadir sehingga tidak menjalankan tugas piket di toilet ini."*
  * Label catatan beralih menjadi panduan mencatat alasan ketidakhadiran (Sakit / Izin / Alpa).

### B. Tampilan Kartu Siswa di Portal Petugas (`petugas/dashboard.blade.php`)
* Jika siswa **Hadir**: Menampilkan `Kondisi Toilet: Bersih / Perlu dibersihkan / Rusak`.
* Jika siswa **Tidak Hadir**: Menampilkan status merah `Siswa Tidak Masuk (Tidak Bertugas)` beserta catatan ketidakhadirannya.

### C. Tabel Laporan di Dashboard Admin (`admin/dashboard.blade.php`)
* Ditambahkan kolom **Siswa Petugas** (Nama Lengkap, NIS, Rombel).
* Ditambahkan kolom **Presensi** (Badge Hijau `Hadir` atau Badge Merah `Tidak Hadir`).
* Kolom **Status Kebersihan** menampilkan `Tidak Bertugas (Absen)` jika siswa tidak hadir, sehingga tidak lagi memicu kesalahpahaman.

### D. Pembersihan Halaman Login (`auth/login.blade.php`)
* Menghilangkan teks debug kodingan teknis (`php artisan resikkita:create-staff-user`).
* Digantikan dengan petunjuk login institusional yang rapi dan profesional untuk staf & admin.

### E. Penilaian & Presensi Jadwal Piket Perorangan (`ScheduleController.php` & `admin/schedule.blade.php`)
* **Pemisahan Baris Siswa:** Jadwal piket yang sebelumnya menggabungkan nama dua siswa dalam 1 baris kini dipecah menjadi 1 baris per siswa (`formatScheduleItems`).
* **Presensi Independen:** Setiap siswa memiliki form dropdown status pengerjaan (`Selesai` / `Belum diperiksa`) dan status presensi (`Hadir` / `Izin` / `Sakit` / `Tidak hadir`) dengan tombol simpan mandiri. Admin dapat mencatat siswa A "Hadir" dan siswa B "Sakit" secara independen.

### F. Netralisasi Desain Sistem & Unslop (`public/css/app.css` & Seluruh Views)
* **Solid Institutional Badges:** Mengganti seluruh badge pastel/transparan dengan token semantik solid bergaris batas hairline (`--status-*-line`, `--status-*-bg`, `--status-*-text`).
* **Pembersihan Glow & Shadow Berlebih:** Menghilangkan box-shadow neon dan border transparan pada tombol presensi (`.attendance-choice`), diganti dengan kartu netral bergaris batas halus.
* **Quiet Monochrome Icons:** Mengembalikan warna ikon inline (Lucide) ke hierarki teks natural (`currentColor` / `var(--text-muted)`) agar antarmuka tidak berisik.
* **Penghapusan AI Slop & Emoji:** Menghapus emoji (`✨`, `🧹`, `⚠️`) dari `<option>` dan mengganti ikon generic sparkles dengan ikon utilitas yang tepat (`clipboard-check`).

---

## 📦 5. Rencana 7 Tahap Commit Bersih (Git Commit Roadmap)

Untuk memenuhi standar pengumpulan berkas PSAS 2026/2027 dengan commit teratur dan terstruktur:

1. **Commit 1: `feat(database): add individual student attendance and master regu migrations`**
   - Migration 000004 (composite key `jadwal_id` + `pelapor_id`, status kehadiran).
   - Migration 000005 (tabel `master_regu`).
   - Migration 000006 (kolom foto profil).
2. **Commit 2: `feat(models): update eloquent models and relations for teams and individual reports`**
   - Model `MasterRegu`, `HistoriPengerjaan`, `JadwalPiket`, `MasterGedung`, `Siswa`, `User`.
3. **Commit 3: `feat(services): implement core ResikKitaService for schedule and inspection pipeline`**
   - `ResikKitaService` (perhitungan metrik, penanganan siswa perorangan, format laporan).
4. **Commit 4: `feat(controllers): add petugas workflow, team management, and profile handlers`**
   - Controller `PetugasController`, `TeamController`, `ProfileController`, `ScheduleController`, dll.
5. **Commit 5: `feat(views): integrate individual schedule assessment and petugas inspection UI`**
   - Tampilan perorangan jadwal piket (`admin/schedule.blade.php`), portal petugas (`petugas/dashboard.blade.php`, `petugas/inspect.blade.php`).
6. **Commit 6: `refactor(ui): unslop design system, neutralize colors, and cleanup login page`**
   - Netralisasi CSS token (`app.css`), penghapusan teks debug artisan di `login.blade.php`, standarisasi badge solid dan icon hairline.
7. **Commit 7: `test: add feature tests and engineering documentation`**
   - Dokumen progress, test feature, dan seeder final.

---

## 🚀 6. Cara Menjalankan & Menguji

```bash
# 1. Pastikan database / XAMPP siap
sudo /opt/lampp/xampp start

# 2. Migrasi dan isi seeder
cd /home/ihsan/Downloads/3_Projects_Work/CBT/CBT-Project
php artisan migrate
php artisan db:seed

# 3. Jalankan server lokal
php artisan serve --port=8001
```
* **Portal Petugas:** Buka `http://127.0.0.1:8001/login` ➔ Tab *Login Petugas* (Token: `PKT-1234`, pilih `Regu 1`).
* **Portal Admin:** Buka `http://127.0.0.1:8001/login` (`admin` / `admin123`).
