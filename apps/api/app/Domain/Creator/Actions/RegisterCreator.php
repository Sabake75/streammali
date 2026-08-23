<?php

namespace App\Domain\Creator\Actions;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class RegisterCreator
{
    public function __invoke(string $name, string $phone, string $password, UploadedFile $identityDocument): User
    {
        // Private disk (storage/app/private locally, S3-compatible in
        // production via FILESYSTEM_DISK=s3 — the container filesystem is
        // ephemeral and would silently lose these on every deploy
        // otherwise) — never publicly served, see config/filesystems.php.
        // Only downloadable by an authenticated moderator, via the route in
        // routes/web.php.
        $path = $identityDocument->store('identity-documents', config('filesystems.default'));

        return User::create([
            'name' => $name,
            'phone' => $phone,
            'password' => $password,
            'role' => UserRole::Creator,
            'identity_document_path' => $path,
            // Only reached once the controller's `accepted` validation rule
            // has passed, so acceptance is implicit at this point.
            'terms_accepted_at' => now(),
        ]);
    }
}
