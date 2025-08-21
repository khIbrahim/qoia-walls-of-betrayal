<?php

namespace fenomeno\WallsOfBetrayal\Events\Punishment;


use fenomeno\WallsOfBetrayal\Class\Punishment\AbstractPunishment;

class NewReportEvent extends PunishmentEvent {

    public function getPunishmentType(): string
    {
        return AbstractPunishment::TYPE_REPORT;
    }
}