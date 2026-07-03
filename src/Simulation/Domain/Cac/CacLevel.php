<?php
namespace Src\Simulation\Domain\Cac;

enum CacLevel: string
{
    case Low  = 'low';
    case Mid  = 'mid';
    case High = 'high';
}
