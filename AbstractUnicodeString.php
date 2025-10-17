<?php

/**
 * This file is part of the Flaphl package.
 * 
 * (c) Jade Phyressi <jade@flaphl.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flaphl\Element\String;

use Flaphl\Element\String\Exceptions\InvalidArgumentException;
use Flaphl\Element\String\Exceptions\UnicodeException;

/**
 * AbstractUnicodeString: Base class for Unicode string implementations.
 * 
 * Provides common functionality and structure for all Unicode string types
 * in the Flaphl framework. This abstract class defines the core interface
 * and implements shared methods that can be used by concrete implementations.
 * 
 * @package Flaphl\Element\String
 * @author Jade Phyressi <jade@flaphl.com>
 */
abstract class AbstractUnicodeString implements Stringable
{
    /**
     * @var string The UTF-8 encoded string content.
     */
    protected readonly string $string;

    /**
     * @var string The encoding of the string (always UTF-8).
     */
    protected readonly string $encoding;

    /**
     * @var int|null Cached character count.
     */
    protected ?int $length = null;

    /**
     * Constructor to create a new Unicode string.
     *
     * @param string $string The string content.
     * @param string $encoding The source encoding (will be converted to UTF-8).
     * @throws UnicodeException If the string cannot be converted to UTF-8.
     */
    public function __construct(string $string = '', string $encoding = 'UTF-8')
    {
        if ($encoding !== 'UTF-8') {
            try {
                $converted = mb_convert_encoding($string, 'UTF-8', $encoding);
                if ($converted === false) {
                    throw new UnicodeException("Cannot convert string from {$encoding} to UTF-8.");
                }
                $string = $converted;
            } catch (\ValueError $e) {
                throw new UnicodeException("Cannot convert string from {$encoding} to UTF-8.");
            }
        }

        // Validate UTF-8
        if (!mb_check_encoding($string, 'UTF-8')) {
            throw new UnicodeException('Invalid UTF-8 string provided.');
        }

        $this->string = $string;
        $this->encoding = 'UTF-8';
    }

    /**
     * Get the character length of the string.
     *
     * @return int The number of Unicode characters.
     */
    public function length(): int
    {
        if ($this->length === null) {
            $this->length = mb_strlen($this->string, 'UTF-8');
        }
        return $this->length;
    }

    /**
     * Get the byte length of the string.
     *
     * @return int The number of bytes.
     */
    public function byteLength(): int
    {
        return strlen($this->string);
    }

    /**
     * Check if the string is empty.
     *
     * @return bool True if the string is empty.
     */
    public function isEmpty(): bool
    {
        return $this->string === '';
    }

    /**
     * Get a character at the specified index.
     *
     * @param int $index The character index (0-based).
     * @return string The character at the specified index.
     * @throws InvalidArgumentException If index is out of bounds.
     */
    public function charAt(int $index): string
    {
        if ($index < 0 || $index >= $this->length()) {
            throw new InvalidArgumentException("Index {$index} is out of bounds for string length {$this->length()}.");
        }

        return mb_substr($this->string, $index, 1, 'UTF-8');
    }

    /**
     * Get the Unicode code point at the specified index.
     *
     * @param int $index The character index.
     * @return int The Unicode code point.
     * @throws InvalidArgumentException If index is out of bounds.
     */
    public function codePointAt(int $index): int
    {
        $char = $this->charAt($index);
        $codePoints = mb_convert_encoding($char, 'UTF-32BE', 'UTF-8');
        return unpack('N', $codePoints)[1];
    }

    /**
     * Check if the string contains another string.
     *
     * @param string|self $needle The string to search for.
     * @param bool $caseSensitive Whether the search is case-sensitive.
     * @return bool True if the string contains the needle.
     */
    public function contains(string|self $needle, bool $caseSensitive = true): bool
    {
        $needleString = $needle instanceof static ? $needle->string : $needle;
        
        if ($caseSensitive) {
            return mb_strpos($this->string, $needleString, 0, 'UTF-8') !== false;
        } else {
            return mb_stripos($this->string, $needleString, 0, 'UTF-8') !== false;
        }
    }

