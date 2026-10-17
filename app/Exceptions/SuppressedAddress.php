<?php

namespace App\Exceptions;

use RuntimeException;

/** The address bounced or marked us as spam before, so it must not be subscribed again. */
class SuppressedAddress extends RuntimeException
{
    public function __construct(public readonly string $email)
    {
        parent::__construct('This address can\'t receive email from us (it bounced or reported spam).');
    }
}
