# 📌 [PSAS 2026/2027] PEMBARUAN & PERAPIHAN PROJECT BOARD NOTION (CBT RESIKKITA)

Dokumen ini memuat panduan langkah demi langkah untuk memperbarui status kartu, melengkapi isi kartu (*card details*), serta materi konversi ORM ke Raw SQL untuk pengumpulan berkas PSAS.

---

## 🚀 1. Pembaruan Status Board (Drag & Drop Card)

Pindahkan kartu-kartu berikut dari kolom **In progress** / **Not started** ke kolom **Done**:

| Nama Kartu | Status Baru | Priority | Tags / Label | Keterangan Singkat |
| :--- | :---: | :---: | :--- | :--- |
| **[Fitur 4] Portal Presensi Petugas, Token Akses & Transaksi Laporan** | **Done** ✅ | High | `Database Transaction`, `Presensi Individu` | Selesai 100%. Presensi dipisah perorangan, alur siswa Tidak Hadir otomatis diset *Tidak Bertugas*, validasi token harian aktif. |
| **[Fitur 7] Dashboard Monitoring Publik & Ekspor Laporan** | **Done** ✅ | Medium | `Public Monitoring`, `Excel Export` | Selesai 100%. Endpoint `/pantau` aktif tanpa auth, metrik harian realtime, filter gedung/lantai, dan ekspor spreadsheet. |
| **[Refactor] Pemecahan Controller & Perorangan Jadwal Piket** | **Done** ✅ | High | `Clean Code`, `Refactoring` | Controller monolitik 930 baris (`DatabaseController`) telah dipecah menjadi 12 modul controller terpisah + jadwal perorangan. |
| **[UI/UX] Desain Sistem Institusional Netral (Unslop)** | **Done** ✅ | Low | `Design System`, `Institutional UI` | Penghapusan warna neon/pastel transparan, penghapusan emoji pada dropdown, pembersihan teks debug di login, dan standarisasi badge solid hairline. |

---

## 📝 2. Isi Detail untuk Card: `[Fitur 4] Portal Presensi Petugas, Token Akses & Transaksi Laporan`

*(Buka kartu Fitur 4 di Notion dan tempelkan bagian ini ke dalam halaman kartunya)*

### A. Deskripsi Fitur
Modul autentikasi berbasis token harian untuk siswa/petugas piket serta form inspeksi kebersihan toilet dengan verifikasi presensi per individu siswa.

### B. Alur Logika Baru (Presensi Perorangan)
1. **Siswa Hadir:**
   - Dropdown kebersihan aktif (`Bersih`, `Perlu dibersihkan`, `Rusak`).
   - Laporan disimpan dan dihitung dalam metrik toilet bersih.
2. **Siswa Tidak Hadir (Sakit / Izin / Alpa):**
   - Status kebersihan otomatis diset menjadi **`Tidak Bertugas`**.
   - Input catatan beralih untuk menuliskan keterangan ketidakhadiran.
   - Pada dashboard admin, muncul badge merah **Tidak Hadir** dan status **Tidak Bertugas (Absen)** sehingga adil dan transparan.

### C. Konversi ORM ke Raw SQL (Bahan Google Docs PSAS)
* **Query Cek Token Aktif Hari Ini:**
  * *Eloquent ORM:*
    ```php
    TokenAkses::where('kode_token', $kode)->whereDate('tanggal', today())->where('status', 'Aktif')->first();
    ```
  * *Raw SQL:*
    ```sql
    SELECT * FROM token_akses 
    WHERE UPPER(TRIM(kode_token)) = 'PKT-1234' 
      AND tanggal = CURDATE() 
      AND status = 'Aktif' 
    LIMIT 1;
    ```
* **Query Simpan Laporan Presensi Individu:**
  * *Eloquent ORM:*
    ```php
    HistoriPengerjaan::updateOrCreate(
        ['jadwal_id' => $jadwalId, 'pelapor_id' => $siswaId, 'tanggal' => today()],
        ['status_kebersihan' => $statusKebersihan, 'status_kehadiran' => $statusKehadiran, 'catatan' => $catatan]
    );
    ```
  * *Raw SQL:*
    ```sql
    INSERT INTO histori_pengerjaan (tanggal, jadwal_id, token_id, toilet_id, pelapor_id, status_kebersihan, status_kehadiran, catatan, created_at, updated_at)
    VALUES (CURDATE(), 12, 5, 3, 101, 'Bersih', 'Hadir', 'Kondisi wangi dan kering', NOW(), NOW())
    ON DUPLICATE KEY UPDATE 
        status_kebersihan = VALUES(status_kebersihan),
        status_kehadiran = VALUES(status_kehadiran),
        catatan = VALUES(catatan),
        updated_at = NOW();
    ```