    /**
     * Find the index of the first occurrence of a substring.
     *
     * @param string|self $needle The substring to search for.
     * @param int $offset The offset to start searching from.
     * @param bool $caseSensitive Whether the search is case-sensitive.
     * @return int|false The index of the first occurrence, or false if not found.
     */
    public function indexOf(string|self $needle, int $offset = 0, bool $caseSensitive = true): int|false
    {
        $needleString = $needle instanceof static ? $needle->string : $needle;
        
        if ($caseSensitive) {
            return mb_strpos($this->string, $needleString, $offset, 'UTF-8');
        } else {
            return mb_stripos($this->string, $needleString, $offset, 'UTF-8');
        }
    }

    /**
     * Find the index of the last occurrence of a substring.
     *
     * @param string|self $needle The substring to search for.
     * @param int $offset The offset to start searching from.
     * @param bool $caseSensitive Whether the search is case-sensitive.
     * @return int|false The index of the last occurrence, or false if not found.
     */
    public function lastIndexOf(string|self $needle, int $offset = 0, bool $caseSensitive = true): int|false
    {
        $needleString = $needle instanceof static ? $needle->string : $needle;
        
        if ($caseSensitive) {
            return mb_strrpos($this->string, $needleString, $offset, 'UTF-8');
        } else {
            return mb_strripos($this->string, $needleString, $offset, 'UTF-8');
        }
    }

    /**
     * Check if the string starts with another string.
     *
     * @param string|self $prefix The prefix to check.
     * @param bool $caseSensitive Whether the check is case-sensitive.
     * @return bool True if the string starts with the prefix.
     */
    public function startsWith(string|self $prefix, bool $caseSensitive = true): bool
    {
        $prefixString = $prefix instanceof static ? $prefix->string : $prefix;
        $length = mb_strlen($prefixString, 'UTF-8');
        
        if ($length === 0) {
            return true;
        }
        
        $start = mb_substr($this->string, 0, $length, 'UTF-8');
        
        if ($caseSensitive) {
            return $start === $prefixString;
        } else {
            return mb_strtolower($start, 'UTF-8') === mb_strtolower($prefixString, 'UTF-8');
        }
    }

    /**
     * Check if the string ends with another string.
     *
     * @param string|self $suffix The suffix to check.
     * @param bool $caseSensitive Whether the check is case-sensitive.
     * @return bool True if the string ends with the suffix.
     */
    public function endsWith(string|self $suffix, bool $caseSensitive = true): bool
    {
        $suffixString = $suffix instanceof static ? $suffix->string : $suffix;
        $length = mb_strlen($suffixString, 'UTF-8');
        
        if ($length === 0) {
            return true;
        }
        
        $end = mb_substr($this->string, -$length, null, 'UTF-8');
        
        if ($caseSensitive) {
            return $end === $suffixString;
        } else {
            return mb_strtolower($end, 'UTF-8') === mb_strtolower($suffixString, 'UTF-8');
        }
    }

    /**
     * Trim whitespace from both ends.
     *
     * @param string $characters Characters to trim.
     * @return static A new trimmed Unicode string.
     */
    public function trim(string $characters = " \t\n\r\0\x0B"): static
    {
        // For Unicode-aware trimming, we need to handle multibyte characters
        $pattern = '/^[' . preg_quote($characters, '/') . ']+|[' . preg_quote($characters, '/') . ']+$/u';
        $trimmed = preg_replace($pattern, '', $this->string);
        return new static($trimmed);
    }

    /**
     * Trim whitespace from the left end.
     *
     * @param string $characters Characters to trim.
     * @return static A new left-trimmed Unicode string.
     */
    public function trimLeft(string $characters = " \t\n\r\0\x0B"): static
    {
        $pattern = '/^[' . preg_quote($characters, '/') . ']+/u';
        $trimmed = preg_replace($pattern, '', $this->string);
        return new static($trimmed);
    }

