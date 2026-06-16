<?php

namespace Iceylan\Subtitle\Support;

use Iceylan\Subtitle\Subtitle;

interface OverlapsResolverInterface
{
	public function resolve( Subtitle $entries ): Subtitle;
}
