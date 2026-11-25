<?php

namespace Database\Seeders;

use App\Models\DetailJadwal;
use App\Models\JadwalPiket;
use App\Models\MasterGedung;
use App\Models\MasterRegu;
use App\Models\MasterToilet;
use App\Models\Siswa;
use App\Models\TokenAkses;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Admin Default
        $admin = User::firstOrCreate(['username' => 'admin'], [
            'nama_lengkap' => 'Administrator ResikKita',
            'password' => 'admin123',
            'role' => 'Admin',
        ]);

        // 2. Master Gedung
        $g1 = MasterGedung::firstOrCreate(['nama_gedung' => 'Gedung Graha Widya'], [
            'deskripsi' => 'Gedung pembelajaran teori dan perpustakaan pusat'
        ]);
        $g2 = MasterGedung::firstOrCreate(['nama_gedung' => 'Gedung Pusat Praktik Kejuruan'], [
            'deskripsi' => 'Laboratorium PPLG, DKV, dan bengkel TJKT'
        ]);
        $g3 = MasterGedung::firstOrCreate(['nama_gedung' => 'Gedung Penunjang & Seni'], [
            'deskripsi' => 'Kantin, aula serbaguna, dan ruang kegiatan siswa'
        ]);

        // 3. Master Toilet
        $toilets = [
            ['gedung_id' => $g1->id, 'nama_toilet' => 'Toilet Siswa Putra Lt. 1', 'lantai' => 'Lantai 1', 'jenis_toilet' => 'Pria', 'jumlah_bilik' => 4, 'status_toilet' => 'Aktif'],
            ['gedung_id' => $g1->id, 'nama_toilet' => 'Toilet Siswa Putri Lt. 1', 'lantai' => 'Lantai 1', 'jenis_toilet' => 'Wanita', 'jumlah_bilik' => 4, 'status_toilet' => 'Aktif'],
            ['gedung_id' => $g1->id, 'nama_toilet' => 'Toilet Bersama Lt. 2', 'lantai' => 'Lantai 2', 'jenis_toilet' => 'Bersama', 'jumlah_bilik' => 2, 'status_toilet' => 'Aktif'],
            ['gedung_id' => $g2->id, 'nama_toilet' => 'Toilet Lab Komputer', 'lantai' => 'Lantai 2', 'jenis_toilet' => 'Bersama', 'jumlah_bilik' => 3, 'status_toilet' => 'Aktif'],
            ['gedung_id' => $g2->id, 'nama_toilet' => 'Toilet Bengkel TJKT', 'lantai' => 'Lantai 1', 'jenis_toilet' => 'Pria', 'jumlah_bilik' => 3, 'status_toilet' => 'Aktif'],
        ];

        $toiletModels = [];
        foreach ($toilets as $t) {
            $toiletModels[] = MasterToilet::firstOrCreate([
                'gedung_id' => $t['gedung_id'],
                'nama_toilet' => $t['nama_toilet'],
                'lantai' => $t['lantai'],
            ], $t);
        }

        // 4. Data Siswa
        $studentsData = [
            ['nis' => '12400101', 'nama_lengkap' => 'Ahmad Fauzan Pratama', 'rombel' => 'XII PPLG 1'],
            ['nis' => '12400102', 'nama_lengkap' => 'Alifia Zahra Khairunnisa', 'rombel' => 'XII PPLG 1'],
            ['nis' => '12400103', 'nama_lengkap' => 'Bintang Wahyu Hidayat', 'rombel' => 'XII PPLG 2'],
            ['nis' => '12400104', 'nama_lengkap' => 'Dewi Cahyaningrum', 'rombel' => 'XII PPLG 2'],
            ['nis' => '12400105', 'nama_lengkap' => 'Fajar Nugraha', 'rombel' => 'XI DKV 1'],
            ['nis' => '12400106', 'nama_lengkap' => 'Gina Septiani', 'rombel' => 'XI DKV 1'],
            ['nis' => '12400107', 'nama_lengkap' => 'Ihsan Mubina', 'rombel' => 'XII PPLG 1'],
            ['nis' => '12400108', 'nama_lengkap' => 'Nabila Rahmadani', 'rombel' => 'XI TJKT 2'],
        ];

        $studentModels = [];
        foreach ($studentsData as $s) {
            $studentModels[] = Siswa::firstOrCreate(['nis' => $s['nis']], $s);
        }

        // 5. Master Regu (Relasi Gedung & Ketua Regu)
        $regu1 = MasterRegu::firstOrCreate([
            'nama_regu' => 'Regu 1',
        ], [
            'gedung_id' => $g1->id,
            'ketua_siswa_id' => $studentModels[0]->id, // Ahmad Fauzan Pratama
            'keterangan' => 'Bertanggung jawab atas kebersihan seluruh toilet di Gedung Graha Widya',
        ]);

        $regu2 = MasterRegu::firstOrCreate([
            'nama_regu' => 'Regu 2',
        ], [
            'gedung_id' => $g2->id,
            'ketua_siswa_id' => $studentModels[5]->id, // Gina Septiani
            'keterangan' => 'Bertanggung jawab atas kebersihan seluruh toilet di Gedung Pusat Praktik Kejuruan',
        ]);

        // 6. Token Akses Hari Ini
        TokenAkses::firstOrCreate([
            'kode_token' => 'PKT-1234',
        ], [
            'tanggal' => today(),
            'minggu_ke' => 1,
            'berlaku_mulai' => '00:00:00',
            'berlaku_sampai' => '23:59:59',
            'dibuat_oleh' => $admin->id,
            'status' => 'Aktif',
        ]);

        // 7. Jadwal Piket (7 Hari): Regu 1 di Gedung Graha Widya (3 Toilet x 2 Siswa)
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        foreach ($days as $day) {
            // Regu 1 -> Gedung Graha Widya (Toilet 0, 1, 2)
            $j1 = JadwalPiket::firstOrCreate([
                'hari' => $day,
                'minggu_ke' => 1,
                'nama_regu' => 1,
                'regu_id' => $regu1->id,
                'toilet_id' => $toiletModels[0]->id,
            ], ['status_jadwal' => 'Aktif']);
            DetailJadwal::firstOrCreate(['jadwal_id' => $j1->id, 'siswa_id' => $studentModels[0]->id], ['status_penugasan' => 'Aktif']);
            DetailJadwal::firstOrCreate(['jadwal_id' => $j1->id, 'siswa_id' => $studentModels[1]->id], ['status_penugasan' => 'Aktif']);

            $j2 = JadwalPiket::firstOrCreate([
                'hari' => $day,
                'minggu_ke' => 1,
                'nama_regu' => 1,
                'regu_id' => $regu1->id,
                'toilet_id' => $toiletModels[1]->id,
            ], ['status_jadwal' => 'Aktif']);
            DetailJadwal::firstOrCreate(['jadwal_id' => $j2->id, 'siswa_id' => $studentModels[2]->id], ['status_penugasan' => 'Aktif']);
            DetailJadwal::firstOrCreate(['jadwal_id' => $j2->id, 'siswa_id' => $studentModels[3]->id], ['status_penugasan' => 'Aktif']);

            $j3 = JadwalPiket::firstOrCreate([
                'hari' => $day,
                'minggu_ke' => 1,
                'nama_regu' => 1,
                'regu_id' => $regu1->id,
                'toilet_id' => $toiletModels[2]->id,
            ], ['status_jadwal' => 'Aktif']);
            DetailJadwal::firstOrCreate(['jadwal_id' => $j3->id, 'siswa_id' => $studentModels[4]->id], ['status_penugasan' => 'Aktif']);
            DetailJadwal::firstOrCreate(['jadwal_id' => $j3->id, 'siswa_id' => $studentModels[6]->id], ['status_penugasan' => 'Aktif']);

            // Regu 2 -> Gedung PPK (Toilet 3, 4)
            $j4 = JadwalPiket::firstOrCreate([
                'hari' => $day,
                'minggu_ke' => 1,
                'nama_regu' => 2,
                'regu_id' => $regu2->id,
                'toilet_id' => $toiletModels[3]->id,
            ], ['status_jadwal' => 'Aktif']);
            DetailJadwal::firstOrCreate(['jadwal_id' => $j4->id, 'siswa_id' => $studentModels[5]->id], ['status_penugasan' => 'Aktif']);
            DetailJadwal::firstOrCreate(['jadwal_id' => $j4->id, 'siswa_id' => $studentModels[7]->id], ['status_penugasan' => 'Aktif']);
        }
    }
}