    /**
     * Trim whitespace from the right end.
     *
     * @param string $characters Characters to trim.
     * @return static A new right-trimmed Unicode string.
     */
    public function trimRight(string $characters = " \t\n\r\0\x0B"): static
    {
        $pattern = '/[' . preg_quote($characters, '/') . ']+$/u';
        $trimmed = preg_replace($pattern, '', $this->string);
        return new static($trimmed);
    }

    /**
     * Replace all occurrences of a string with another string.
     *
     * @param string|self $search The string to search for.
     * @param string|self $replace The replacement string.
     * @param bool $caseSensitive Whether the replacement is case-sensitive.
     * @return static A new Unicode string with replacements made.
     */
    public function replace(string|self $search, string|self $replace, bool $caseSensitive = true): static
    {
        $searchString = $search instanceof static ? $search->string : $search;
        $replaceString = $replace instanceof static ? $replace->string : $replace;
        
        if ($caseSensitive) {
            $result = str_replace($searchString, $replaceString, $this->string);
        } else {
            $result = str_ireplace($searchString, $replaceString, $this->string);
        }
        
        return new static($result);
    }

    /**
     * Split the string by a delimiter.
     *
     * @param string|self $delimiter The delimiter to split by.
     * @param int $limit Maximum number of parts (0 for no limit).
     * @return array An array of Unicode string parts.
     */
    public function split(string|self $delimiter, int $limit = 0): array
    {
        $delimiterString = $delimiter instanceof static ? $delimiter->string : $delimiter;
        
        if ($limit === 0) {
            $parts = explode($delimiterString, $this->string);
        } else {
            $parts = explode($delimiterString, $this->string, $limit);
        }
        
        return array_map(fn($part) => new static($part), $parts);
    }

    /**
     * Reverse the string.
     *
     * @return static A new reversed Unicode string.
     */
    public function reverse(): static
    {
        // Unicode-safe string reversal
        $characters = [];
        for ($i = 0; $i < $this->length(); $i++) {
            $characters[] = $this->charAt($i);
        }
        
        return new static(implode('', array_reverse($characters)));
    }

    /**
     * Repeat the string a specified number of times.
     *
     * @param int $times The number of times to repeat.
     * @return static A new Unicode string with the repeated content.
     * @throws InvalidArgumentException If times is negative.
     */
    public function repeat(int $times): static
    {
        if ($times < 0) {
            throw new InvalidArgumentException('Repeat count cannot be negative.');
        }
        
        return new static(str_repeat($this->string, $times));
    }

    /**
     * Pad the string to a certain length with another string.
     *
     * @param int $length The target length.
     * @param string $padString The padding string.
     * @param int $padType The padding type (STR_PAD_RIGHT, STR_PAD_LEFT, STR_PAD_BOTH).
     * @return static A new padded Unicode string.
     */
    public function pad(int $length, string $padString = ' ', int $padType = STR_PAD_RIGHT): static
    {
        // For Unicode strings, we need to handle padding differently
        $currentLength = $this->length();
        
        if ($length <= $currentLength) {
            return $this;
        }
        
        $padLength = $length - $currentLength;
        $padStr = str_repeat($padString, ceil($padLength / mb_strlen($padString, 'UTF-8')));
        $padStr = mb_substr($padStr, 0, $padLength, 'UTF-8');
        
        switch ($padType) {
            case STR_PAD_LEFT:
                return new static($padStr . $this->string);
            case STR_PAD_BOTH:
                $leftPad = str_repeat($padString, floor($padLength / 2));
                $rightPad = str_repeat($padString, ceil($padLength / 2));
                return new static($leftPad . $this->string . $rightPad);
            default:
                return new static($this->string . $padStr);
        }
    }

    /**
     * Compare this string with another string.
     *
     * @param string|self $other The string to compare with.
     * @param bool $caseSensitive Whether the comparison is case-sensitive.
     * @return int Less than 0 if this string is less, 0 if equal, greater than 0 if greater.
     */
    public function compareTo(string|self $other, bool $caseSensitive = true): int
    {
        $otherString = $other instanceof static ? $other->string : $other;
        
        if ($caseSensitive) {
            return strcmp($this->string, $otherString);
        } else {
            return strcasecmp($this->string, $otherString);
        }
    }

