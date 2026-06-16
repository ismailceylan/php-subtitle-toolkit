<?php

namespace Iceylan\Subtitle\Support;

use Traversable;
use ArrayIterator;
use InvalidArgumentException;

trait Arrayable
{
	protected array $items = [];

	public function count(): int
	{
		return count( $this->items );
	}

	public function get( int $index ): mixed
	{
		if( ! is_int((int) $index ))
		{
			throw new InvalidArgumentException(
				"Index position should only be an integer."
			);
		}

		$count = count( $this->items );

		if( $index < 0 )
		{
			$positiveIndex = $count + $index;

			if( $positiveIndex < 0 || $positiveIndex >= $count )
			{
				throw new InvalidArgumentException( 'Invalid negative index position.' );
			}

			return $this->items[ $positiveIndex ];
		}

		if( $index >= 0 && $index < $count )
		{
			return $this->items[ $index ];
		}

		throw new InvalidArgumentException( 'Invalid positive index position.' );
	}

	public function toArray(): array
	{
		return $this->items;
	}

	public function getIterator(): Traversable
	{
		return new ArrayIterator( $this->items );
	}

	public function offsetExists( mixed $offset ): bool
	{
		return isset( $this->items[ $offset ]);
	}

	public function offsetGet( mixed $offset ): mixed
	{
		return $this->get((int) $offset );
	}

	public function offsetSet( mixed $offset, mixed $value ): void
	{
		$this->items[ $offset ] = $value;
	}

	public function offsetUnset( mixed $offset ): void
	{
		unset( $this->items[ $offset ]);
	}

}
