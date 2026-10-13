<?php
namespace Src\SourceControl\Domain;

final class GitHubAccountInUse extends \DomainException
{
    public function __construct(string $login)
    {
        parent::__construct("The GitHub account {$login} is already connected to another Areyna account.");
    }
}
