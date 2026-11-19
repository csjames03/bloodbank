<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class HttpException extends RuntimeException
{
    public function __construct(public readonly int $status, string $message = '')
    {
        parent::__construct($message, $status);
    }

    public static function notFound(string $message = 'Page not found'): self
    {
        return new self(404, $message);
    }

    public static function forbidden(string $message = 'You do not have access to this page'): self
    {
        return new self(403, $message);
    }
}
