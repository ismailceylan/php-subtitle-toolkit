<?php

namespace Iceylan\Subtitle\Renderers;

use Iceylan\Subtitle\Subtitle;
use Iceylan\Subtitle\Support\Helper;
use Iceylan\Subtitle\Support\RendererInterface;

class VTT implements RendererInterface
{
	public function render( Subtitle $entries ): string
	{
		$document = [];

		foreach( $entries->toArray() as $index => $entry )
		{
			$block = '';

			$start = $this->msToTimestamp( $entry->starts );
			$end = $this->msToTimestamp( $entry->ends );

			if( ! is_null( $entry->sequenceNumber )) 
			{
				$block .= $entry->sequenceNumber . "\n";
			}

			$block .= "$start --> $end\n";
			$block .= implode( "\n", $entry->content );

			$document[] = $block;
		}

		return "WEBVTT\n\n" . trim( implode( "\n\n", $document ));
	}

	private function msToTimestamp( int $ms ): string
	{
		$time = Helper::msToTimeParts( $ms );

		$hours = $time[ 'hours' ];
		$minutes = $time[ 'minutes' ];
		$seconds = $time[ 'seconds' ];
		$milliseconds = $time[ 'milliseconds' ];

		return "$hours:$minutes:$seconds.$milliseconds";
	}
}
