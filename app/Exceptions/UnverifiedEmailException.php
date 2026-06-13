<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class UnverifiedEmailException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Your email address has not been verified by the identity provider. Please verify your email and try again.');
    }
}
