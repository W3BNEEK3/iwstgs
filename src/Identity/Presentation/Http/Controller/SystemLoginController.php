<?php

namespace Src\Identity\Presentation\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Src\Identity\Application\Command\LoginUser\LoginUserCommand;
use Src\Identity\Domain\Exceptions\InvalidCredentialsException;
use Src\Identity\Presentation\Http\Request\LoginRequest;
use Src\Shared\Application\Bus\CommandBus;

/**
 * SystemLoginController
 *
 * Handles authentication for the system-level super_admin portal (/sys/login).
 * Only super_admin accounts may sign in here — all other roles are rejected.
 * This route is intentionally separate from /login (used by org admins / content authors)
 * so that the system administration surface is not publicly discoverable.
 */
class SystemLoginController
{
    public function __construct(private readonly CommandBus $commandBus) {}

    public function show(): View
    {
        return view('identity.system-login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new LoginUserCommand(
                email:    $request->input('email'),
                password: $request->input('password'),
                remember: $request->boolean('remember'),
            ));
        } catch (InvalidCredentialsException) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        /** @var \Src\Identity\Infrastructure\Persistence\Eloquent\Model\UserModel|null $user */
        $user = Auth::user();

        // Only super_admin accounts are permitted through this portal
        if (!$user || !$user->hasRole('super_admin')) {
            Auth::logout();
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Access denied. This portal is for system administrators only.']);
        }

        return redirect()->intended('/admin/projects');
    }

    public function destroy(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('sys.login');
    }
}
