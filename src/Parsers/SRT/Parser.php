<?php

namespace Iceylan\Subtitle\Parsers\SRT;

use InvalidArgumentException;
use Iceylan\Subtitle\Entry;
use Iceylan\Subtitle\Collection;
use Iceylan\Subtitle\Support\ParserInterface;

class Parser implements ParserInterface
{
	public static function canParse( string $content, string $extension ): bool
	{
		$content = explode( "\n", substr( $content, 0, 250 ));
		$firstLine = trim( $content[ 0 ]);
		$secondLine = trim( $content[ 1 ]);
		$timePattern = '/^(\d{2}:\d{2}:\d{2}),(\d{3}) --> (\d{2}:\d{2}:\d{2}),(\d{3})$/';
		$isFirstLineDecimal = preg_match( '/^\d$/', $firstLine );
		$isSecondLineTime = preg_match( $timePattern, $secondLine );

		return $isFirstLineDecimal && $isSecondLineTime;
	}

	public function parse( string $content ): Collection
	{
		$entries = [];
		$lines = explode( "\n", trim( $content ));
		$machine = new StateMachine;

		$machine
			->on( "block-ready", function( Block $block ) use( &$entries )
			{
				$entry = new Entry(
					sequenceNumber: $block->sequenceNumber,
					starts: $block->getStartMs(),
					ends: $block->getEndMs(),
					content: $block->content
				);
				
				$entries[] = $entry;
			})
			->on( "malformed-block", function()
			{
				throw new InvalidArgumentException( "Bozuk bir blok tespit edildi." );
			});

		foreach( $lines as $line )
		{
			$machine->handle( trim( $line ));
		}

		// an empty line could trigger state machine
		// to handle latest block that left in it
		$machine->handle( "" );

		return ( new Collection )->from( $entries );
	}
}
