<?php

namespace Iceylan\Subtitle;

use Iceylan\Subtitle\Support\OverlapsResolverInterface;

class TrimAndGapResolver implements OverlapsResolverInterface
{
	public function __construct(
		public int $gap = 10
	)
	{}

	public function resolve( array $entries ): array
	{
		$count = count( $entries );

		if( $count <= 1 )
		{
			return $entries;
		}

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

		return $processed;
	}
}
