<?php

namespace fenomeno\WallsOfBetrayal\Logs\Writer;

use fenomeno\WallsOfBetrayal\Logs\LogRecord;

interface WriterInterface
{

    public function flush(): void;
    public function push(LogRecord $record): void;

}