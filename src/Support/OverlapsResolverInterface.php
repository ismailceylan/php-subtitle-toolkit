<?php

namespace Iceylan\Subtitle\Support;

interface OverlapsResolverInterface
{
	public function resolve( array $entries ): array;
}
