<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateStaffUser extends Command
{
    protected $signature = 'resikkita:create-staff-user {username?} {--name=} {--role=} {--generate-password}';

    protected $description = 'Create the first or another ResikKita staff account';

    public function handle(): int
    {
        $username = trim((string) ($this->argument('username') ?: $this->ask('Username')));
        if ($username === '') {
            $this->error('Username wajib diisi.');

            return self::FAILURE;
        }
        if (User::where('username', $username)->exists()) {
            $this->error('Username tersebut sudah digunakan.');

            return self::FAILURE;
        }

        $name = trim((string) ($this->option('name') ?: $this->ask('Nama lengkap')));
        if ($name === '') {
            $this->error('Nama lengkap wajib diisi.');

            return self::FAILURE;
        }

        $roles = ['Admin', 'Guru', 'Kesiswaan'];
        $role = $this->option('role') ?: $this->choice('Role staf', $roles, 'Admin');
        if (! in_array($role, $roles, true)) {
            $this->error('Role harus Admin, Guru, atau Kesiswaan.');

            return self::FAILURE;
        }

        if ($this->option('generate-password')) {
            $password = Str::password(20, true, true, false);
        } else {
            $password = $this->secret('Password (minimal 8 karakter)');
            $confirmation = $this->secret('Ulangi password');
            if (strlen((string) $password) < 8 || $password !== $confirmation) {
                $this->error('Password harus minimal 8 karakter dan kedua input harus sama.');

                return self::FAILURE;
            }
        }

        User::create([
            'nama_lengkap' => $name,
            'username' => $username,
            'password' => $password,
            'role' => $role,
        ]);

        $this->info("Akun {$role} {$username} berhasil dibuat.");
        if ($this->option('generate-password')) {
            $this->line('Password awal (catat sekarang): '.$password);
        }

        return self::SUCCESS;
    }
}