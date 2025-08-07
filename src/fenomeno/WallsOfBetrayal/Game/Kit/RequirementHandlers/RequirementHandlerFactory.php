<?php

namespace fenomeno\WallsOfBetrayal\Game\Kit\RequirementHandlers;

use fenomeno\WallsOfBetrayal\Enum\KitRequirementType;

class RequirementHandlerFactory
{

    /** @var RequirementHandlerInterface[] */
    private array $validators = [];

    public function __construct(){
        $this->validators[KitRequirementType::BREAK->value] = new BlockRequirementHandler();
        $this->validators[KitRequirementType::KILL->value]  = new EntityRequirementHandler();
    }

    public function make(KitRequirementType $type): ?RequirementHandlerInterface
    {
        return $this->validators[$type->value] ?? null;
    }

}