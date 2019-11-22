<?php

namespace Prophecy\Permit\Exceptions;

use Throwable;

class ModuleNotFoundException extends \Exception {
    public function __construct($message = "Module not found.", $code = 402, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
