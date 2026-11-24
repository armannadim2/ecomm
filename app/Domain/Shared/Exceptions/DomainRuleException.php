<?php

namespace App\Domain\Shared\Exceptions;

use RuntimeException;

/**
 * A business rule was broken. The message is safe to show to staff or
 * customers and should say what to do next.
 */
class DomainRuleException extends RuntimeException {}
