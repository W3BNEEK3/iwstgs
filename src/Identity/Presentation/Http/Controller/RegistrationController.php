<?php

namespace Src\Identity\Presentation\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Src\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use Src\Identity\Domain\Exception\EmailAlreadyTakenException;
use Src\Identity\Presentation\Http\Request\RegisterRequest;
use Src\Shared\Application\Bus\CommandBus;

class RegistrationController
{
    public function __construct(private readonly CommandBus $commandBus) {}

    public function show(): View
    {
        return view('identity.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new RegisterUserCommand(
                name:     $request->input('name'),
                email:    $request->input('email'),
                password: $request->input('password'),
            ));
        } catch (EmailAlreadyTakenException $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        }

        return redirect()->route('login')
            ->with('success', 'Account created. Please log in.');
    }
}