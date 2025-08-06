<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts;

use JsonSerializable;

interface PayloadInterface extends JsonSerializable
{

    /**
     * j'ai juste fait ça pour que ça soit un array
     *
     * @return array
     */
    public function jsonSerialize(): array;

}