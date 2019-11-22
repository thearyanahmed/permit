<?php

namespace Prophecy\Permit\Exceptions;

use Throwable;

class PermissionNotFoundException extends \Exception {
    public function __construct($message = "Permission not found.", $code = 404, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
