<?php

namespace Prophecy\Permit\Exceptions;

use Throwable;

class RoleNotFoundException extends \Exception {
    public function __construct($message = "Role not found.", $code = 404, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
