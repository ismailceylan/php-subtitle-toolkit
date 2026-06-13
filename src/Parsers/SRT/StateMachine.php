<?php

namespace Iceylan\Subtitle\Parsers\SRT;

use Iceylan\Subtitle\Support\EventBus;

class StateMachine
{
	use EventBus;

	private Block $block;

	public function __construct()
	{
		$this->block = new Block;
	}

	public function handle( string $line )
	{
		if( empty( $line ))
		{
			$this->handleEmptyLine();
		}
		else if( preg_match( '/^\d+$/', $line ))
		{
			$this->handleDecimalLine( $line );
		}
		else if( preg_match( '/^(\d{2}:\d{2}:\d{2}),(\d{3}) --> (\d{2}:\d{2}:\d{2}),(\d{3})$/', $line ))
		{
			$this->handleTimeLine( $line );
		}
		else
		{
			$this->handleContentLine( $line );
		}
	}

	private function handleEmptyLine()
	{
		// we have an empty line for sure and if we
		// haven't closed the current block yet
		if( $this->block->isDirty())
		{
			if( ! $this->block->isValid())
			{
				// that means previous block wasn't
				// complete yet and tried to create new one
				$this->emit( "malformed-block", [ $this->block ]);
			}
			else
			{
				// current block is fully captured and ready to process
				$this->emit( "block-ready", [ $this->block ]);
			}
			
			$this->block = new Block;
		}
	}

	private function handleDecimalLine( string $line )
	{
		if( ! $this->block->isDirty())
		{
			// it's a sequence number line for sure
			$this->block->setSequenceNumber( $line );
		}
		else
		{
			// the given line data will be considered as content of the block

			if( $this->block->canHaveContent())
			{
				// this line belongs to current block's content area
				$this->block->addContent( $line );
			}
			else
			{
				// current block doesn't expecting content data yet
				$this->emit( "malformed-block", [ $this->block ]);
			}
		}
	}

	private function handleTimeLine( string $line )
	{
		$this->block->setTime( $line );
	}

	private function handleContentLine( string $line )
	{
		$this->block->addContent( $line );
	}
}
