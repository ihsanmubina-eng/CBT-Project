<?php

namespace Tests\Feature;

use App\Models\DetailJadwal;
use App\Models\JadwalPiket;
use App\Models\MasterGedung;
use App\Models\MasterRegu;
use App\Models\MasterToilet;
use App\Models\Siswa;
use App\Models\TokenAkses;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetugasWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_multi_toilet_and_individual_student_workflow(): void
    {
        $user = User::create(['nama_lengkap' => 'Guru', 'username' => 'guru.test', 'password' => 'secret123', 'role' => 'Guru']);
        $building = MasterGedung::create(['nama_gedung' => 'Gedung Graha Widya']);

        $t1 = MasterToilet::create([
            'gedung_id' => $building->id,
            'nama_toilet' => 'Toilet Putra Lt. 1',
            'lantai' => 'Lantai 1',
            'jenis_toilet' => 'Pria',
            'jumlah_bilik' => 4,
            'status_toilet' => 'Aktif',
        ]);
        $t2 = MasterToilet::create([
            'gedung_id' => $building->id,
            'nama_toilet' => 'Toilet Putri Lt. 1',
            'lantai' => 'Lantai 1',
            'jenis_toilet' => 'Wanita',
            'jumlah_bilik' => 4,
            'status_toilet' => 'Aktif',
        ]);

        $dayName = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'][today()->dayOfWeekIso];
        $s1 = Siswa::create(['nis' => '101', 'nama_lengkap' => 'Ahmad Fauzan', 'rombel' => 'XII PPLG 1']);
        $s2 = Siswa::create(['nis' => '102', 'nama_lengkap' => 'Alifia Zahra', 'rombel' => 'XII PPLG 1']);
        $s3 = Siswa::create(['nis' => '103', 'nama_lengkap' => 'Bintang Wahyu', 'rombel' => 'XII PPLG 2']);
        $s4 = Siswa::create(['nis' => '104', 'nama_lengkap' => 'Dewi Cahya', 'rombel' => 'XII PPLG 2']);

        $regu = MasterRegu::create([
            'nama_regu' => 'Regu 1',
            'gedung_id' => $building->id,
            'ketua_siswa_id' => $s1->id,
            'keterangan' => 'Regu Gedung Graha Widya',
        ]);

        // Jadwal Toilet 1 (2 Siswa)
        $sch1 = JadwalPiket::create([
            'hari' => $dayName,
            'minggu_ke' => 1,
            'nama_regu' => 1,
            'regu_id' => $regu->id,
            'toilet_id' => $t1->id,
            'status_jadwal' => 'Aktif',
        ]);
        DetailJadwal::create(['jadwal_id' => $sch1->id, 'siswa_id' => $s1->id, 'status_penugasan' => 'Aktif']);
        DetailJadwal::create(['jadwal_id' => $sch1->id, 'siswa_id' => $s2->id, 'status_penugasan' => 'Aktif']);

        // Jadwal Toilet 2 (2 Siswa)
        $sch2 = JadwalPiket::create([
            'hari' => $dayName,
            'minggu_ke' => 1,
            'nama_regu' => 1,
            'regu_id' => $regu->id,
            'toilet_id' => $t2->id,
            'status_jadwal' => 'Aktif',
        ]);
        DetailJadwal::create(['jadwal_id' => $sch2->id, 'siswa_id' => $s3->id, 'status_penugasan' => 'Aktif']);
        DetailJadwal::create(['jadwal_id' => $sch2->id, 'siswa_id' => $s4->id, 'status_penugasan' => 'Aktif']);

        $token = TokenAkses::create([
            'kode_token' => 'PKT-1234',
            'tanggal' => today(),
            'minggu_ke' => 1,
            'berlaku_mulai' => '00:00:00',
            'berlaku_sampai' => '23:59:59',
            'dibuat_oleh' => $user->id,
            'status' => 'Aktif',
        ]);

        // 1. Petugas/Regu Masuk (Token & Regu)
        $this->post(route('login.student'), ['token' => 'PKT-1234', 'team' => '1'])
            ->assertRedirect(route('petugas.dashboard'));

        // 2. Dashboard menampilkan Wilayah Tugas, Dropdown Toilet, dan 2 Siswa Piket
        $dash = $this->get(route('petugas.dashboard'));
        $dash->assertOk()
            ->assertSee('Wilayah Tugas: Gedung Graha Widya')
            ->assertSee('Ketua: Ahmad Fauzan')
            ->assertSee('Toilet Putra Lt. 1')
            ->assertSee('Toilet Putri Lt. 1')
            ->assertSee('0 / 2 Toilet Selesai')
            ->assertSee('Ahmad Fauzan')
            ->assertSee('Alifia Zahra')
            ->assertSee('Periksa & Isi Laporan Siswa Ini', false);

        // 3. Klik siswa 1 -> Pindah halaman terpisah untuk periksa siswa 1
        $inspectS1 = $this->get(route('petugas.inspect', ['schedule_id' => $sch1->id, 'student_id' => $s1->id]));
        $inspectS1->assertOk()
            ->assertSee('Pemeriksaan: Ahmad Fauzan')
            ->assertSee('Status Kehadiran Siswa')
            ->assertSee('Hadir')
            ->assertSee('Tidak Hadir')
            ->assertSee('Kondisi Kebersihan Toilet')
            ->assertSee('Bersih')
            ->assertSee('Perlu dibersihkan')
            ->assertSee('Rusak');

        // 4. Submit laporan Siswa 1 dengan status Hadir
        $this->post(route('petugas.report'), [
            'schedule_id' => $sch1->id,
            'student_id' => $s1->id,
            'status_kehadiran' => 'Hadir',
            'status_kebersihan' => 'Bersih',
            'catatan' => 'Siswa 1 selesai menyapu dan mengepel.',
        ])->assertRedirect(route('petugas.dashboard', ['schedule_id' => $sch1->id]));

        $this->assertDatabaseHas('histori_pengerjaan', [
            'jadwal_id' => $sch1->id,
            'pelapor_id' => $s1->id,
            'status_kehadiran' => 'Hadir',
            'status_kebersihan' => 'Bersih',
        ]);
        $this->assertDatabaseHas('kehadiran_siswa', [
            'siswa_id' => $s1->id,
            'status_kehadiran' => 'Hadir',
        ]);

        // Dashboard sekarang menampilkan Siswa 1 Laporan Terkunci (Hadir), dan Siswa 2 masih menunggu
        $dashAfterS1 = $this->get(route('petugas.dashboard', ['schedule_id' => $sch1->id]));
        $dashAfterS1->assertOk()
            ->assertSee('Laporan terkunci')
            ->assertSee('Hadir');

        // Mencoba buka lagi form siswa 1 yang sudah terkunci -> diredirect dengan error
        $this->get(route('petugas.inspect', ['schedule_id' => $sch1->id, 'student_id' => $s1->id]))
            ->assertRedirect(route('petugas.dashboard', ['schedule_id' => $sch1->id]))
            ->assertSessionHas('error');

        // 5. Buka halaman terpisah untuk Siswa 2
        $inspectS2 = $this->get(route('petugas.inspect', ['schedule_id' => $sch1->id, 'student_id' => $s2->id]));
        $inspectS2->assertOk()->assertSee('Pemeriksaan: Alifia Zahra');

        // Submit laporan Siswa 2 dengan status Tidak Hadir
        $this->post(route('petugas.report'), [
            'schedule_id' => $sch1->id,
            'student_id' => $s2->id,
            'status_kehadiran' => 'Tidak Hadir',
            'status_kebersihan' => 'Perlu dibersihkan',
            'catatan' => 'Siswa sakit tidak masuk.',
        ])->assertRedirect(route('petugas.dashboard', ['schedule_id' => $sch1->id]));

        $this->assertDatabaseHas('histori_pengerjaan', [
            'jadwal_id' => $sch1->id,
            'pelapor_id' => $s2->id,
            'status_kehadiran' => 'Tidak Hadir',
            'status_kebersihan' => 'Perlu dibersihkan',
        ]);
        $this->assertDatabaseHas('kehadiran_siswa', [
            'siswa_id' => $s2->id,
            'status_kehadiran' => 'Tidak Hadir',
        ]);

        // Toilet 1 sekarang sudah lengkap (2/2 Siswa selesai)
        $dashAfterT1 = $this->get(route('petugas.dashboard', ['schedule_id' => $sch1->id]));
        $dashAfterT1->assertOk()
            ->assertSee('Toilet ini sudah lengkap diperiksa (2/2 Siswa).')
            ->assertSee('1 / 2 Toilet Selesai');
    }

    public function test_staff_can_manage_master_regu_crud(): void
    {
        $user = User::create(['nama_lengkap' => 'Admin', 'username' => 'admin.test', 'password' => 'secret123', 'role' => 'Admin']);
        $building = MasterGedung::create(['nama_gedung' => 'Gedung Penunjang']);
        $student = Siswa::create(['nis' => '999', 'nama_lengkap' => 'Ketua Baru', 'rombel' => 'XI PPLG 1']);

        $this->actingAs($user);

        // 1. Create Regu
        $res = $this->post(route('teams.store'), [
            'nama_regu' => 'Regu 99',
            'gedung_id' => $building->id,
            'ketua_siswa_id' => $student->id,
            'keterangan' => 'Regu penunjang',
        ]);
        $res->assertRedirect(route('teams.index'));

        $this->assertDatabaseHas('master_regu', [
            'nama_regu' => 'Regu 99',
            'gedung_id' => $building->id,
            'ketua_siswa_id' => $student->id,
        ]);

        $team = MasterRegu::where('nama_regu', 'Regu 99')->firstOrFail();

        // 2. View Index
        $index = $this->get(route('teams.index'));
        $index->assertOk()
            ->assertSee('Regu 99')
            ->assertSee('Gedung Penunjang')
            ->assertSee('Ketua Baru');

        // 3. Update Regu
        $this->put(route('teams.update', $team->id), [
            'nama_regu' => 'Regu 99 Revisi',
            'gedung_id' => $building->id,
            'ketua_siswa_id' => $student->id,
            'keterangan' => 'Keterangan baru',
        ])->assertRedirect(route('teams.index'));

        $this->assertDatabaseHas('master_regu', [
            'id' => $team->id,
            'nama_regu' => 'Regu 99 Revisi',
            'keterangan' => 'Keterangan baru',
        ]);

        // 4. Delete Regu
        $this->delete(route('teams.delete', $team->id))
            ->assertRedirect(route('teams.index'));

        $this->assertDatabaseMissing('master_regu', [
            'id' => $team->id,
        ]);
    }
}
