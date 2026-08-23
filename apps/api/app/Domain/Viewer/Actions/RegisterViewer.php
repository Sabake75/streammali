<?php

namespace App\Domain\Viewer\Actions;

use App\Enums\UserRole;
use App\Models\User;

class RegisterViewer
{
    public function __invoke(string $name, string $phone, string $password): User
    {
        return User::create([
            'name' => $name,
            'phone' => $phone,
            'password' => $password,
            'role' => UserRole::Viewer,
            // Only reached once the controller's `accepted` validation rule
            // has passed, so acceptance is implicit at this point.
            'terms_accepted_at' => now(),
        ]);
    }
}
