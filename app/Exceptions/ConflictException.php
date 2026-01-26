<?php

namespace App\Exceptions;

class ConflictException extends \Exception
{
    public function __construct(string $message = 'state conflict')
    {
        parent::__construct($message, 409);
    }
}
