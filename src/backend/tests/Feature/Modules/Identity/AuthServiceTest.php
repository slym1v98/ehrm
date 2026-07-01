<?php

namespace Tests\Feature\Modules\Identity;

use App\Modules\Identity\Application\Services\AuthenticationService;
use App\Modules\Identity\Domain\Exceptions\InvalidCredentialsException;
use App\Modules\Identity\Domain\Exceptions\InvalidPasswordException;
use App\Modules\Identity\Domain\Exceptions\UserDisabledException;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_sanctum_token(): void
    {
        UserModel::create([
            'name' => 'Admin',
            'email' => 'admin@ihrm.local',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $result = app(AuthenticationService::class)->login('admin@ihrm.local', 'password123');

        $this->assertSame('Bearer', $result['token_type']);
        $this->assertNotEmpty($result['access_token']);
    }

    public function test_invalid_credentials_throw(): void
    {
        $this->expectException(InvalidCredentialsException::class);
        app(AuthenticationService::class)->login('missing@ihrm.local', 'bad');
    }

    public function test_disabled_user_cannot_login(): void
    {
        UserModel::create([
            'name' => 'Disabled',
            'email' => 'disabled@ihrm.local',
            'password' => Hash::make('password123'),
            'status' => 'disabled',
        ]);

        $this->expectException(UserDisabledException::class);
        app(AuthenticationService::class)->login('disabled@ihrm.local', 'password123');
    }

    public function test_change_password_validates_current_password(): void
    {
        $user = UserModel::create([
            'name' => 'User',
            'email' => 'user@ihrm.local',
            'password' => Hash::make('old-password'),
            'status' => 'active',
        ]);

        $this->expectException(InvalidPasswordException::class);
        app(AuthenticationService::class)->changePassword($user, 'wrong', 'new-password');
    }
}
