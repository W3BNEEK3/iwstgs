<?php
namespace Src\SourceControl\Presentation\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Src\Shared\Application\Bus\CommandBus;
use Src\SourceControl\Application\Command\ConnectGitHubAccount\ConnectGitHubAccountCommand;
use Src\SourceControl\Application\Command\DisconnectGitHub\DisconnectGitHubCommand;
use Src\SourceControl\Application\Service\LinkOutcome;
use Src\SourceControl\Application\Service\RepositoryLinker;
use Src\SourceControl\Domain\GitHubAccountInUse;
use Src\SourceControl\Domain\HostUnavailable;
use Src\SourceControl\Domain\RepositoryHost;

/**
 * Connect GitHub (identify the learner's account), send them to install the
 * App, and "Check again" to link the repository they created.
 */
class GitHubConnectController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly RepositoryHost $host,
        private readonly RepositoryLinker $linker,
    ) {}

    public function connect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('github.oauth_state', $state);
        $request->session()->put('github.return_to', $this->safeReturn($request->query('return')));

        return redirect()->away($this->host->authorizeUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        $returnTo = $request->session()->pull('github.return_to', route('learn.catalogue'));
        $expected = $request->session()->pull('github.oauth_state');

        if (! is_string($expected) || ! hash_equals($expected, (string) $request->query('state')) || ! $request->filled('code')) {
            return redirect($returnTo)->with('error', 'GitHub sign-in didn\'t complete. Please try "Connect GitHub" again.');
        }

        try {
            $this->commandBus->dispatch(new ConnectGitHubAccountCommand((string) Auth::id(), (string) $request->query('code')));
        } catch (GitHubAccountInUse $e) {
            return redirect($returnTo)->with('error', $e->getMessage());
        } catch (HostUnavailable $e) {
            Log::warning("GitHub connect failed: {$e->getMessage()}");

            return redirect($returnTo)->with('error', 'We couldn\'t reach GitHub. Please try again in a minute.');
        }

        // If the App was installed first, the repository may be linkable already.
        $this->tryLink();

        return redirect($returnTo)->with('success', 'GitHub connected.');
    }

    public function install(): RedirectResponse
    {
        return redirect()->away($this->host->installUrl());
    }

    public function check(Request $request): RedirectResponse
    {
        $outcome = $this->tryLink();

        return back()->with($outcome?->result === LinkOutcome::LINKED ? 'success' : 'info',
            $outcome?->message() ?? 'We couldn\'t reach GitHub. Please try again in a minute.');
    }

    public function disconnect(): RedirectResponse
    {
        $this->commandBus->dispatch(new DisconnectGitHubCommand((string) Auth::id()));

        return back()->with('success', 'GitHub disconnected. Reconnect any time to carry on with your builds.');
    }

    private function tryLink(): ?LinkOutcome
    {
        try {
            return $this->linker->linkForUser((string) Auth::id());
        } catch (HostUnavailable $e) {
            Log::warning("Repository linking failed: {$e->getMessage()}");

            return null;
        }
    }

    /** Only ever return to a path on this site. */
    private function safeReturn(mixed $return): string
    {
        return is_string($return) && preg_match('#^/(?!/)#', $return) ? $return : route('learn.catalogue');
    }
}