### D. Screenshot Terkait
* `dokumentasi_progress/05_controller_petugas_submit_report.png`
* `dokumentasi_progress/06_view_petugas_inspect_blade.png`
* `dokumentasi_progress/07_view_petugas_dashboard_blade.png`

---

## 📊 3. Isi Detail untuk Card: `[Fitur 7] Dashboard Monitoring Publik & Ekspor Laporan`

*(Buka kartu Fitur 7 di Notion dan tempelkan bagian ini)*

### A. Deskripsi Fitur
Halaman monitoring toilet publik tanpa autentikasi (`/pantau`) yang menampilkan kondisi riil seluruh toilet sekolah secara realtime, serta panel admin untuk merekap laporan harian dan ekspor ke format Excel/CSV.

### B. Konversi ORM ke Raw SQL (Bahan Google Docs PSAS)
* **Query Rekap Metrik Kebersihan Harian:**
  * *Eloquent ORM:*
    ```php
    $total = MasterToilet::count();
    $bersih = HistoriPengerjaan::whereDate('tanggal', today())->where('status_kebersihan', 'Bersih')->count();
    ```
  * *Raw SQL:*
    ```sql
    SELECT 
        COUNT(t.id) AS total_toilet,
        SUM(CASE WHEN hp.status_kebersihan = 'Bersih' THEN 1 ELSE 0 END) AS total_bersih,
        SUM(CASE WHEN hp.status_kebersihan = 'Perlu dibersihkan' THEN 1 ELSE 0 END) AS total_perlu_pembersihan,
        SUM(CASE WHEN hp.status_kebersihan = 'Rusak' THEN 1 ELSE 0 END) AS total_rusak,
        SUM(CASE WHEN hp.id IS NULL THEN 1 ELSE 0 END) AS total_belum_diperiksa
    FROM master_toilet t
    LEFT JOIN histori_pengerjaan hp ON t.id = hp.toilet_id AND hp.tanggal = CURDATE();
    ```
* **Query Tabel Jadwal Piket Per Siswa (Presensi 1 per 1):**
  * *Raw SQL:*
    ```sql
    SELECT 
        jp.id AS jadwal_id, jp.hari, jp.minggu_ke, jp.nama_regu,
        g.nama_gedung, t.nama_toilet, t.lantai,
        s.id AS siswa_id, s.nis, s.nama_lengkap, s.rombel,
        COALESCE(hp.status_kehadiran, 'Belum diisi') AS status_kehadiran,
        COALESCE(hp.status_kebersihan, 'Belum diperiksa') AS status_kebersihan
    FROM jadwal_piket jp
    JOIN master_toilet t ON jp.toilet_id = t.id
    JOIN master_gedung g ON t.gedung_id = g.id
    JOIN detail_jadwal dj ON jp.id = dj.jadwal_id
    JOIN siswa s ON dj.siswa_id = s.id
    LEFT JOIN histori_pengerjaan hp ON jp.id = hp.jadwal_id AND s.id = hp.pelapor_id AND hp.tanggal = CURDATE()
    WHERE jp.status_jadwal = 'Aktif'
    ORDER BY FIELD(jp.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'), jp.nama_regu, s.nama_lengkap ASC;
    ```

### C. Screenshot Terkait
* `dokumentasi_progress/08_view_admin_dashboard_blade.png`
* `dokumentasi_progress/09_service_resikkita_format_report.png`

---

## ✅ 4. Checklist Final Pengumpulan Berkas (Google Form)

Perbarui status checklist di bagian bawah halaman Notion Anda menjadi:

- [x] **Link Repository GitHub** (7 Commit bertahap rapi di branch `main` dengan standar Conventional Commits).
- [x] **Link Notion Workspace ini** (Publish to web / Share to web aktif).
- [x] **File Dokumen Konversi ORM ke Query SQL Mentah** (Gunakan contoh SQL pada Bagian 2 & 3 di atas untuk dimasukkan ke Google Docs).
- [x] **File User Manual lengkap dengan screenshot alur sistem** (10 Screenshot dari folder `dokumentasi_progress/` siap dimasukkan ke template panduan).
