<?php

namespace fenomeno\WallsOfBetrayal\Logs\Writer;

use fenomeno\WallsOfBetrayal\Logs\LogRecord;
use RuntimeException;
use SplQueue;

class AsyncFileWriter
{
    private string $baseDir;
    private SplQueue $queue;
    private int $maxBuffer;

    public function __construct(string $baseDir, int $maxBuffer = 10000)
    {
        $this->baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR);
        if(! is_dir($this->baseDir)){
            @mkdir($this->baseDir, 0777, true);
        }
        $this->queue     = new SplQueue();
        $this->maxBuffer = $maxBuffer;
    }

    public function push(LogRecord $record): void
    {
        if($this->queue->count() >= $this->maxBuffer){
            $this->queue->shift();
        }

        $this->queue->enqueue($record);
    }

    public function flush(): void
    {
        if($this->queue->isEmpty()){
            return;
        }

        $buckets = [];
        while(! $this->queue->isEmpty()){
            /** @var LogRecord $record */
            $record = $this->queue->dequeue();
            $date   = date('Y-m-d');
            $channel = $record->channel;
            $file = $this->baseDir . DIRECTORY_SEPARATOR . $channel;
            if(!is_dir($file)){
                @mkdir($file, 0777, true);
            }
            $path = $file . DIRECTORY_SEPARATOR . $date . '.log';
            $buckets[$path][] = json_encode($record->jsonSerialize(), JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }
        foreach($buckets as $path => $lines){
            $this->appendFile($path, $lines);
        }
    }

    private function appendFile(string $path, array $lines): void
    {
        $h = @fopen($path, 'ab');
        if(!$h){
            throw new RuntimeException('Cannot open log file: ' . $path);
        }
        try {
            foreach($lines as $line){
                fwrite($h, $line);
            }
        } finally {
            fclose($h);
        }
    }
}

