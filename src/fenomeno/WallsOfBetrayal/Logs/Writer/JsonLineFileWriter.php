<?php

namespace fenomeno\WallsOfBetrayal\Logs\Writer;

use fenomeno\WallsOfBetrayal\Logs\LogRecord;
use pocketmine\utils\Filesystem;
use Symfony\Component\Filesystem\Path;

class JsonLineFileWriter implements WriterInterface
{

    /** @var array<string, string> */
    private array $buckets = [];

    public function __construct(
        private readonly string $baseDir,
        private readonly string $dateFormat = 'd-m-Y',
    ){
        if(! is_dir($this->baseDir)) {
            @mkdir($this->baseDir, 0777, true);
        }
    }

    public function flush(): void
    {
        if(empty($this->buckets)){
            return;
        }

        foreach($this->buckets as $path => $lines){
            if (is_array($lines)){
                $this->appendLines($path, $lines);
            } else {
                $this->appendLines($path, [$lines]);
            }
        }

        $this->buckets = [];
    }

    public function push(LogRecord $record): void
    {
        $date  = date($this->dateFormat, (int) $record->timestamp);
        $dir   = Path::join($this->baseDir, $record->channel);

        if(! is_dir($dir)){
            @mkdir($dir, 0777, true);
        }
        $path  = Path::join($dir, $date . '.log');

        $this->buckets[$path][] = json_encode($record, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }

    public function appendLines(string $path, array $lines): void
    {
        $file = @fopen($path, 'a');
        if($file === false){
            throw new \RuntimeException("Could not open log file for writing: $path");
        }

        try {
            foreach ($lines as $line){
                @fwrite($file, $line);
            }
        } finally {
            @fclose($file);
        }
    }

}