<?php
namespace Src\Shared\Domain\Contract;

interface Module
{
    public function prefix(): string;
    public function name(): string;
    public function routePath(): string;
}