    /**
     * Check if this string equals another string.
     *
     * @param string|self $other The string to compare with.
     * @param bool $caseSensitive Whether the comparison is case-sensitive.
     * @return bool True if the strings are equal.
     */
    public function equals(string|self $other, bool $caseSensitive = true): bool
    {
        return $this->compareTo($other, $caseSensitive) === 0;
    }

    /**
     * Get all Unicode characters as an array.
     *
     * @return array An array of individual Unicode characters.
     */
    public function toCharArray(): array
    {
        $characters = [];
        for ($i = 0; $i < $this->length(); $i++) {
            $characters[] = $this->charAt($i);
        }
        return $characters;
    }

    /**
     * Get the underlying string value.
     *
     * @return string The raw UTF-8 string.
     */
    public function toString(): string
    {
        return $this->string;
    }

    /**
     * Magic method to get string representation.
     *
     * @return string The raw UTF-8 string.
     */
    public function __toString(): string
    {
        return $this->string;
    }

    /**
     * Get the encoding of the string.
     *
     * @return string The encoding (always UTF-8).
     */
    public function getEncoding(): string
    {
        return $this->encoding;
    }

    /**
     * Check if the string is valid UTF-8.
     *
     * @return bool True if the string is valid UTF-8.
     */
    public function isValidUtf8(): bool
    {
        return mb_check_encoding($this->string, 'UTF-8');
    }

    /**
     * Normalize the Unicode string.
     *
     * @param int $form The normalization form (default: Normalizer::FORM_C).
     * @return static A new normalized Unicode string.
     * @throws UnicodeException If normalization fails.
     */
    public function normalize(int $form = \Normalizer::FORM_C): static
    {
        if (!class_exists('Normalizer')) {
            throw new UnicodeException('Intl extension is required for Unicode normalization.');
        }
        
        $normalized = \Normalizer::normalize($this->string, $form);
        if ($normalized === false) {
            throw new UnicodeException('Unicode normalization failed.');
        }
        
        return new static($normalized);
    }

    // Abstract methods that concrete implementations must provide

    /**
     * Create a new instance from a string.
     * Concrete implementations should define their own factory methods.
     *
     * @param string $string The string content.
     * @param string $encoding The source encoding.
     * @return static A new instance of the concrete implementation.
     */
    abstract public static function of(string $string, string $encoding = 'UTF-8'): static;

    /**
     * Create an empty instance.
     * Concrete implementations should define their own empty factory methods.
     *
     * @return static A new empty instance of the concrete implementation.
     */
    abstract public static function empty(): static;

    /**
     * Get a substring from the string.
     * Abstract method to ensure concrete implementations handle immutability correctly.
     *
     * @param int $start The starting index.
     * @param int|null $length The length of the substring (null for rest).
     * @return static A new instance with the substring.
     * @throws InvalidArgumentException If start is out of bounds.
     */
    abstract public function substring(int $start, ?int $length = null): static;

    /**
     * Concatenate with another string.
     * Abstract method to ensure concrete implementations handle immutability correctly.
     *
     * @param string|AbstractUnicodeString $other The string to concatenate.
     * @return static A new instance with the concatenated result.
     */
    abstract public function concat(string|AbstractUnicodeString $other): static;

    /**
     * Convert the string to lowercase.
     * Abstract method to allow specialized case conversion in concrete implementations.
     *
     * @return static A new instance in lowercase.
     */
    abstract public function toLowerCase(): static;

    /**
     * Convert the string to uppercase.
     * Abstract method to allow specialized case conversion in concrete implementations.
     *
     * @return static A new instance in uppercase.
     */
    abstract public function toUpperCase(): static;

    /**
     * Convert the string to title case.
     * Abstract method to allow specialized case conversion in concrete implementations.
     *
     * @return static A new instance in title case.
     */
    abstract public function toTitleCase(): static;
}
