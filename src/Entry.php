<?php

namespace Iceylan\Subtitle;

use JsonSerializable;

class Entry implements JsonSerializable
{
	public string $id;

	public function __construct(
		public ?int $sequenceNumber = null,
		public ?array $content = [],
		public ?int $starts = 0,
		public ?int $ends = 0
	)
	{
		$this->id = md5( microtime() . $sequenceNumber . $starts . $ends );
	}

	public function setSequenceNumber( int $value ): self
	{
		$this->sequenceNumber = $value;
		return $this;
	}

	public function addContent( string $msg ): self
	{
		$this->content[] = $msg;
		return $this;
	}

	public function setContent( array $content ): self
	{
		$this->content = $content;
		return $this;
	}

	public function setStart( int $ms ): self
	{
		$this->starts = $ms;
		return $this;
	}

	public function setEnd( int $ms ): self
	{
		$this->ends = $ms;
		return $this;
	}
	
	public function isEmpty(): bool
	{
		return count( $this->content ) === 0;
	}

	public function duration(): int
	{
		return $this->ends - $this->starts;
	}

	public function jsonSerialize(): mixed
	{
		return [
			'sq' => $this->sequenceNumber,
			'ms' => $this->content,
			'st' => $this->starts,
			'en' => $this->ends
		];
	}
}
