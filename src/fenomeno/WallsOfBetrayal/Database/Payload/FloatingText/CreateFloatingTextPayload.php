<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\FloatingText;

use fenomeno\WallsOfBetrayal\Class\FloatingText;
use fenomeno\WallsOfBetrayal\Database\Contrasts\PayloadInterface;
use fenomeno\WallsOfBetrayal\Utils\PositionParser;
use InvalidArgumentException;
use pocketmine\item\StringToItemParser;

final readonly class CreateFloatingTextPayload implements PayloadInterface
{

    public function __construct(
        public string $id,
        public array  $position,
        public string $text,
    ){}

    public function jsonSerialize(): array
    {
        return [
            'id'   => $this->id,
            'text' => $this->text,
            'pos'  => json_encode($this->position),
        ];
    }

    public static function fromObject(object $object): self
    {
        if (! $object instanceof FloatingText){
            throw new InvalidArgumentException("Object must be type of " . FloatingText::class);
        }

        $id   = $object->getId();
        $text = $object->getText();
        $pos = PositionParser::toArray($object->getPosition());

        return new CreateFloatingTextPayload($id, $pos, $text);
    }
}