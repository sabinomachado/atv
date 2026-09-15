<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;

test('seeds the admin accounts', function () {
    $this->seed(AdminUserSeeder::class);

    expect(User::where('email', 'sabino_machado@outlook.com')->exists())->toBeTrue()
        ->and(User::where('email', 'roliveto22@yahoo.com.br')->exists())->toBeTrue();
});

test('uses the shared password from ADMIN_DEFAULT_PASSWORD when set', function () {
    putenv('ADMIN_DEFAULT_PASSWORD=super-secret-123');

    $this->seed(AdminUserSeeder::class);

    $admin = User::where('email', 'sabino_machado@outlook.com')->firstOrFail();

    expect(Hash::check('super-secret-123', $admin->password))->toBeTrue();

    putenv('ADMIN_DEFAULT_PASSWORD');
});

test('running the seeder again does not touch an existing admin', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::where('email', 'sabino_machado@outlook.com')->firstOrFail();
    $admin->update(['name' => 'Nome Alterado Pelo Admin']);

    $this->seed(AdminUserSeeder::class);

    expect(User::where('email', 'sabino_machado@outlook.com')->count())->toBe(1)
        ->and($admin->fresh()->name)->toBe('Nome Alterado Pelo Admin');
});
