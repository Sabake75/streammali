<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

// Identity documents live on the private "local" disk and are never
// publicly served — only a logged-in moderator (session guard, same as
// the Filament panel) can download one. Checking Auth::check() manually
// instead of the `auth` middleware avoids it trying to redirect guests to
// a named `login` route that doesn't exist in this API-first app.
Route::get('/moderation/creators/{user}/identity-document', function (Request $request, User $user) {
    abort_unless(Auth::check(), 401);
    abort_unless(Auth::user()->role === UserRole::Moderator, 403);
    abort_if($user->identity_document_path === null, 404);

    return Storage::disk('local')->response($user->identity_document_path);
})->name('creators.identity-document');
