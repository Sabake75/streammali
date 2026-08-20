<?php

namespace App\Domain\Creator\Actions;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class RegisterCreator
{
    public function __invoke(string $name, string $phone, string $password, UploadedFile $identityDocument): User
    {
        // Private disk (storage/app/private) — never publicly served, see
        // config/filesystems.php. Only downloadable by an authenticated
        // moderator, via the route in routes/web.php.
        $path = $identityDocument->store('identity-documents', 'local');

        return User::create([
            'name' => $name,
            'phone' => $phone,
            'password' => $password,
            'role' => UserRole::Creator,
            'identity_document_path' => $path,
        ]);
    }
}
