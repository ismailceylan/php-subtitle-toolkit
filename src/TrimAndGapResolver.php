<?php

namespace Iceylan\Subtitle;

use Iceylan\Subtitle\Support\OverlapsResolverInterface;

class TrimAndGapResolver implements OverlapsResolverInterface
{
	public function __construct(
		public int $gap = 10
	)
	{}

	public function resolve( Collection $subtitle ): Collection
	{
		$count = $subtitle->count();

		if( $count <= 1 )
		{
			return $subtitle;
		}

		$entries = $subtitle->toArray();
		$processed = [];

		for( $i = 0; $i < $count; $i++ )
		{
			$current = clone $entries[ $i ];

			if( $i < $count - 1 )
			{
				$next = $entries[ $i + 1 ];

				if( $current->ends > $next->starts )
				{
					$newEndTime = $next->starts - $this->gap;
					
					if( $newEndTime <= $current->starts )
					{
						$newEndTime = $next->starts;
					}

					$current->setEnd( $newEndTime );
				}
			}

			if( $current->ends > $current->starts )
			{
				$processed[] = $current;
			}
		}

		return ( new Collection )->from( $processed );
	}
}
