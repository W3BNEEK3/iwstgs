<?php
namespace Src\Shared\Presentation\Http\Controller\Admin;

use Illuminate\View\View;
use Src\Shared\Domain\Feature\FeatureFlagRepository;

class FeatureFlagController
{
    public function __construct(private readonly FeatureFlagRepository $flags) {}

    public function index(): View
    {
        return view('admin.feature-flags.index', ['flags' => $this->flags->all()]);
    }

    public function toggle(string $key): View
    {
        $flag = $this->flags->toggle($key); // flips is_enabled, returns the updated flag
        return view('admin.feature-flags._row', ['flag' => $flag]);
    }
}
