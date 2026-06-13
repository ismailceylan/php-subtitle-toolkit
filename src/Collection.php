<?php

namespace Iceylan\Subtitle;

use Closure;
use JsonSerializable;
use InvalidArgumentException;
use Iceylan\Subtitle\Support\RendererInterface;
use Iceylan\Subtitle\Support\OverlapsResolverInterface;

class Collection implements JsonSerializable
{
	public array $entries = [];

	public function get( int $index )
	{
		if( ! is_int((int) $index ))
		{
			throw new InvalidArgumentException(
				"İndeks yalnızca tam sayı olmalıdır."
			);
		}

		$count = count( $this->entries );

		// 2. Negatif İndeks Kontrolü (Sondan Erişim)
		if( $index < 0 )
		{
			$positiveIndex = $count + $index;

			if( $positiveIndex < 0 || $positiveIndex >= $count )
			{
				throw new InvalidArgumentException( "Geçersiz negatif indeks." );
			}

			return $this->entries[ $positiveIndex ];
		}

		if( $index >= 0 && $index < $count )
		{
			return $this->entries[ $index ];
		}

		throw new InvalidArgumentException( "Geçersiz pozitif indeks." );
	}

	public function count(): int
	{
		return count( $this->entries );
	}

	public function screenTime(): int
	{
		$total = 0;

		foreach( $this->entries as $entry )
		{
			$total += $entry->duration();
		}

		return $total;
	}

	public function duration(): int
	{
		$firstEntry = $this->entries[ 0 ];
		$lastEntry = $this->entries[ count( $this->entries ) - 1 ];

		return $lastEntry->ends - $firstEntry->starts;
	}

	public function addEntry( Entry $entry ): self
	{
		$this->entries[] = $entry;
		$this->sort();

		return $this;
	}

	public function sort(): self
	{
		usort( $this->entries, fn( Entry $a, Entry $b ) => 
			$a->starts <=> $b->starts
		);

		return $this;
	}

	public function getEntries(): array
	{
		return $this->entries;
	}

	public function from( array $entries ): self
	{
		$this->entries = $entries;
		return $this;
	}

	public function slice( int $startIndex, ?int $length = null ): self
	{
		return ( new self )->from(
			array_slice( $this->entries, $startIndex, $length )
		);
	}

	public function merge( Collection $other ): self
	{
		return ( new self )
			->from( array_merge( $this->entries, $other->getEntries()))
			->sort();
	}

	public function delay( int $ms ): self
	{
		return $this->delayFrom( 0, $ms );
	}

	public function delayFrom( int $from, int $ms ): self
	{
		foreach( array_slice( $this->entries, $from ) as $entry )
		{
			$entry->starts += $ms;
			$entry->ends += $ms;
		}

		return $this;
	}

	public function cut( int $from, int $length = 1 ): self
	{
		$entries = $this->slice( $from, $length );
		$this->delayFrom( $from, -1 * $entries->duration());

		for( $i = $from; $i < $from + $length; $i++ )
		{
			unset( $this->entries[ $i ]);
		}

		$this->entries = array_values( $this->entries );

		return $this;
	}

	public function push( Entry $entry )
	{
		$this->entries[] = $entry;
		
		return $this->sort();
	}

	public function sanitize(): self
	{
		foreach( $this->entries as $entry )
		{
			foreach( $entry->content as $index => $msg )
			{
				$entry->content[ $index ] = strip_tags( $msg );
			}
		}

		return $this;
	}

	public function changeFPS( float $from, float $to ): self
	{
		$ratio = $from / $to;

		foreach( $this->entries as $entry )
		{
			$entry->setStart( $entry->starts * $ratio );
			$entry->setEnd( $entry->ends * $ratio );
		}

		return $this;
	}

	public function stretch( int $srcAnchor1, int $desAnchor1, int $srcAnchor2, int $desAnchor2 ): self
	{
		$srcDelta = $srcAnchor2 - $srcAnchor1;
		$desDelta = $desAnchor2 - $desAnchor1;
		$scale = $desDelta / $srcDelta;

		foreach( $this->entries as $entry )
		{
			$entry->setStart( $desAnchor1 + (( $entry->starts - $srcAnchor1 ) * $scale ));
			$entry->setEnd( $desAnchor1 + (( $entry->ends - $srcAnchor1 ) * $scale ));
		}

		return $this;
	}

	public function filter( Closure $callback ): self
	{
		$stack = [];
		$collection = new self;

		foreach( $this->entries as $entry )
		{
			if( $callback( $entry ))
			{
				$stack[] = $entry;
			}
		}

		$collection->from( $stack );

		return $collection;
	}

	public function resolveOverlaps( ?OverlapsResolverInterface $resolver = null ): self
	{
		$resolver ??= new TrimAndGapResolver( 10 );

		$this->sort();

		$resolvedEntries = $resolver->resolve( $this->entries );

		return ( new self )->from( $resolvedEntries );
	}

	public function render( string|RendererInterface $renderer ): string
	{
		if( is_string( $renderer ))
		{
			if( ! class_exists( $renderer ) || ! is_subclass_of( $renderer, RendererInterface::class ))
			{
				throw new InvalidArgumentException( 'Bilinmeyen renderer sınıfı.' );
			}

			$renderer = new $renderer();
		}

		return $renderer->render( $this );
	}

	public function jsonSerialize(): mixed
	{
		return $this->entries;
	}
}
