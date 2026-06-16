<?php

namespace Iceylan\Subtitle;

use Countable;
use ArrayAccess;
use Traversable;
use ArrayIterator;
use JsonSerializable;
use IteratorAggregate;

class Content implements Countable, ArrayAccess, IteratorAggregate, JsonSerializable
{
	private array $lines = [];

	public function __construct( array|string $rawContent = [])
	{
		if( is_string( $rawContent ))
		{
			$rawContent = explode( "\n", $rawContent );
		}
		
		$this->lines = $rawContent;
	}

	public function getLines(): array
	{
		return $this->lines;
	}

	public function push( string $line ): void
	{
		$this->lines[] = $line;
	}

	public function count(): int
	{
		return count( $this->lines );
	}

	public function getIterator(): Traversable
	{
		return new ArrayIterator( $this->lines );
	}

	public function offsetExists( mixed $offset ): bool
	{
		return isset( $this->lines[ $offset ]);
	}

	public function offsetGet( mixed $offset ): mixed
	{
		return $this->lines[ $offset ];
	}

	public function offsetSet( mixed $offset, mixed $value ): void
	{
		$this->lines[ $offset ] = $value;
	}

	public function offsetUnset( mixed $offset ): void
	{
		unset( $this->lines[ $offset ]);
	}

	public function jsonSerialize(): array
	{
		return $this->lines;
	}
}
