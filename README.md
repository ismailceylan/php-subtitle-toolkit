# Subtitle Manager for PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/iceylan/subtitle.svg?style=flat-square)](https://packagist.org/packages/iceylan/subtitle) [![Total Downloads](https://img.shields.io/packagist/dt/iceylan/subtitle.svg?style=flat-square)](https://packagist.org/packages/iceylan/subtitle) [![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

`Iceylan\Subtitle` is a modern, enterprise-grade, highly extensible, and **100% Immutable** subtitle manipulation library for PHP. Built on robust software design patterns, it allows you to parse, convert, shift time, change frame rates, and programmatically resolve subtitle overlaps without any unexpected side effects.

## Features
- **Fluent API:** Clean and readable method chaining.
- **Smart Formats:** Auto-detects subtitle formats.
- **Immutability:** Keeps your original subtitle data safe from side effects.
- **Overlap Resolution:** Built-in strategies to clean overlapping subtitle timestamps.
- **Format Conversion**: Convert between different subtitle formats.
- **Time Shifting:** Change FPS, stretch by anchors or just move subtitles forward or backward in time.
- **Extensible:** Easily inject custom parsers, renderers, or overlap resolvers.

---

## Installation
```bash
composer require iceylan/subtitle
```

## Quick Start
Convert a SubRip (`.srt`) file into a WebVTT (`.vtt`) string while automatically cleaning up time overlaps and normalizing lines in one single chain:

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

## Architecture & Core Concepts
### 1. Unified Gateway (Parse)
The `Parse` class acts as a static gateway that utilizes lazy-loading and driver detection. It normalizes heterogeneous line endings (`\r\n`, `\r`) to unified unix lines (`\n`) under the hood before delegating the work to the appropriate parser.

```php
use Iceylan\Subtitle\Parse;

// Auto-detects and loads the file into a Collection
$collection = Parse::from( 'path/to/subtitle.srt' );
```

#### Registering Custom Formats
You can expand the library by adding your own parser drivers without touching the core source code:

```php
Parse::register( MyCustomParserDriver::class );
```

### 2. Deep Immutable Engine (`Collection` & `Entry`)
Every manipulation creates a brand-new timeline universe. Thanks to deep-cloning capability, original instances remain unpolluted.

```php
$original = Parse::from( 'movie.srt' );

// Each line creates an independent variation of your timeline
$shifted   = $original->delay( 2500 ); // 2.5 seconds forward
$converted = $original->changeFPS( 23.976, 25.0 ); 

// $original remains completely untainted in memory!
```

## Complete API Reference
### Collection Queries
#### `get(int $index): Entry`
Retrieves a specific subtitle cue entry. Supports negative indexes to seamlessly grab entries counting back from the end of the timeline.

```php
$firstEntry = $collection->get( 0 );
$lastEntry  = $collection->get( -1 ); // Python-style reverse indexing!
```

#### `count(): int`
Returns the total number of subtitle entries inside the collection.

```php
$totalCues = $collection->count();
```

#### `screenTime(): int`
Calculates the absolute summation of individual subtitle visibility durations in milliseconds.

```php
$activeMilliseconds = $collection->screenTime();
```

#### `duration(): int`
Calculates the gross chronological timeline span between the very first starting cue and the absolute last ending cue in milliseconds.

```php
$totalMovieTimeSpan = $collection->duration();
```

#### `getEntries(): array`
Extracts the underlying raw array containing all Entry instances.

```php
$rawArray = $collection->getEntries();
```

### Collection Transformations
#### `delay(int $ms): Collection`
Shifts the entire subtitle track timeline forward or backward by the given amount of milliseconds.

```php
$twoSecsForward = $collection->delay( 2000 );
$oneSecBackward = $collection->delay( -1000 );
```

#### `delayFrom(int $fromIndex, int $ms): Collection`
Shifts timelines by milliseconds exclusively for entries starting from a specific index boundary up to the end.

```php
// Shifts everything starting from index 15 and onwards by 3 seconds
$partiallyShifted = $collection->delayFrom( 15, 3000 );
```

#### `cut(int $fromIndex, int $length = 1): Collection`
Excises a specific range of subtitle cues out of the collection and automatically retrofits the chronologically succeeding blocks back in time to heal the gap.

```php
$trimmedCollection = $collection->cut(from: 10, length: 3);
```

#### `sanitize(): Collection`
Loops through all textual contents of the cues and strips out any untrusted markup or HTML tags.

```php
$cleanTextCollection = $collection->sanitize();
```

#### `changeFPS(float $from, float $to): Collection`
Re-calculates all timestamp sequences proportionally to match target frame rate transitions.

