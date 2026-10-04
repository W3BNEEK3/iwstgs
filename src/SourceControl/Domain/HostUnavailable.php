<?php
namespace Src\SourceControl\Domain;

/** GitHub isn't configured, refused, or couldn't be reached. Callers show a friendly message and retry later. */
final class HostUnavailable extends \RuntimeException {}
