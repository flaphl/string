# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-10-17

### Added
- Initial release of Flaphl String Element
- `UnicodeString` class for immutable Unicode string operations
- `HtmlString` class with XSS protection and HTML sanitization
- `StringBuilder` class for fast single-threaded string building
- `StringBuffer` class for thread-safe string operations with file locking
- `AbstractUnicodeString` base class with common string operations
- Custom exception hierarchy (`UnicodeException`, `InvalidArgumentException`)
- Full UTF-8 and multibyte character support
- PHPUnit test suite with 173 tests covering all functionality
- PSR-4 autoloading support
- MIT license

### Features
- String manipulation: length, substring, concat, trim, case conversion
- Search operations: indexOf, lastIndexOf, contains, startsWith, endsWith
- Advanced operations: split, replace, reverse, repeat, pad
- HTML operations: tag filtering, attribute sanitization, entity handling
- Thread safety: file-based locking for concurrent access
- Unicode normalization (with intl extension)
- Method chaining for fluent API design