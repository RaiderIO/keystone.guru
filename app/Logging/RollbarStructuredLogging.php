<?php

namespace App\Logging;

abstract class RollbarStructuredLogging extends StructuredLogging
{
    public function __construct()
    {
        parent::__construct();

//        $this->addLogger(
//            Rollbar::logger()
//        );
    }
}
