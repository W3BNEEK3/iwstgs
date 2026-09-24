<?php

namespace Src\Shared\Domain;

/**
 * Base class for domain exceptions.
 *
 * A DomainException signals a business rule violation — "this operation is not
 * allowed by the domain." It is distinct from a system/infrastructure exception.
 *
 * Example: Attempting to assign an architectural task to a Junior-rank learner
 * should throw a DomainException, not a generic RuntimeException, because the
 * cause is a violated business rule, not a system failure.
 */
abstract class DomainException extends \RuntimeException {}
