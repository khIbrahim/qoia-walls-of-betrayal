<?php

namespace fenomeno\WallsOfBetrayal\DTO;

use InvalidArgumentException;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;

class KingdomEnchantment
{

    public function __construct(
        public string $enchantmentId,
        public int $level,
        public int $cost,
        public string $description = ''
    ){}

    public static function fromArray(mixed $enchantmentData): self
    {
        if(! isset($enchantmentData['id'], $enchantmentData['level'], $enchantmentData['cost'])){
            throw new InvalidArgumentException("Invalid enchantment data provided");
        }

        $enchantmentId = (string) $enchantmentData['id'];
        if(StringToEnchantmentParser::getInstance()->parse($enchantmentId) === null){
            throw new InvalidArgumentException("Enchantment with id '$enchantmentId' does not exist");
        }
        $level         = (int) $enchantmentData['level'];
        $cost          = (int) $enchantmentData['cost'];
        $description   = isset($enchantmentData['description']) ? (string) $enchantmentData['description'] : '';

        return new self($enchantmentId, $level, $cost, $description);
    }

    /** Peut pas être null */
    public function getEnchantment(): Enchantment
    {
        return StringToEnchantmentParser::getInstance()->parse($this->enchantmentId);
    }

    public function getEnchantmentInstance(): EnchantmentInstance
    {
        return new EnchantmentInstance($this->getEnchantment(), $this->level);
    }

}