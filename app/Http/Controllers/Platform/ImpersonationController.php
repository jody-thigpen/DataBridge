<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Http\RedirectResponse;

class ImpersonationController extends Controller
{
    public function store(User $user, ImpersonationService $impersonation): RedirectResponse
    {
        $impersonation->start(auth()->user(), $user);

        return redirect()->route('dashboard')->with('status', "Support session started as {$user->name}.");
    }

    public function destroy(ImpersonationService $impersonation): RedirectResponse
    {
        $impersonation->stop();

        return redirect()->route('platform.clients.index')->with('status', 'Support session ended.');
    }
}
