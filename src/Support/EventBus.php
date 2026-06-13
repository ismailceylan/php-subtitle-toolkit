<?php

namespace Iceylan\Subtitle\Support;

use Closure;

trait EventBus
{
	private array $listeners = [];

	public function on( string $eventName, Closure $listener )
	{
		$this->listeners[ $eventName ] = $listener;
		return $this;
	}

	public function emit( string $eventName, ?array $args = null )
	{
		$listener = $this->listeners[ $eventName ] ?? null;

		if( $listener )
		{
			$listener( ...( $args ?? []));
		}

		return $this;
	}
}
