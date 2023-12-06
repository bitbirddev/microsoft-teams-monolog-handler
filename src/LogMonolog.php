<?php

namespace bitbirddev\MicrosoftTeamsNotifier;

use bitbirddev\MicrosoftTeamsNotifier\Handler\MicrosoftTeamsHandler;
use Monolog\Logger;

class LogMonolog
{
    /**
     * @param array $config
     *
     * @return Logger
     */
    public function __invoke(array $config): Logger
    {
        return new Logger(
            $config['title'],
            [new MicrosoftTeamsHandler(
                $config['webhookDsn'],
                $config['level'],
                $config['title'],
                $config['subject'],
                $config['emoji'],
                $config['color'],
                $config['format']
            )
            ]
        );
    }
}
