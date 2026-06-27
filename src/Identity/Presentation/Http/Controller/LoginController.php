<?php

namespace Src\Identity\Presentation\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Src\Identity\Application\Command\LoginUser\LoginUserCommand;
use Src\Identity\Domain\Exception\InvalidCredentialsException;
use Src\Identity\Presentation\Http\Request\LoginRequest;
use Src\Shared\Application\Bus\CommandBus;

class LoginController
{
    public function __construct(private readonly CommandBus $commandBus) {}

    public function show(): View
    {
        return view('identity.login');
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

        return redirect()->intended('/learn');
    }

    public function destroy(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    }
}