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

	public function __clone()
	{
		foreach( $this->entries as $index => $entry ) 
		{
			$this->entries[ $index ] = clone $entry;
		}
	}

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

	public function silentTime(): int
	{
		return $this->duration() - $this->screenTime();
	}

	public function duration(): int
	{
		$firstEntry = $this->entries[ 0 ];
		$lastEntry = $this->entries[ count( $this->entries ) - 1 ];

		return $lastEntry->ends - $firstEntry->starts;
	}

	public function avgDurationPerEntry(): int|float
	{
		return $this->screenTime() / $this->count();
	}

	public function silenceDensity(): int|float
	{
		return $this->silentTime() / $this->screenTime();
	}

	public function talkativeDensity(): int|float
	{
		return $this->screenTime() / $this->silentTime();
	}

	public function avgSilenceBetweenEntries(): float
	{
		$count = $this->count();
		
		if( $count <= 1 )
		{
			return 0.0;
		}

		return $this->silentTime() / ( $count - 1 );
	}

	public function characters(): int
	{
		$total = 0;

		foreach( $this->entries as $entry )
		{
			$total += mb_strlen( str_replace( " ", '', implode( '', $entry->content )), 'UTF-8' );
		}

		return $total;
	}

	public function words(): int
	{
		$total = 0;

		foreach( $this->entries as $entry ) 
		{
			$text = implode( ' ', $entry->content );
			$words = preg_split( '/\s+/u', trim( $text ));
			
			if( ! empty( $words ) && $words[0] !== '' )
			{
				$total += count( $words );
			}
		}

		return $total;
	}

	public function avgCharsPerSecond(): float
	{
		$screenTimeSec = $this->screenTime() / 1000;

		if( $screenTimeSec <= 0 )
		{
			return 0.0;
		}

		return round( $this->characters() / $screenTimeSec, 2 );
	}

	public function avgWordsPerMinute(): float
	{
		$screenTimeMin = $this->screenTime() / 60000;

		if( $screenTimeMin <= 0 )
		{
			return 0.0;
		}

		return round( $this->words() / $screenTimeMin, 2 );
	}

	public function push( Entry $entry )
	{
		$cloned = clone $this;
		$cloned->entries[] = $entry;

		return $cloned->sort();
	}

	public function sort(): self
	{
		$cloned = clone $this;

		usort( $cloned->entries, fn( Entry $a, Entry $b ) => 
			$a->starts <=> $b->starts
		);

		return $cloned;
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
		$cloned = clone $this;

		return $cloned->from(
			array_slice( $cloned->entries, $startIndex, $length )
		);
	}

	public function merge( Collection $other ): self
	{
		$cloned = clone $this;

		return $cloned
			->from( array_merge( $cloned->entries, $other->getEntries()))
			->sort();
	}

	public function delay( int $ms ): self
	{
		return $this->delayFrom( 0, $ms );
	}

	public function delayFrom( int $from, int $ms ): self
	{
		$cloned = clone $this;

		foreach( array_slice( $cloned->entries, $from, null, true ) as $entry )
		{
			$entry->starts += $ms;
			$entry->ends += $ms;
		}

		return $cloned;
	}

	public function cut( int $from, int $length = 1 ): self
	{
		$cloned = clone $this;
		$entries = $cloned->slice( $from, $length );
		$cloned->delayFrom( $from, -1 * $entries->duration());

		for( $i = $from; $i < $from + $length; $i++ )
		{
			unset( $cloned->entries[ $i ]);
		}

		$cloned->entries = array_values( $cloned->entries );

		return $cloned;
	}

	public function sanitize(): self
	{
		$cloned = clone $this;

		foreach( $cloned->entries as $entry )
		{
			foreach( $entry->content as $index => $msg )
			{
				$entry->content[ $index ] = strip_tags( $msg );
			}
		}

		return $cloned;
	}

	public function changeFPS( float $from, float $to ): self
	{
		$cloned = clone $this;
		$ratio = $from / $to;

		foreach( $cloned->entries as $entry )
		{
			$entry->setStart( $entry->starts * $ratio );
			$entry->setEnd( $entry->ends * $ratio );
		}

		return $cloned;
	}

	public function stretch( int $srcAnchor1, int $desAnchor1, int $srcAnchor2, int $desAnchor2 ): self
	{
		$cloned = clone $this;
		$srcDelta = $srcAnchor2 - $srcAnchor1;
		$desDelta = $desAnchor2 - $desAnchor1;
		$scale = $desDelta / $srcDelta;

		foreach( $cloned->entries as $entry )
		{
			$entry->setStart( $desAnchor1 + (( $entry->starts - $srcAnchor1 ) * $scale ));
			$entry->setEnd( $desAnchor1 + (( $entry->ends - $srcAnchor1 ) * $scale ));
		}

		return $cloned;
	}

	public function filter( Closure $callback ): self
	{
		$cloned = clone $this;
		$stack = [];
		$collection = new self;

		foreach( $cloned->entries as $entry )
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

		return $resolver->resolve(( clone $this )->sort());
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

		return trim( $renderer->render( $this ));
	}

	public function jsonSerialize(): mixed
	{
		return $this->entries;
	}
}
