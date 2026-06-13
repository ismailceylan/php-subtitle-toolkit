<?php

namespace Iceylan\Subtitle\Support;

use Iceylan\Subtitle\Collection;

interface RendererInterface
{
	public function render( Collection $collection ): string;
}
