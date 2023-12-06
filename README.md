# bitbirddev Microsoft Teams Monolog Handler

![Package Version](https://img.shields.io/badge/Version-1.2.0-brightgreen.svg)

A PHP package that defines custom Monolog handler to send Microsoft Teams notifications with an Incoming Webhook.
The package aims to provide global messaging & log system that uses Microsoft Teams "MessageCard" notification and uses Monolog logging library.

# Features

- Monolog wiring with Microsoft Teams channel
- Application error notifying
- Simple messaging

# Install

```sh
$ composer require bitbirddev/microsoft-teams-monolog-handler
```

Please consider running `composer suggest` command to install required and missing dependencies related to framework you use (ex. Symfony):

```sh
$ composer suggest
bitbirddev/microsoft-teams-monolog-handler suggests:
 - symfony/monolog-bundle: The MonologBundle provides integration of the Monolog library into the Symfony framework.
```

# Microsoft Teams Webhook setting

Follow these steps to set up new Webhook:

- In Microsoft Teams, choose More options (⋯) next to the channel name and then choose 'Connectors'
- Find in the list of Connectors the 'Incoming Webhook' option, and choose 'Add'
- Provide required information for the new Webhook
- Copy the Webhook url - that information will be used to configure the package with `MICROSOFT_TEAMS_WEBHOOK_URL`

# Symfony configuration

Place the code below in `.env` file:

```yaml
###> bitbirddev/microsoft-teams-monolog-handler ###
MICROSOFT_TEAMS_WEBHOOK_URL=webhook_url (without https://)
###< bitbirddev/microsoft-teams-monolog-handler ###
```

Register `MicrosoftTeamsMonologHandler.php` as a new service with the code below:

```diff
// config\services.yaml

services:
    ...

    # MICROSOFT TEAMS MONOLOG HANDLER
+    ms_teams_monolog_handler:
+        class: bitbirddev\MicrosoftTeamsNotifier\Handler\MicrosoftTeamsHandler
+        arguments:
+            $webhookDsn: '%https://env(MICROSOFT_TEAMS_WEBHOOK_URL)%'
+            $level: 'error'
+            $title: 'Message title'
+            $subject: 'Message subject'
+            $emoji:  '&#x1F6A8'
+            $color: '#fd0404'
+            $format: '[%%datetime%%] %%channel%%.%%level_name%%: %%message%%'
```

> _$webhookDsn:_  
> Microsoft Teams webhook url
>
> _$level:_  
> the minimum level for handler to be triggered and the message be logged in the channel (Monolog/Logger class: ‘error’ = 400)
>
> _$title (nullable):_  
> title of Microsoft Teams Message
>
> _$subject (nullable):_  
> subject of Microsoft Teams Message
>
> _$emoji (nullable):_  
> emoji of Microsoft Teams Message (displayed next to the message title). Value needs to reflect the pattern: ‘&#x<EMOJI_HEX_CODE>’
>
> _$color (nullable):_  
> hexadecimal color value for Message Card color theme
>
> _$format (nullable):_  
> every handler uses a Formatter to format the record before logging it. This attribute can be set to overwrite default log message (available options: %datetime% | %extra.token% | %channel% | %level_name% | %message%).

Modify your Monolog settings that will point from now to the new handler:

```diff
// config\packages\dev\monolog.yaml
// config\packages\prod\monolog.yaml

monolog:
    handlers:
        ...

        # MICROSOFT TEAMS HANDLER
+        teams:
+            type: service
+            id: ms_teams_monolog_handler
```

> _type:_  
> handler type (in our case this references custom notifier service)
>
> _id:_  
> notifier service class \bitbirddev\MicrosoftTeamsNotifier\LogMonolog

# Laravel configuration

Place the code below in `.env` file:

```yaml
###> bitbirddev/microsoft-teams-monolog-handler ###
MICROSOFT_TEAMS_WEBHOOK_URL=webhook_url (without https://)
###< bitbirddev/microsoft-teams-monolog-handler ###
```

Modify your Monolog logging settings that will point to the new handler:

### Att: definition of ALL parameters is compulsory - please use NULL value for attributes you want to skip.

```diff
// config\logging.php

<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [
    'channels' => [
        'stack' => [
            'driver' => 'stack',
-            'channels' => ['single'],
+            'channels' => ['single', 'custom'],
            'ignore_exceptions' => false
        ],

         # MICROSOFT TEAMS MONOLOG HANDLER
+       'custom' => [
+            'driver' => 'custom',
+            'via' => \bitbirddev\MicrosoftTeamsNotifier\LogMonolog::class,
+            'webhookDsn' => 'https://env('MICROSOFT_TEAMS_WEBHOOK_URL)',
+            'level'  => env('LOG_LEVEL', 'debug'), // or simply 'debug'
+            'title'  => 'Message Title', // can be NULL
+            'subject'  => 'Message Subject', // can be NULL
+            'emoji'  => '&#x1F3C1', // can be NULL
+            'color'  => '#fd0404', // can be NULL
+            'format' => '[%datetime%] %channel%.%level_name%: %message%' // can be NULL
+        ],

...
```

> _driver:_  
> is a crucial part of each channel that defines how and where the log message is recorded. The ‘custom’ driver calls a specified factory to create a channel.
>
> _via:_  
> factory class which will be invoked to create the Monolog instance
>
> _webhookDsn:_  
> Microsoft Teams webhook url
>
> _level:_  
> the minimum level for handler to be triggered and the message be logged in the channel (Monolog/Logger class: ‘debug’ = 100)
>
> _title (nullable):_  
> title of Microsoft Teams Message
>
> _subject (nullable):_  
> subject of Microsoft Teams Message
>
> _emoji (nullable):_  
> emoji of Microsoft Teams Message (displayed next to the message title). Value needs to reflect the pattern: ‘&#x<EMOJI_HEX_CODE>’
>
> _color (nullable):_  
> hexadecimal color value for Message Card color theme
>
> _format (nullable):_  
> message template - available options: %datetime% | %extra.token% | %channel% | %level_name% | %message%

# Usage

Correctly configured service in Symfony/Laravel will raise Logs in Microsoft Teams automatically accordingly to level assigned to.

### Symfony - manual messaging

```php
// LoggerInterface $logger
$logger->info('Info message with custom Handler');
$logger->error('Error message with custom Handler');
```

### Laravel - manual messaging

```php
// Illuminate\Support\Facades\Log
Log::channel('custom')->info('Info message with custom Handler');
Log::channel('custom')->error('Error message with custom Handler');
```

# License

The code is available under the MIT license. See the LICENSE file for more info.
