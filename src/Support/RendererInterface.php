<?php

namespace Iceylan\Subtitle\Support;

use Iceylan\Subtitle\Subtitle;

interface RendererInterface
{
	public function render( Subtitle $collection ): string;
}
