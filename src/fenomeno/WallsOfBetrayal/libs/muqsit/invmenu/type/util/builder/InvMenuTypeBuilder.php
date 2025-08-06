<?php

declare(strict_types=1);

namespace fenomeno\WallsOfBetrayal\libs\muqsit\invmenu\type\util\builder;

use fenomeno\WallsOfBetrayal\libs\muqsit\invmenu\type\InvMenuType;

interface InvMenuTypeBuilder{

	public function build() : InvMenuType;
}