<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicMonitoringController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\ToiletController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\StaffAuth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public & Petugas Siswa Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [AuthController::class, 'index'])->name('home');
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->name('login.authenticate');
Route::post('/login/siswa', [AuthController::class, 'loginStudent'])->name('login.student');

Route::get('/petugas', [PetugasController::class, 'dashboard'])->name('petugas.dashboard');
Route::get('/petugas/periksa/{schedule_id}/{student_id}', [PetugasController::class, 'inspectStudent'])->name('petugas.inspect');
Route::post('/petugas/laporan', [PetugasController::class, 'submitReport'])->name('petugas.report');
Route::post('/petugas/keluar', [PetugasController::class, 'logoutStudent'])->name('petugas.logout');

Route::get('/pantau', [PublicMonitoringController::class, 'dashboard'])->name('public.dashboard');
Route::get('/pantau/ekspor', [PublicMonitoringController::class, 'exportHistory'])->name('public.export');

/*
|--------------------------------------------------------------------------
| Admin / Guru / Kesiswaan Routes (Protected by StaffAuth)
|--------------------------------------------------------------------------
*/
Route::middleware(StaffAuth::class)->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard & Token Generator
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/token', [DashboardController::class, 'generateToken'])->name('token.generate');

    // Profil Pengguna (Staff / Admin / Guru)
    Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Manajemen Pengguna (Staff Users)
    Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
    Route::post('/pengguna', [UserController::class, 'store'])->name('users.store');
    Route::delete('/pengguna/{id}', [UserController::class, 'destroy'])->name('users.delete');

    // Data Siswa & Roster
    Route::get('/siswa', [StudentController::class, 'index'])->name('students.index');
    Route::get('/siswa/template', [StudentController::class, 'template'])->name('students.template');
    Route::post('/siswa', [StudentController::class, 'store'])->name('students.store');
    Route::post('/siswa/import', [StudentController::class, 'import'])->name('students.import');
    Route::patch('/siswa/{id}/mutasi', [StudentController::class, 'archive'])->name('students.archive');
    Route::delete('/siswa/{id}', [StudentController::class, 'destroy'])->name('students.delete');

    // Master Regu
    Route::get('/regu', [TeamController::class, 'index'])->name('teams.index');
    Route::post('/regu', [TeamController::class, 'store'])->name('teams.store');
    Route::get('/regu/{id}/edit', [TeamController::class, 'edit'])->name('teams.edit');
    Route::put('/regu/{id}', [TeamController::class, 'update'])->name('teams.update');
    Route::delete('/regu/{id}', [TeamController::class, 'destroy'])->name('teams.delete');

    // Jadwal Piket & Presensi
    Route::get('/jadwal-piket', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::post('/jadwal-piket', [ScheduleController::class, 'store'])->name('schedule.store');
    Route::post('/jadwal-piket/import', [ScheduleController::class, 'import'])->name('schedule.import');
    Route::patch('/jadwal-piket/{id}', [ScheduleController::class, 'update'])->name('schedule.update');
    Route::get('/jadwal-piket/template', [ScheduleController::class, 'template'])->name('schedule.template');

    // Master Gedung
    Route::get('/gedung', [BuildingController::class, 'index'])->name('buildings.index');
    Route::post('/gedung', [BuildingController::class, 'store'])->name('buildings.store');
    Route::post('/gedung/import', [BuildingController::class, 'import'])->name('buildings.import');
    Route::get('/gedung/template', [BuildingController::class, 'template'])->name('buildings.template');
    Route::get('/gedung/{id}/edit', [BuildingController::class, 'edit'])->name('buildings.edit');
    Route::put('/gedung/{id}', [BuildingController::class, 'update'])->name('buildings.update');
    Route::delete('/gedung/{id}', [BuildingController::class, 'destroy'])->name('buildings.delete');

    // Master Toilet
    Route::get('/toilet', [ToiletController::class, 'index'])->name('toilets.index');
    Route::post('/toilet', [ToiletController::class, 'store'])->name('toilets.store');
    Route::post('/toilet/import', [ToiletController::class, 'import'])->name('toilets.import');
    Route::get('/toilet/template', [ToiletController::class, 'template'])->name('toilets.template');
    Route::get('/toilet/{id}/edit', [ToiletController::class, 'edit'])->name('toilets.edit');
    Route::put('/toilet/{id}', [ToiletController::class, 'update'])->name('toilets.update');
    Route::delete('/toilet/{id}', [ToiletController::class, 'destroy'])->name('toilets.delete');

    // Histori Data & Mutasi
    Route::get('/histori', [HistoryController::class, 'index'])->name('history.index');
    Route::get('/histori/{id}/edit', [HistoryController::class, 'edit'])->name('history.edit');
    Route::put('/histori/{id}', [HistoryController::class, 'update'])->name('history.update');
    Route::delete('/histori/{id}', [HistoryController::class, 'destroy'])->name('history.delete');
});
