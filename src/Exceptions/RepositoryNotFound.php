<?php

namespace Hsntngr\Repository\Exceptions;

use Exception;

class RepositoryNotFound extends Exception
{
    public function __construct(string $message = "")
    {
        parent::__construct($message);
    }
}
