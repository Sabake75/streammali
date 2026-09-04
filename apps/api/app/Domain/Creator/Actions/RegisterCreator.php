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

        $user = User::create([
            'name' => $name,
            'phone' => $phone,
            'password' => $password,
            'identity_document_path' => $path,
            // Only reached once the controller's `accepted` validation rule
            // has passed, so acceptance is implicit at this point.
            'terms_accepted_at' => now(),
        ]);

        // 'role' is deliberately not mass-assignable (see User model) —
        // forceFill is the trusted, server-only path to set it. Without
        // this, the row would keep the users table's DB-level default
        // ('viewer'), not Creator.
        $user->forceFill(['role' => UserRole::Creator])->save();

        return $user;
    }
}
