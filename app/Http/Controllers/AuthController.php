<?php

namespace App\Http\Controllers;

use App\Models\TokenAkses;
use App\Services\ResikKitaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        protected ResikKitaService $service,
    ) {}

    public function index(): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return redirect()->route(session()->has('petugas.token_id') ? 'petugas.dashboard' : 'login');
    }

    public function login(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        if (session()->has('petugas.token_id')) {
            return redirect()->route('petugas.dashboard');
        }

        return view('auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()->withErrors(['username' => 'Username atau kata sandi tidak cocok.'])->withInput($request->only('username'));
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))->with('success', 'Berhasil masuk ke panel ResikKita.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Kamu sudah keluar dari panel.');
    }

    public function loginStudent(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'token' => ['required', 'string', 'max:30'],
            'team' => ['required', 'integer', 'between:1,4'],
        ]);

        $token = TokenAkses::query()
            ->where('kode_token', Str::upper(trim($input['token'])))
            ->whereDate('tanggal', today())
            ->where('status', 'Aktif')
            ->latest('id')
            ->first();

        if (! $token || ! $this->service->tokenIsValid($token)) {
            return back()->withErrors(['token' => 'Token tidak aktif untuk hari ini. Minta kode terbaru dari guru.'])->withInput();
        }

        $team = (int) $input['team'];
        $schedule = $this->service->activeScheduleForTeam($token, $team);
        if (! $schedule) {
            return back()->withErrors(['team' => "Belum ada jadwal untuk Regu {$team} pada hari ini."])->withInput();
        }

        $request->session()->regenerate();
        $request->session()->put(['petugas.token_id' => $token->id, 'petugas.regu' => $team]);

        return redirect()->route('petugas.dashboard')->with('success', 'Token aktif. Tugas regu kamu sudah dibuka.');
    }

    public function logoutStudent(Request $request): RedirectResponse
    {
        $request->session()->forget(['petugas.token_id', 'petugas.regu']);
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Akses regu sudah ditutup.');
    }
}
