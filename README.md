# Flaphl String Element

A modern PHP string manipulation library with Unicode support, HTML safety, and thread-safe operations.

## Installation

```bash
composer require flaphl/string
```

## Quick Start

```php
use Flaphl\Element\String\UnicodeString;
use Flaphl\Element\String\HtmlString;
use Flaphl\Element\String\StringBuilder;

// Unicode string operations
$text = UnicodeString::of('Hello 世界');
echo $text->length(); // 8 (characters, not bytes)
echo $text->toUpperCase(); // HELLO 世界

// HTML-safe strings
$html = HtmlString::safe('<script>alert("xss")</script><p>Safe content</p>');
echo $html->toString(); // <p>Safe content</p>

// Fast string building
$builder = new StringBuilder();
$result = $builder->append('Hello')
                 ->append(' ')
                 ->append('World')
                 ->getString(); // "Hello World"
```

## Classes

- **UnicodeString** - Immutable Unicode string with full UTF-8 support
- **HtmlString** - HTML-safe string with XSS protection
- **StringBuilder** - Fast string concatenation for single-threaded use
- **StringBuffer** - Thread-safe string buffer with file locking

## Features

- Full Unicode/UTF-8 character support
- HTML sanitization and XSS protection
- Thread-safe operations with file-based locking
- Immutable string operations
- Method chaining for fluent API
- Zero-copy optimizations where possible

## Testing

```bash
# Run tests
vendor/bin/phpunit

# With test documentation
vendor/bin/phpunit --testdox
```

## Requirements

- PHP 8.2 or higher
- mbstring extension
- Optional: intl extension for advanced normalization

## License

MIT License. See LICENSE file for details.