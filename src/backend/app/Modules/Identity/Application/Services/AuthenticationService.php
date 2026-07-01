<?php

namespace App\Modules\Identity\Application\Services;

use App\Modules\Identity\Domain\Aggregates\User\Email;
use App\Modules\Identity\Domain\Aggregates\User\UserId;
use App\Modules\Identity\Domain\Events\UserLoggedIn;
use App\Modules\Identity\Domain\Events\UserLoginFailed;
use App\Modules\Identity\Domain\Exceptions\InvalidCredentialsException;
use App\Modules\Identity\Domain\Exceptions\InvalidPasswordException;
use App\Modules\Identity\Domain\Exceptions\UserDisabledException;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserModel;
use DateTimeImmutable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

class AuthenticationService
{
    /** @return array{access_token: string, token_type: string, user: UserModel} */
    public function login(string $email, string $password): array
    {
        $emailValue = mb_strtolower(trim($email));
        $user = UserModel::where('email', $emailValue)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            Event::dispatch(new UserLoginFailed(Email::fromString($emailValue), 'Invalid credentials', new DateTimeImmutable()));
            throw new InvalidCredentialsException('Invalid credentials');
        }

        if ($user->status !== 'active') {
            Event::dispatch(new UserLoginFailed(Email::fromString($emailValue), 'User is disabled', new DateTimeImmutable()));
            throw new UserDisabledException('User is disabled');
        }

        $user->last_login_at = now();
        $user->save();
        Event::dispatch(new UserLoggedIn(UserId::fromString($user->id), new DateTimeImmutable()));

        return [
            'access_token' => $user->createToken('api')->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $user,
        ];
    }

    public function logout(UserModel $user): void
    {
        $token = $user->currentAccessToken();
        if ($token) {
            $token->delete();
        }
    }

    public function changePassword(UserModel $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new InvalidPasswordException('Current password is invalid');
        }

        $user->password = Hash::make($newPassword);
        $user->save();
    }
}
