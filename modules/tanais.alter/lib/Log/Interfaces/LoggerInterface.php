<?php

namespace Tanais\Alter\Log\Interfaces;

interface LoggerInterface
{
    public function writeToLogFile($fileName, $message);

    public function log($message);
}

?>