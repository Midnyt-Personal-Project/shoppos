<?php

use Native\Desktop\Facades\ChildProcess;

class ExternalApiServer
{
    public static function start()
    {
        ChildProcess::start(
            cmd: 'php -S 0.0.0.0:9100 external-api-server.php',
            alias: 'external-api'
        );
    }

    public static function stop()
    {
        ChildProcess::stop('external-api');
    }
}