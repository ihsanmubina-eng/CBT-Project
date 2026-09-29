<?php

namespace Tests\Feature;

use App\Models\DetailJadwal;
use App\Models\JadwalPiket;
use App\Models\MasterGedung;
use App\Models\MasterToilet;
use App\Models\Siswa;
use App\Models\TokenAkses;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_to_login_without_staff_or_petugas_access(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_staff_can_sign_in_with_a_database_account(): void
    {
        User::create([
            'nama_lengkap' => 'Guru Database',
            'username' => 'guru.database',
            'password' => 'Rahasia123!',
            'role' => 'Guru',
        ]);

        $this->post(route('login.authenticate'), [
            'username' => 'guru.database',
            'password' => 'Rahasia123!',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Guru Database')
            ->assertSee('Belum dibuat');
    }

    public function test_public_dashboard_does_not_require_staff_login(): void
    {
        $this->get(route('public.dashboard'))
            ->assertOk()
            ->assertSee('Status piket kebersihan');
    }

    public function test_admin_pages_redirect_to_login_without_staff_session(): void
    {
        $this->get(route('buildings.index'))
            ->assertRedirect(route('login'));
    }

    public function test_student_token_and_team_open_the_database_assignment(): void
    {
        [$user, $toilet, $student, $schedule] = $this->createTaskForToday(2);
        $token = $this->createToken($user, 1);

        $this->post(route('login.student'), [
            'token' => $token->kode_token,
            'team' => '2',
        ])->assertRedirect(route('petugas.dashboard'));

        $this->get(route('petugas.dashboard'))
            ->assertOk()
            ->assertSee('Regu 2')
            ->assertSee($toilet->nama_toilet)
            ->assertSee($student->nama_lengkap);
    }

    public function test_petugas_report_is_saved_to_database_and_locks_after_first_submission(): void
    {
        [$user, $toilet, , $schedule] = $this->createTaskForToday(1);
        $token = $this->createToken($user, 1);
        $this->post(route('login.student'), [
            'token' => $token->kode_token,
            'team' => '1',
        ])->assertRedirect(route('petugas.dashboard'));

        $this->post(route('petugas.report'), [
            'status_kebersihan' => 'Bersih',
            'catatan' => 'Sudah diperiksa oleh regu.',
        ])->assertRedirect(route('petugas.dashboard'))
            ->assertSessionHas('success', 'Laporan sudah dikirim dan dikunci.');

        $this->get(route('petugas.dashboard'))
            ->assertOk()
            ->assertSee('Status kebersihan: Bersih')
            ->assertSee('Terkunci');
        $this->assertDatabaseHas('histori_pengerjaan', [
            'jadwal_id' => $schedule->id,
            'token_id' => $token->id,
            'toilet_id' => $toilet->id,
            'status_kebersihan' => 'Bersih',
            'status_kunci' => 'Terkunci',
        ]);

        $this->from(route('petugas.dashboard'))
            ->post(route('petugas.report'), [
                'status_kebersihan' => 'Rusak',
                'catatan' => 'Percobaan laporan kedua.',
            ])->assertRedirect(route('petugas.dashboard'))
            ->assertSessionHas('error', 'Laporan regu ini sudah dikirim dan terkunci.');
    }

    public function test_staff_can_import_a_csv_schedule_for_multiple_database_teams(): void
    {
        $this->staff();
        $building = MasterGedung::create(['nama_gedung' => 'Gedung A']);
        $toiletMan = MasterToilet::create([
            'gedung_id' => $building->id, 'nama_toilet' => 'Toilet Pria', 'lantai' => 'Lantai 1',
            'jenis_toilet' => 'Pria', 'jumlah_bilik' => 4, 'status_toilet' => 'Aktif',
        ]);
        $toiletWoman = MasterToilet::create([
            'gedung_id' => $building->id, 'nama_toilet' => 'Toilet Wanita', 'lantai' => 'Lantai 1',
            'jenis_toilet' => 'Wanita', 'jumlah_bilik' => 4, 'status_toilet' => 'Aktif',
        ]);
        $rian = Siswa::create(['nis' => '001', 'nama_lengkap' => 'Rian Hidayat', 'rombel' => 'XI PPLG 1']);
        $siti = Siswa::create(['nis' => '002', 'nama_lengkap' => 'Siti Aminah', 'rombel' => 'XI PPLG 1']);
        $oldSchedule = JadwalPiket::create([
            'hari' => 'Jumat', 'minggu_ke' => 4, 'nama_regu' => 4,
            'toilet_id' => $toiletMan->id, 'status_jadwal' => 'Aktif',
        ]);

        $csv = implode("\n", [
            'hari,minggu,regu,gedung,ruangan,siswa,rombel',
            'Senin,1,1,Gedung A,Toilet Pria (Lt. 1),Rian Hidayat,XI PPLG 1',
            'Senin,1,2,Gedung A,Toilet Wanita (Lt. 1),Siti Aminah,XI PPLG 1',
        ]);

        $this->from(route('schedule.index'))->post(route('schedule.import'), [
            'schedule_file' => UploadedFile::fake()->createWithContent('jadwal.csv', $csv),
        ])->assertRedirect(route('schedule.index'))
            ->assertSessionHas('success', '2 jadwal berhasil diimpor. Siklus sebelumnya diarsipkan; histori laporan tetap tersimpan.');

        $this->assertDatabaseCount('jadwal_piket', 3);
        $this->assertDatabaseHas('jadwal_piket', ['id' => $oldSchedule->id, 'status_jadwal' => 'Arsip']);
        $this->assertDatabaseHas('jadwal_piket', ['nama_regu' => 2, 'toilet_id' => $toiletWoman->id, 'status_jadwal' => 'Aktif']);
        $this->assertDatabaseHas('detail_jadwal', ['siswa_id' => $rian->id]);
        $this->assertDatabaseHas('detail_jadwal', ['siswa_id' => $siti->id]);
    }

    public function test_invalid_csv_does_not_archive_or_replace_active_database_schedules(): void
    {
        $this->staff();
        $building = MasterGedung::create(['nama_gedung' => 'Gedung A']);
        $toilet = MasterToilet::create([
            'gedung_id' => $building->id, 'nama_toilet' => 'Toilet Pria', 'lantai' => 'Lantai 1',
            'jenis_toilet' => 'Pria', 'jumlah_bilik' => 4, 'status_toilet' => 'Aktif',
        ]);
        Siswa::create(['nis' => '001', 'nama_lengkap' => 'Rian Hidayat', 'rombel' => 'XI PPLG 1']);
        $oldSchedule = JadwalPiket::create([
            'hari' => 'Jumat', 'minggu_ke' => 4, 'nama_regu' => 4,
            'toilet_id' => $toilet->id, 'status_jadwal' => 'Aktif',
        ]);

        $csv = implode("\n", [
            'hari,minggu,regu,gedung,ruangan,siswa,rombel',
            'Senin,1,1,Gedung A,Toilet Pria (Lt. 1),Rian Hidayat,XI PPLG 1',
            'Senin,1,1,Gedung A,Toilet Pria (Lt. 1),Rian Hidayat,XI PPLG 1',
        ]);

        $this->post(route('schedule.import'), [
            'schedule_file' => UploadedFile::fake()->createWithContent('jadwal.csv', $csv),
        ])->assertRedirect()
            ->assertSessionHasErrors('schedule_file');

        $this->assertDatabaseCount('jadwal_piket', 1);
        $this->assertDatabaseHas('jadwal_piket', ['id' => $oldSchedule->id, 'status_jadwal' => 'Aktif']);
    }

    public function test_student_csv_import_populates_the_roster_table(): void
    {
        $this->staff();
        $csv = "nis,nama_lengkap,rombel\n001,Rian Hidayat,XI PPLG 1\n";

        $this->from(route('students.index'))->post(route('students.import'), [
            'student_file' => UploadedFile::fake()->createWithContent('siswa.csv', $csv),
        ])->assertRedirect(route('students.index'))
            ->assertSessionHas('success', '1 data siswa berhasil diimpor.');

        $this->assertDatabaseHas('siswa', ['nis' => '001', 'nama_lengkap' => 'Rian Hidayat']);
    }

    private function staff(): User
    {
        $user = User::create([
            'nama_lengkap' => 'Admin Test', 'username' => 'admin.test',
            'password' => 'StrongPass123!', 'role' => 'Admin',
        ]);
        $this->actingAs($user);

        return $user;
    }

    private function createTaskForToday(int $team): array
    {
        $user = $this->staff();
        $building = MasterGedung::create(['nama_gedung' => 'Gedung Regu '.$team]);
        $toilet = MasterToilet::create([
            'gedung_id' => $building->id, 'nama_toilet' => 'Toilet Regu '.$team, 'lantai' => 'Lantai 1',
            'jenis_toilet' => 'Bersama', 'jumlah_bilik' => 3, 'status_toilet' => 'Aktif',
        ]);
        $student = Siswa::create([
            'nis' => str_pad((string) $team, 3, '0', STR_PAD_LEFT),
            'nama_lengkap' => 'Siswa Regu '.$team, 'rombel' => 'XI PPLG 1',
        ]);
        $schedule = JadwalPiket::create([
            'hari' => $this->todayName(), 'minggu_ke' => 1, 'nama_regu' => $team,
            'toilet_id' => $toilet->id, 'status_jadwal' => 'Aktif',
        ]);
        DetailJadwal::create(['jadwal_id' => $schedule->id, 'siswa_id' => $student->id, 'status_penugasan' => 'Aktif']);

        return [$user, $toilet, $student, $schedule];
    }

    private function createToken(User $user, int $week): TokenAkses
    {
        return TokenAkses::create([
            'kode_token' => 'PKT-'.random_int(1000, 9999), 'tanggal' => today(), 'minggu_ke' => $week,
            'berlaku_mulai' => '00:00:00', 'berlaku_sampai' => '23:59:59',
            'dibuat_oleh' => $user->id, 'status' => 'Aktif',
        ]);
    }

    private function todayName(): string
    {
        return [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'][today()->dayOfWeekIso];
    }
}
