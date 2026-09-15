<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Panel admins to provision. Only used to create the account the first
     * time — an existing user's name/password is never touched here, so
     * this is safe to run on every deploy.
     */
    private const ADMINS = [
        'sabino_machado@outlook.com' => 'Sabino Machado',
        'roliveto22@yahoo.com.br' => 'Roliveto',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sharedPassword = env('ADMIN_DEFAULT_PASSWORD');

        foreach (self::ADMINS as $email => $name) {
            if (User::where('email', $email)->exists()) {
                continue;
            }

            $password = $sharedPassword ?: Str::password(16);

            User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            if (blank($sharedPassword)) {
                $this->command?->warn("Admin criado: {$email} / senha gerada: {$password} (troque após o primeiro login).");
            } else {
                $this->command?->info("Admin criado: {$email} (senha definida em ADMIN_DEFAULT_PASSWORD).");
            }
        }
    }
}
