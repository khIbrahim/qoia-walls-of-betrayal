<?php

namespace fenomeno\WallsOfBetrayal\Logs\Writer;

use fenomeno\WallsOfBetrayal\Logs\LogRecord;
use SplQueue;

class BufferedWriter implements WriterInterface
{

    private SplQueue $queue;

    public function __construct(
        private readonly WriterInterface $inner,
        private readonly int $maxBuffer = 10000,
    ){
        $this->queue = new SplQueue();
    }

    public function flush(): void
    {
        if($this->queue->isEmpty()){
            return;
        }

        while(! $this->queue->isEmpty()){
            /** @var LogRecord $r */
            $r = $this->queue->dequeue();
            $this->inner->push($r);
        }
        $this->inner->flush();
    }

    public function push(LogRecord $record): void
    {
        if ($this->queue->count() >= $this->maxBuffer){
            $this->queue->shift();
        }

        $this->queue->enqueue($record);
    }
}