```php
$palVersion = $collection->changeFPS( 23.976, 25.0 );
```

#### `stretch(int $srcAnchor1, int $desAnchor1, int $srcAnchor2, int $desAnchor2): Collection`
Applies linear time interpolation scaling across the entire timeline using two custom real-time reference anchor coordinates. Perfect for syncing subtitles when the frame rate is unknown.

```php
$syncedCollection = $collection->stretch(
    srcAnchor1: 5000,  desAnchor1: 7200,  // first reference point warp
    srcAnchor2: 90000, desAnchor2: 94500  // second reference point warp
);
```

#### `filter(Closure $callback): Collection`
Filters the collection using a custom boolean evaluation closure. Returns a brand new subset collection.

```php
$adsRemoved = $collection->filter( function( Entry $entry )
{
    return ! in_array( '[www.yourads.com](https://www.yourads.com)', $entry->content );
});
```

#### `slice(int $startIndex, ?int $length = null): Collection`
Extracts a designated window slice out of the collection sequence.

```php
$firstTenLines = $collection->slice(0, 10);
```

#### `merge(Collection $other): Collection`
Merges two separate collections together and automatically re-sorts them chronologically.

```php
$fullSubtitles = $partOneCollection->merge($partTwoCollection);
```

#### `push(Entry $entry) / addEntry(Entry $entry): Collection`
Safely appends a new subtitle element block into the timeline pool and auto-arranges its chronological index placement.

```php
use Iceylan\Subtitle\Entry;

$newCue = ( new Entry )
    ->setSequenceNumber( 101 )
    ->setStart( 12000 )
    ->setEnd( 15500 )
    ->addContent( "Breaking News!" );

$updatedCollection = $collection->push( $newCue );
```

#### `resolveOverlaps(?OverlapsResolverInterface $resolver = null): Collection`
Resolves temporal overlapping collision structural errors where a trailing block's start overlaps a preceding block's end. Uses a default TrimAndGapResolver(10) if no strategy is specified.

```php
// Clean up overlaps leaving a safe 10ms gap between colliding items
$resolved = $collection->resolveOverlaps();
```

### Overlap Resolution Strategies
You can swap out the collision resolution strategy or build your own by implementing `OverlapsResolverInterface`.

#### Built-in Strategy: `TrimAndGapResolver`
This strategy adjusts the end time of the preceding item to create a comfortable gap before the next subtitle block arrives, safely handling narrow intervals or empty text fallbacks.

```php
use Iceylan\Subtitle\Resolvers\TrimAndGapResolver;

// Enforces a strict 20ms gap between any overlapping blocks
$resolved = $collection->resolveOverlaps( new TrimAndGapResolver( 20 ));
```

### Network Optimization & Frontend Integration
Both `Entry` and `Collection` natively implement `JsonSerializable`. The JSON payload keys are highly compressed to reduce data overhead, making it ideal for directly streaming files over the wire to your Vue.js, React, or modern web video players.

```php
echo json_encode( $collection );
```

**Compressed JSON Output Structure:**

```json
[
  {
    "sq": 1,
    "ms": [ "Hello, welcome to the film." ],
    "st": 1500,
    "en": 4200
  },
  {
    "sq": 2,
    "ms": [ "Subtitles optimized for network speed." ],
    "st": 4500,
    "en": 8000
  }
]
```

- sq: Sequence Number
- ms: Message / Text Array content lines
- st: Start timestamp in absolute milliseconds
- en: End timestamp in absolute milliseconds

### Extending with Custom Formats
Adding support for an extra subtitle format is extremely straight-forward. Simply implement the core interfaces provided under the `Support` layer.

**Creating a Custom Parser Driver**

```php
namespace App\Subtitle\Parsers;

use Iceylan\Subtitle\Collection;
use Iceylan\Subtitle\Support\ParserInterface;

class MyCustomParser implements ParserInterface
{
    public static function canParse( string $content, string $extension ): bool
    {
        return $extension === 'custom';
    }

    public function parse( string $content ): Collection
    {
        $collection = new Collection();
        // Custom parsing state logic here...
        return $collection;
    }
}
```











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

## Contributing
I welcome all kind of contributions!

The infrastructure is built using the Infrastructure Strategy Pattern. To add a new format, simply write a class that implements `ParserInterface` and `RendererInterface`, place them in the appropriate directories, register them in the `Parser` class, and submit a PR, that’s all you need to do!

## License
This library is released under the [MIT License](https://github.com/ismailceylan/php-subtitle-toolkit/blob/main/LICENSE).
