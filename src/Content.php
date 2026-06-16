<?php

namespace Iceylan\Subtitle;

use Countable;
use ArrayAccess;
use JsonSerializable;
use IteratorAggregate;
use Iceylan\Subtitle\Support\Arrayable;

class Content implements Countable, ArrayAccess, IteratorAggregate, JsonSerializable
{
	use Arrayable;

	public function __construct( array|string $rawContent = [])
	{
		if( is_string( $rawContent ))
		{
			$rawContent = explode( "\n", $rawContent );
		}
		
		$this->items = $rawContent;
	}

	public function getLines(): array
	{
		return $this->items;
	}

	public function push( string $line ): void
	{
		$this->items[] = $line;
	}

	public function jsonSerialize(): array
	{
		return $this->items;
	}

	public function __toString(): string
	{
		return implode( "\n", $this->items );
	}
}
