<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('make:admin-user {email} {--password=} {--name=Admin}', function () {
    $email = $this->argument('email');
    $name = $this->option('name') ?: 'Admin';
    $password = $this->option('password') ?? $this->secret('Password (min 8 characters)');
    if (strlen($password) < 8) {
        $this->error('Password must be at least 8 characters.');
        return 1;
    }
    if (User::where('email', $email)->exists()) {
        $this->warn("User {$email} already exists.");
        return 0;
    }
    User::create([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make($password),
    ]);
    $this->info("Admin created: {$email}");
    return 0;
})->purpose('Create an admin user for Filament (e.g. on production)');
