<?php
namespace Src\SourceControl\Application\Service;

final class LinkOutcome
{
    public const LINKED = 'linked';
    public const NOT_CONNECTED = 'not_connected';
    public const NOT_INSTALLED = 'not_installed';
    public const NOTHING_WAITING = 'nothing_waiting';
    public const NO_MATCH = 'no_match';
    public const NOT_FROM_TEMPLATE = 'not_from_template';

    /** @param string[] $repos repositories that look right but weren't created from the template */
    public function __construct(
        public readonly string $result,
        public readonly int $linked = 0,
        public readonly array $repos = [],
    ) {}

    /** What to tell the learner. */
    public function message(): string
    {
        return match ($this->result) {
            self::LINKED            => 'Your repository is linked. You can start on the first milestone.',
            self::NOT_CONNECTED     => 'Connect your GitHub account first.',
            self::NOT_INSTALLED     => 'Areyna isn\'t installed on your GitHub account yet. Use "Give Areyna access" and choose your project\'s repository.',
            self::NOTHING_WAITING   => 'Your projects already have their repositories.',
            self::NOT_FROM_TEMPLATE => 'We found ' . implode(', ', $this->repos) . ' but it wasn\'t created from the project template, so it\'s missing the starter code and tests. Please create a new repository with "Create my repo".',
            default                 => 'We can see your GitHub account but not a repository made from this project\'s template. Create it with "Create my repo", then make sure Areyna has access to it.',
        };
    }
}
