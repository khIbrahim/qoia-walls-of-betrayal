<?php

namespace fenomeno\WallsOfBetrayal\Logs\Domain;

use fenomeno\WallsOfBetrayal\Logs\LoggingManager;
use JsonSerializable;

interface DomainEventInterface extends JsonSerializable
{
    public function getChannel(): string;
    public function getLevel(): int;
    public function getMessage(): string;
    public function getContext(): array;
    public function getExtra(): array;

    public function onRecord(LoggingManager $manager): void;
}