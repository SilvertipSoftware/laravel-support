<?php

namespace SilvertipSoftware\LaravelSupport\Libs\StrongParameters;

use Exception;

class ParameterMissingException extends Exception {

    public function __construct($key) {
        parent::__construct('Parameter is missing or the value is empty: ' . $key);
    }
}
