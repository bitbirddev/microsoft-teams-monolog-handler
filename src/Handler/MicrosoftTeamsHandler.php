<?php

declare(strict_types=1);

namespace bitbirddev\MicrosoftTeamsNotifier\Handler;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Psr\Log\LogLevel;

class MicrosoftTeamsHandler extends AbstractProcessingHandler
{
    /**
     * MicrosoftTeams Webhook DSN
     *
     * @var string
     */
    private string $webhookDsn;

    /**
     * Instance of the MicrosoftTeamsRecord
     *
     * @var MicrosoftTeamsRecord
     */
    private MicrosoftTeamsRecord $microsoftTeamsRecord;

    /**
     * Format of the message
     *
     * @var string|null
     */
    private ?string $format;

    /**
     * @param string $webhookDsn
     * @param int|string|Level $level
     * @param string $title
     * @param string $subject
     * @param string|null $emoji
     * @param string|null $color
     * @param string|null $format
     * @param bool $bubble
     *
     * @phpstan-param value-of<Level::VALUES>|value-of<Level::NAMES>|Level|LogLevel::* $level
     */
    public function __construct(
        string $webhookDsn,
        int|string|Level $level = Level::Debug,
        string $title = 'Message',
        string $subject = 'Date',
        ?string $emoji = 'auto',
        ?string $color = null,
        ?string $format = '%message%',
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);

        $this->webhookDsn = $webhookDsn;
        $this->format = $format;
        $this->microsoftTeamsRecord = new MicrosoftTeamsRecord($title, $subject, $emoji, $color);
    }

    /**
     * {@inheritdoc}
    * @throws \RuntimeException
     */
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new LineFormatter($this->format, 'Y-m-d H:i:s', false, true);
    }

    /**
     * @return string
     */
    public function getWebhookDsn(): string
    {
        return $this->webhookDsn;
    }

    /**
     * @return MicrosoftTeamsRecord
     */
    public function getMicrosoftTeamsRecord(): MicrosoftTeamsRecord
    {
        return $this->microsoftTeamsRecord;
    }

    /**
     * Writes the (already formatted) record down to the log of the implementing handler
     *
     * @throws \RuntimeException
     */
    protected function write(LogRecord $record): void
    {
        $postData = $this->microsoftTeamsRecord->setData($record)->getData();
        $dataString = json_encode($postData);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->webhookDsn);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']);

        $this->execute($ch);
    }

    /**
     * @param \CurlHandle $ch
     * @param int $repeat
     *
     * @return bool|string
     *
     * @throws \RuntimeException
     */
    public static function execute(\CurlHandle $ch, int $repeat = 3): bool|string
    {
        while ($repeat--) {
            $response = curl_exec($ch);

            if (false === $response) {
                if (!$repeat) {
                    $errno = curl_errno($ch);
                    $error = curl_error($ch);

                    throw new \RuntimeException(sprintf('Curl error %d: %s', $errno, $error));
                }

                continue;
            }

            curl_close($ch);

            return $response;
        }

        return false;
    }
}
