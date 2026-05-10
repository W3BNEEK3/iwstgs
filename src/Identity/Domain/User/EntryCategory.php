<?php
namespace Src\Identity\Domain\User;

enum EntryCategory: string
{
    case Inexperienced = "inexperienced";
    case Experienced = "experienced";
}