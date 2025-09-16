<?php

namespace fenomeno\WallsOfBetrayal\Logs;

use fenomeno\WallsOfBetrayal\Logs\Domain\DomainEventInterface;
use fenomeno\WallsOfBetrayal\Logs\Writer\WriterInterface;
use fenomeno\WallsOfBetrayal\Main;

class LoggingManager implements LoggerInterface
{
    /** @var array<string,int> */
    private array $channelLevels = [];
    /** @var callable[] */
    private array $processors = [];
    private int $defaultLevel = LogLevel::INFO;

    private array $logs = [];

    public function __construct(
        private readonly Main $main,
        private readonly WriterInterface $writer,
    ){}

    public function getMain(): Main
    {
        return $this->main;
    }

    public function setDefaultLevel(int $level): void
    {
        $this->defaultLevel = $level;
    }

    public function setChannelLevel(string $channel, int $level): void
    {
        $this->channelLevels[$channel] = $level;
    }

    public function addProcessor(callable $processor): void
    {
        $this->processors[] = $processor;
    }

    private function accepts(string $channel, int $level): bool
    {
        $min = $this->channelLevels[$channel] ?? $this->defaultLevel;
        return $level >= $min;
    }

    public function log(int $level, string $channel, string $message, array $context = [], array $extra = []): void
    {
        if(! $this->accepts($channel, $level)){
            return;
        }

        if(str_contains($message, '{')){
            foreach($context as $k => $v){
                if(is_scalar($v)){
                    $message = str_replace('{'.$k.'}', (string)$v, $message);
                }
            }
        }

        $record = new LogRecord(
            timestamp: microtime(true),
            level: $level,
            levelName: LogLevel::toString($level),
            channel: $channel,
            message: $message,
            context: $context,
            extra: $extra
        );

        foreach($this->processors as $p){
            $record = $p($record) ?? $record;
        }

        $this->writer->push($record);
    }

    public function debug(string $channel, string $message, array $context = [], array $extra = []): void   { $this->log(LogLevel::DEBUG, $channel, $message, $context, $extra); }
    public function info(string $channel, string $message, array $context = [], array $extra = []): void    { $this->log(LogLevel::INFO, $channel, $message, $context, $extra); }
    public function notice(string $channel, string $message, array $context = [], array $extra = []): void  { $this->log(LogLevel::NOTICE, $channel, $message, $context, $extra); }
    public function warning(string $channel, string $message, array $context = [], array $extra = []): void { $this->log(LogLevel::WARNING, $channel, $message, $context, $extra); }
    public function error(string $channel, string $message, array $context = [], array $extra = []): void   { $this->log(LogLevel::ERROR, $channel, $message, $context, $extra); }
    public function critical(string $channel, string $message, array $context = [], array $extra = []): void{ $this->log(LogLevel::CRITICAL, $channel, $message, $context, $extra); }


    public function recordEvent(DomainEventInterface $event): void
    {
        $this->logs[$event->getChannel()] = $event;
        $event->onRecord($this);

        $this->log(
            $event->getLevel(),
            $event->getChannel(),
            $event->getMessage(),
            $event->getContext(),
            $event->getExtra()
        );
    }

    public function getLogsByChannel(string $channel): array
    {
        return array_values(array_filter($this->logs, fn($e) => $e->getChannel() === $channel));
    }

    public function flush(): void
    {
        $this->writer->flush();
    }
}