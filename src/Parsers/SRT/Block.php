<?php

namespace Iceylan\Subtitle\Parsers\SRT;

class Block
{
	public bool $dirty = false;
	public ?int $sequenceNumber = null;
	public array $content = [];
	public string $time;
	public string $starts;
	public string $ends;

	public function isDirty(): bool
	{
		return $this->dirty;
	}

	public function isValid(): bool
	{
		$hasSequenceNumber = isset( $this->sequenceNumber );
		$hasTime = isset( $this->time );

		return $hasSequenceNumber && $hasTime;
	}

	public function setSequenceNumber( string $data )
	{
		$this->sequenceNumber = (int) $data;
		$this->dirty = true;
	}

	public function canHaveContent(): bool
	{
		return $this->isValid();
	}

	public function addContent( string $data )
	{
		$this->content[] = $data;
		$this->dirty = true;
	}

	public function setTime( string $data )
	{
		$parts = explode( "-->", $data );

		$this->time = $data;
		$this->dirty = true;
		$this->setStart( $parts[ 0 ]);
		$this->setEnd( $parts[ 1 ]);
	}

	public function setStart( string $data )
	{
		$this->starts = trim( $data );
	}

	public function setEnd( string $data )
	{
		$this->ends = trim( $data );
	}

	public function getStartMs(): int
	{
		return $this->convertToMs( $this->starts );
	}

	public function getEndMs(): int
	{
		return $this->convertToMs( $this->ends );
	}

	public function convertToMs( string $data ): int
	{
		list( $timePart, $ms ) = explode( ",", $data );
		list( $hours, $minutes, $seconds ) = explode( ":", $timePart );
		
		return (int) $hours * 3600000 + (int) $minutes * 60000 + (int) $seconds * 1000 + (int) $ms;
	}
}
