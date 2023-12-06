<?php

declare(strict_types=1);

namespace bitbirddev\MicrosoftTeamsNotifier\Handler;

use Monolog\Level;
use Monolog\LogRecord;

class MicrosoftTeamsRecord
{
    /**
     * Massage colors
     */
    public const COLOR_DEBUG = '#CCCCCC';
    public const COLOR_INFO = '#00CC00';
    public const COLOR_NOTICE = '#CCCC00';
    public const COLOR_WARNING = '#FFCC00';
    public const COLOR_ERROR = '#FF6600';
    public const COLOR_CRITICAL = '#FF0000';
    public const COLOR_ALERT = '#CC0000';
    public const COLOR_EMERGENCY = '#990000';
    public const COLOR_DEFAULT = '#A6ACAF';

    /**
     * Massage emojis
     */
    public const EMOJI_DEBUG = '&#x1F3C1';
    public const EMOJI_INFO = '&#x1F3C1';
    public const EMOJI_NOTICE = '&#x1F3C1';
    public const EMOJI_WARNING = '&#x1F4E2';
    public const EMOJI_ERROR = '&#x1F4E2';
    public const EMOJI_CRITICAL = '&#x1F4E2';
    public const EMOJI_ALERT = '&#x1F6A8';
    public const EMOJI_EMERGENCY = '&#x1F6A8';
    public const EMOJI_DEFAULT = '&#x1F3C1';

    /**
     * Default Message settings
     */
    public const CARD_TYPE = 'MessageCard';
    public const CARD_CONTEXT = 'https://schema.org/extensions';

    /**
     * @var string
     */
    private string $title;

    /**
     * @var string
     */
    private string $subject;

    /**
     * @var string|null
     */
    private ?string $emoji;

    /**
     * @var string|null
     */
    private ?string $color;

    /**
     * @var array
     */
    private array $data = [];

