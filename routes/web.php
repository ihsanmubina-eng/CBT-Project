<?php

use App\Http\Controllers\DatabaseController;
use App\Http\Middleware\StaffAuth;
use Illuminate\Support\Facades\Route;

Route::get('/', [DatabaseController::class, 'index'])->name('home');
Route::get('/login', [DatabaseController::class, 'login'])->name('login');
Route::post('/login', [DatabaseController::class, 'authenticate'])->name('login.authenticate');
Route::post('/login/siswa', [DatabaseController::class, 'loginStudent'])->name('login.student');
Route::get('/petugas', [DatabaseController::class, 'petugasDashboard'])->name('petugas.dashboard');
Route::post('/petugas/laporan', [DatabaseController::class, 'submitReport'])->name('petugas.report');
Route::post('/petugas/keluar', [DatabaseController::class, 'logoutStudent'])->name('petugas.logout');
Route::get('/pantau', [DatabaseController::class, 'publicDashboard'])->name('public.dashboard');
Route::get('/pantau/ekspor', [DatabaseController::class, 'exportHistory'])->name('public.export');


Route::middleware(StaffAuth::class)->group(function (): void {
	Route::post('/logout', [DatabaseController::class, 'logout'])->name('logout');

	Route::get('/dashboard', [DatabaseController::class, 'dashboard'])->name('dashboard');
	Route::post('/dashboard/token', [DatabaseController::class, 'generateToken'])->name('token.generate');

	Route::get('/pengguna', [DatabaseController::class, 'users'])->name('users.index');
	Route::post('/pengguna', [DatabaseController::class, 'storeUser'])->name('users.store');
	Route::delete('/pengguna/{id}', [DatabaseController::class, 'deleteUser'])->name('users.delete');

	Route::get('/siswa', [DatabaseController::class, 'students'])->name('students.index');
	Route::get('/siswa/template', [DatabaseController::class, 'studentTemplate'])->name('students.template');
	Route::post('/siswa', [DatabaseController::class, 'storeStudent'])->name('students.store');
	Route::post('/siswa/import', [DatabaseController::class, 'importStudents'])->name('students.import');
	Route::patch('/siswa/{id}/mutasi', [DatabaseController::class, 'archiveStudent'])->name('students.archive');
	Route::delete('/siswa/{id}', [DatabaseController::class, 'deleteStudent'])->name('students.delete');

	Route::get('/jadwal-piket', [DatabaseController::class, 'schedule'])->name('schedule.index');
	Route::post('/jadwal-piket', [DatabaseController::class, 'storeSchedule'])->name('schedule.store');
	Route::post('/jadwal-piket/import', [DatabaseController::class, 'importSchedule'])->name('schedule.import');
	Route::patch('/jadwal-piket/{id}', [DatabaseController::class, 'updateSchedule'])->name('schedule.update');
	Route::get('/jadwal-piket/template', [DatabaseController::class, 'scheduleTemplate'])->name('schedule.template');

	Route::get('/gedung', [DatabaseController::class, 'buildings'])->name('buildings.index');
	Route::post('/gedung', [DatabaseController::class, 'storeBuilding'])->name('buildings.store');
	Route::get('/gedung/{id}/edit', [DatabaseController::class, 'editBuilding'])->name('buildings.edit');
	Route::put('/gedung/{id}', [DatabaseController::class, 'updateBuilding'])->name('buildings.update');
	Route::delete('/gedung/{id}', [DatabaseController::class, 'deleteBuilding'])->name('buildings.delete');

	Route::get('/toilet', [DatabaseController::class, 'toilets'])->name('toilets.index');
	Route::post('/toilet', [DatabaseController::class, 'storeToilet'])->name('toilets.store');
	Route::get('/toilet/{id}/edit', [DatabaseController::class, 'editToilet'])->name('toilets.edit');
	Route::put('/toilet/{id}', [DatabaseController::class, 'updateToilet'])->name('toilets.update');
	Route::delete('/toilet/{id}', [DatabaseController::class, 'deleteToilet'])->name('toilets.delete');

	Route::get('/histori', [DatabaseController::class, 'history'])->name('history.index');
	Route::get('/histori/{id}/edit', [DatabaseController::class, 'editHistory'])->name('history.edit');
	Route::put('/histori/{id}', [DatabaseController::class, 'updateHistory'])->name('history.update');
	Route::delete('/histori/{id}', [DatabaseController::class, 'deleteHistory'])->name('history.delete');
});
