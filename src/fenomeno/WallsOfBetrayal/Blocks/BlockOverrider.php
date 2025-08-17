<?php

namespace fenomeno\WallsOfBetrayal\Blocks;

use pocketmine\block\Block;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\block\convert\BlockStateWriter;
use pocketmine\world\format\io\GlobalBlockStateHandlers;

final class BlockOverrider {
    public static function override(string $name, Block $block): void {
        /**
         * Overwriting the entry in the RuntimeBlockStateRegistry by calling our custom version of its
         * @see RuntimeBlockStateRegistry::register() method without prohibiting the overwriting of existing entries
         */
        (function(Block $block): void {
            $typeId = $block->getTypeId();
            $this->typeIndex[$typeId] = clone $block;
            foreach($block->generateStatePermutations() as $v){
                $this->fillStaticArrays($v->getStateId(), $v);
            }
        })->call(RuntimeBlockStateRegistry::getInstance(), $block);

        $reflection = new \ReflectionClass(VanillaBlocks::class);
        /** @var array<string, Block> $blocks */
        $blocks = $reflection->getStaticPropertyValue("members");
        $blocks[mb_strtoupper($name)] = clone $block;
        $reflection->setStaticPropertyValue("members", $blocks);
    }

    public static function setDeserializer(string $typeName, Block $block, \Closure $deserialize = null) {
        (function(string $id, \Closure $deserializer): void {
            $this->deserializeFuncs[$id] = $deserializer;
        })->call(
            GlobalBlockStateHandlers::getDeserializer(),
            $typeName,
            $deserialize ?? fn() => clone $block
        );
    }

    public static function setSerializer(string $typeName, Block $block, \Closure $serialize = null) {
        (function(Block $block, \Closure $serializer): void {
            $this->serializers[$block->getTypeId()] = $serializer;
        })->call(
            GlobalBlockStateHandlers::getSerializer(),
            $block,
            $serialize ?? fn() => BlockStateWriter::create($typeName)
        );
    }
}