    /**
     * MicrosoftTeamsRecord constructor
     *
     * @param string $title
     * @param string $subject
     * @param string|null $emoji
     * @param string|null $color
     */
    public function __construct(string $title, string $subject, ?string $emoji = 'auto', ?string $color = null)
    {
        $this->title = $title;
        $this->subject = $subject;
        $this->emoji = $emoji;
        $this->color = $color;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * Returns data in Microsoft Teams Card format.
     *
     * @param LogRecord $record
     *
     * @return self
     */
    public function setData(LogRecord $record): self
    {
        $formatted = '';

        if (is_string($record->formatted)) {
            $formatted = $record->formatted;
        }

        $this->setType()
            ->setContext()
            ->setThemeColor($record->level->value)
            ->setTitle($record->level->value)
            ->setText($formatted)
            ->setSections($record)
        ;

        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param string $type
     *
     * @return self
     */
    public function setType(string $type = self::CARD_TYPE): self
    {
        $this->data['type'] = $type;

        return $this;
    }

    /**
     * @param string $context
     *
     * @return self
     */
    public function setContext(string $context = self::CARD_CONTEXT): self
    {
        $this->data['context'] = $context;

        return $this;
    }

    /**
     * @param int $level
     *
     * @return self
     */
    public function setThemeColor(int $level): self
    {
        $this->data['themeColor'] = $this->color ? $this->color : $this->getThemeColor($level);

        return $this;
    }

    /**
     * @param int $level
     *
     * @return self
     */
    public function setTitle(int $level): self
    {
        if($this->emoji == 'auto') {
            $this->data['title'] = sprintf(
                '%s %s',
                $this->getEmoji($level),
                $this->title
            );
        } elseif($this->emoji) {
            $this->data['title'] = sprintf(
                '%s %s',
                $this->emoji,
                $this->title
            );
        }

        return $this;
    }

    /**
     * @param string $text
     *
     * @return self
     */
    public function setText(string $text): self
    {
        $this->data['text'] = $text;

        return $this;
    }

    /**
     * @param LogRecord $record
     *
     * @return self
     */
    public function setSections(LogRecord $record): self
    {
        $this->data['sections'] = [];

        foreach (['extra', 'context'] as $element) {
            if (empty($record->$element)) {
                continue;
            }

            $levelName = Level::fromValue($record->level->value)->getName();

            $facts = [$this->getFact('Level', $levelName)];
            array_push($facts, $this->getFact('Channel', $record->channel));
            array_push($facts, $this->getFact('Datetime', $record->datetime->format('d.m.Y / H:i:s')));

            foreach($record->$element as $key => $value) {
                if ($value instanceof \Throwable) {
                    /** @var \Throwable $value */
                    array_push(
                        $facts,
                        $this->getFact('message', $value->getMessage()),
                        // $this->getFact('Code', $value->getCode()),
                        $this->getFact('Line', "{$value->getFile()}:{$value->getLine()}"),
                        $this->getFact('Trace', $value->getTraceAsString(), true)
                    );
                } else {
                    $facts[] = $this->getFact($key, $value);
                }
            }

            $this->data['sections'][] = [
                'wrap' => true,
                // 'activityTitle' => strtoupper($record->level->name),
                // 'activitySubtitle' => $record->message,
                // 'activityText' => "asdfasdf",
                // 'activitySubtitle' => $record->datetime->format('Y/m/d g:i A'),
                'facts' => $facts
            ];
        }

        return $this;
    }

    /**
     * @param string|int $name
     * @param mixed $value
     * @param bool $isQuoted
     *
     * @return array
     */
    public function getFact(string|int $name, mixed $value, bool $isQuoted = false): array
    {
        if (is_int($name)) {
            $name = strval($name);
        }

        $name = trim(str_replace('_', ' ', $name));
        $value = $this->transformValue($value);

        $doQuote = $isQuoted && (is_string($value) || is_bool($value) || is_float($value) || is_int($value) || null === $value);

        return [
            'name' => ucfirst($name).':',
            'value' => $doQuote ? sprintf('%s %s %s', '<pre>', $value, '</pre>') : $value,
        ];
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    protected function transformValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $value = json_encode($value, JSON_PRETTY_PRINT);

            return !empty($value) ? substr($value, 0, 1000) : '';
        }

        if (is_string($value)) {
            return substr($value, 0, 1000);
        }

        return $value;
    }

    /**
     * Returns Microsoft Teams Card message theme color based on log level.
     *
     * @param int $level
     *
     * @return string
     */
    public function getThemeColor(int $level): string
    {
        return match (true) {
            $level == Level::Debug->value => static::COLOR_DEBUG,
            $level == Level::Info->value => static::COLOR_INFO,
            $level == Level::Notice->value => static::COLOR_NOTICE,
            $level == Level::Warning->value => static::COLOR_WARNING,
            $level == Level::Error->value => static::COLOR_ERROR,
            $level == Level::Critical->value => static::COLOR_CRITICAL,
            $level == Level::Alert->value => static::COLOR_ALERT,
            $level == Level::Emergency->value => static::COLOR_EMERGENCY,
            default => static::COLOR_DEFAULT,
        };
    }

    /**
     * Returns Microsoft Teams Card message emoji based on log level.
     *
     * @param int $level
     *
     * @return string
     */
    public function getEmoji(int $level): string
    {
        return match (true) {
            $level == Level::Debug->value => static::EMOJI_DEBUG,
            $level == Level::Info->value => static::EMOJI_INFO,
            $level == Level::Notice->value => static::EMOJI_NOTICE,
            $level == Level::Warning->value => static::EMOJI_WARNING,
            $level == Level::Error->value => static::EMOJI_ERROR,
            $level == Level::Critical->value => static::EMOJI_CRITICAL,
            $level == Level::Alert->value => static::EMOJI_ALERT,
            $level == Level::Emergency->value => static::EMOJI_EMERGENCY,
            default => static::EMOJI_DEFAULT,
        };
    }
}
