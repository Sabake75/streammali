<?php

namespace App\Domain\Viewer\Actions;

use App\Enums\UserRole;
use App\Models\User;

class RegisterViewer
{
    public function __invoke(string $name, string $phone, string $password): User
    {
        $user = User::create([
            'name' => $name,
            'phone' => $phone,
            'password' => $password,
            // Only reached once the controller's `accepted` validation rule
            // has passed, so acceptance is implicit at this point.
            'terms_accepted_at' => now(),
        ]);

        // 'role' is deliberately not mass-assignable (see User model) —
        // forceFill is the trusted, server-only path to set it.
        $user->forceFill(['role' => UserRole::Viewer])->save();

        return $user;
    }
}
