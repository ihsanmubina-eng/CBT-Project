<?php

namespace Tests\Feature;

use App\Models\MasterGedung;
use App\Models\MasterToilet;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ExcelImportTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(): User
    {
        $user = User::create([
            'nama_lengkap' => 'Admin Penguji',
            'username' => 'admin.uji',
            'password' => 'Rahasia123!',
            'role' => 'Admin',
        ]);
        $this->actingAs($user);

        return $user;
    }

    private function createXlsxFile(string $filename, array $headers, array $rows): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'test_xlsx_');
        $writer = new Writer();
        $writer->openToFile($tmp);
        $writer->addRow(Row::fromValues($headers));
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return new UploadedFile($tmp, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_can_import_buildings_via_excel(): void
    {
        $this->actingAdmin();
        $file = $this->createXlsxFile('gedung.xlsx', ['nama_gedung', 'deskripsi'], [
            ['Gedung Graha Widya', 'Gedung pembelajaran teori'],
            ['Gedung Pusat Praktik', 'Laboratorium komputer'],
        ]);

        $this->post(route('buildings.import'), ['building_file' => $file])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('master_gedung', ['nama_gedung' => 'Gedung Graha Widya']);
        $this->assertDatabaseHas('master_gedung', ['nama_gedung' => 'Gedung Pusat Praktik']);
    }

    public function test_can_import_toilets_via_excel(): void
    {
        $this->actingAdmin();
        $file = $this->createXlsxFile('toilet.xlsx', ['nama_toilet', 'nama_gedung', 'lantai', 'jenis_toilet', 'jumlah_bilik', 'status_toilet'], [
            ['Toilet Putra Lt. 1', 'Gedung Utama', 'Lantai 1', 'Pria', 4, 'Aktif'],
        ]);

        $this->post(route('toilets.import'), ['toilet_file' => $file])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('master_gedung', ['nama_gedung' => 'Gedung Utama']);
        $this->assertDatabaseHas('master_toilet', [
            'nama_toilet' => 'Toilet Putra Lt. 1',
            'lantai' => 'Lantai 1',
            'jenis_toilet' => 'Pria',
            'jumlah_bilik' => 4,
            'status_toilet' => 'Aktif',
        ]);
    }

    public function test_can_import_students_via_excel(): void
    {
        $this->actingAdmin();
        $file = $this->createXlsxFile('siswa.xlsx', ['nis', 'nama_lengkap', 'rombel'], [
            ['12400999', 'Fauzan Ahmad', 'XII PPLG 1'],
        ]);

        $this->post(route('students.import'), ['student_file' => $file])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('siswa', [
            'nis' => '12400999',
            'nama_lengkap' => 'Fauzan Ahmad',
            'rombel' => 'XII PPLG 1',
        ]);
    }
}
