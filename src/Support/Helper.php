<?php

namespace Iceylan\Subtitle\Support;

class Helper
{
	public static function msToTimeParts( int $ms ): array
	{
		$totalSeconds = $ms / 1000;
		$seconds = floor( $totalSeconds );
		$millisecondsPart = round(( $totalSeconds - $seconds ) * 1000 );
		$hours = floor( $seconds / 3600 );
		$minutes = floor(( $seconds % 3600 ) / 60 );
		$secs = $seconds % 60;

		$formattedHours = str_pad( $hours, 2, '0', STR_PAD_LEFT );
		$formattedMinutes = str_pad( $minutes, 2, '0', STR_PAD_LEFT );
		$formattedSeconds = str_pad( $secs, 2, '0', STR_PAD_LEFT );
		$formattedMilliseconds = str_pad( $millisecondsPart, 3, '0', STR_PAD_LEFT );

		return [
			'hours' => $formattedHours,
			'minutes' => $formattedMinutes,
			'seconds' => $formattedSeconds,
			'miliseconds' => $formattedMilliseconds
		];
	}
}
