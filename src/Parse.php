<?php

namespace Iceylan\Subtitle;

use InvalidArgumentException;
use Iceylan\Subtitle\Support\ParserInterface;
use Iceylan\Subtitle\Parsers\SRT\Parser as SRTParser;

class Parse
{
	protected static array $drivers = [];

	public static function bootDefaults()
	{
		if( ! empty( static::$drivers ))
		{
			return;
		}

		static::register(
		[
			SRTParser::class
		]);
	}

	public static function register( string|array $driverClasses )
	{
		foreach((array) $driverClasses as $driverClass )
		{
			if( ! is_subclass_of( $driverClass, ParserInterface::class ))
			{
				throw new InvalidArgumentException(
					"A parser class should implements ParserInterface."
				);
			}

			if( ! in_array( $driverClass, static::$drivers ))
			{
				static::$drivers[] = $driverClass;
			}
		}
	}

	public static function from( string $path ): Collection
	{
		static::bootDefaults();

		$content = file_get_contents( $path );
		$content = str_replace([ "\r\n", "\r" ], "\n", $content );
		$extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ));

		foreach( static::$drivers as $driverClass )
		{
			if( $driverClass::canParse( $content, $extension ))
			{
				return ( new $driverClass )->parse( $content );
			}
		}

		throw new InvalidArgumentException( "This subtitle format doesn't supported yet!" );
	}
}
