<?php

namespace Iceylan\Subtitle\Support;

use Iceylan\Subtitle\Subtitle;

interface ParserInterface
{
	public function parse( string $content ): Subtitle;
	
	public static function canParse( string $content, string $extension ): bool;

}
