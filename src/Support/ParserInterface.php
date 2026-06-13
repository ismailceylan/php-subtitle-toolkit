<?php

namespace Iceylan\Subtitle\Support;

use Iceylan\Subtitle\Collection;

interface ParserInterface
{
	public function parse( string $content ): Collection;
	
	public static function canParse( string $content, string $extension ): bool;

}
