<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\AccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccessSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_users_do_not_keep_the_default_password_or_reset_changed_passwords(): void
    {
        foreach (['HQ', 'ABJ', 'PH'] as $code) {
            Branch::query()->create([
                'code' => $code,
                'name' => $code,
            ]);
        }

        app(AccessSeeder::class)->run();

        foreach (User::query()->get() as $user) {
            $this->assertFalse(Hash::check('password', $user->password));
        }

        $administrator = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $administrator->password = Hash::make('password');
        $administrator->save();

        app(AccessSeeder::class)->run();

        $administrator->refresh();
        $this->assertFalse(Hash::check('password', $administrator->password));
        $passwordHash = $administrator->password;

        app(AccessSeeder::class)->run();

        $this->assertSame($passwordHash, $administrator->refresh()->password);
    }
}
