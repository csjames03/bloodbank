<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * A business-rule violation whose message is safe to show to the user
 * (e.g. "Only 2 bags of O- are available"). `$field` names the form field
 * the problem belongs to, when there is one.
 */
final class DomainException extends \RuntimeException
{
    public function __construct(string $message, public readonly ?string $field = null)
    {
        parent::__construct($message);
    }
}
