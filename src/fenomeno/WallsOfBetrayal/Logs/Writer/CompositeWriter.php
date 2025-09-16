<?php

namespace fenomeno\WallsOfBetrayal\Logs\Writer;

use fenomeno\WallsOfBetrayal\Logs\LogRecord;

class CompositeWriter implements WriterInterface
{
    /**
     * @param WriterInterface[] $writers
     */
    public function __construct(
        private readonly array $writers
    ){}

    public function push(LogRecord $record): void
    {
        foreach($this->writers as $w){
            $w->push($record);
        }
    }

    public function flush(): void
    {
        foreach($this->writers as $w){
            $w->flush();
        }
    }
}