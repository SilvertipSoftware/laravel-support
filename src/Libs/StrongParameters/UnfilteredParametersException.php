<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Libs\StrongParameters;

use Exception;

class UnfilteredParametersException extends Exception {

    public function __construct() {
        parent::__construct('Unable to use unpermitted parameters');
    }
}
