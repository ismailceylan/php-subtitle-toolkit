<?php

namespace Iceylan\Subtitle\Support;

use Iceylan\Subtitle\Collection;

interface OverlapsResolverInterface
{
	public function resolve( Collection $entries ): Collection;
}
