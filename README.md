# Subtitle Manager for PHP
Modern, fluent, and highly extensible subtitle manipulation library for PHP. Parse, convert, shift time, and resolve overlaps with ease.

## Features
- **Fluent API:** Clean and readable method chaining.
- **Smart Formats:** Auto-detects subtitle formats.
- **Immutability:** Keeps your original subtitle data safe from side effects.
- **Overlap Resolution:** Built-in strategies to clean overlapping subtitle timestamps.
- **Extensible:** Easily inject custom parsers, renderers, or resolvers.

## Installation
```bash
composer require iceylan/subtitle
```

## Quick Start
```php
use Iceylan\Subtitle\Parse;
use Iceylan\Subtitle\Renderers\VTT;

// Read SRT, sanitize, clean overlaps, and output as WebVTT string
$content = Parse::from( 'movie.srt' )
    ->sanitize()
    ->resolveOverlaps()
    ->render( VTT::class );

file_put_contents( 'movie.vtt', $content );
```

## Advanced Usage
### Working with Entry

An `Entry` object represents a single subtitle block (cue) with its timeline and contents.

#### Creating or Manipulating an Entry
```php
use Iceylan\Subtitle\Entry;

$entry = new Entry();

$entry->setSequenceNumber( 1 )
      ->setStart( 1000 ) // 1 second
      ->setEnd( 3500 ) // 3.5 seconds
      ->addContent( "Hello World" )
      ->addContent( "This is the second line" );

echo $entry->duration(); // Outputs: 2500 (milliseconds)
```

### JSON Output Format
When serialized via `json_encode( $entry )`, it outputs a highly optimized payload:

```json
{
  "sq": 1,
  "ms": ["Hello World", "This is the second line"],
  "st": 1000,
  "en": 3500
}
```

### Shifting Time (Delay)
```php
// Move all subtitles 2.5 seconds forward
$collection->delay( 2500 );
// Move all subtitles 1 second backward
$collection->delay( -1000 );
```

### JSON Serialization
Perfect for frontend video players (e.g., Vue.js/Pinia integration):

```php
// Automatically serializes into a clean JSON array
echo json_encode( $collection );
```
### Loading And Parsing Subtitle Files
It automatically detects the format of subtitle files by analyzing their contents.

```php
use Iceylan\Subtitle\Parse;

$collection = Parse::from( 'path/to/subtitle.srt' );
```


## Contributing
I welcome all kind of contributions!

The infrastructure is built using the Infrastructure Strategy Pattern. To add a new format, simply write a class that implements `ParserInterface` and `RendererInterface`, place them in the appropriate directories, register them in the `Parser` class, and submit a PR, that’s all you need to do!

## License
This library is released under the [MIT License](https://github.com/ismailceylan/php-subtitle-toolkit/blob/main/LICENSE).
