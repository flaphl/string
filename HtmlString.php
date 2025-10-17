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
 * HtmlString: Secure HTML string with automatic escaping and validation.
 * 
 * Provides HTML-safe string operations with automatic escaping of dangerous
 * content while preserving valid HTML markup. Ensures XSS protection and
 * maintains Unicode support for international content.
 * 
 * @package Flaphl\Element\String
 * @author Jade Phyressi <jade@flaphl.com>
 */
class HtmlString extends AbstractUnicodeString
{
    /**
     * @var bool Whether the content is already HTML-escaped.
     */
    private readonly bool $isEscaped;

    /**
     * @var array Allowed HTML tags for safe HTML mode.
     */
    private static array $allowedTags = [
        'p', 'br', 'strong', 'em', 'u', 'i', 'b',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li', 'dl', 'dt', 'dd',
        'blockquote', 'cite', 'code', 'pre',
        'a', 'img', 'span', 'div'
    ];

    /**
     * @var array Allowed attributes for safe HTML mode.
     */
    private static array $allowedAttributes = [
        'href', 'title', 'alt', 'src', 'class', 'id',
        'target', 'rel', 'width', 'height'
    ];

    /**
     * Constructor to create a new HtmlString.
     *
     * @param string $string The HTML string content.
     * @param string $encoding The source encoding (will be converted to UTF-8).
     * @param bool $isEscaped Whether the content is already HTML-escaped.
     * @throws UnicodeException If the string cannot be converted to UTF-8.
     */
    public function __construct(string $string = '', string $encoding = 'UTF-8', bool $isEscaped = false)
    {
        parent::__construct($string, $encoding);
        $this->isEscaped = $isEscaped;
    }

    /**
     * Create an HtmlString from a raw string with automatic escaping.
     *
     * @param string $string The raw string to escape and wrap.
     * @param string $encoding The source encoding.
     * @return self A new HtmlString instance with escaped content.
     */
    public static function of(string $string, string $encoding = 'UTF-8'): static
    {
        $escaped = htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return new static($escaped, $encoding, true);
    }

    /**
     * Create an HtmlString from trusted HTML content (no escaping).
     *
     * @param string $html The trusted HTML content.
     * @param string $encoding The source encoding.
     * @return self A new HtmlString instance with trusted content.
     */
    public static function trusted(string $html, string $encoding = 'UTF-8'): static
    {
        return new static($html, $encoding, false);
    }

    /**
     * Create an HtmlString from safe HTML content with tag filtering.
     *
     * @param string $html The HTML content to filter.
     * @param array|null $allowedTags Custom allowed tags (null for default).
     * @param array|null $allowedAttributes Custom allowed attributes (null for default).
     * @param string $encoding The source encoding.
     * @return self A new HtmlString instance with filtered content.
     */
    public static function safe(
        string $html, 
        ?array $allowedTags = null, 
        ?array $allowedAttributes = null,
        string $encoding = 'UTF-8'
    ): static {
        $tags = $allowedTags ?? self::$allowedTags;
        $attributes = $allowedAttributes ?? self::$allowedAttributes;
        
        // Build allowed tags string for strip_tags
        $allowedTagsString = '<' . implode('><', $tags) . '>';
        
        // Strip disallowed tags
        $filtered = strip_tags($html, $allowedTagsString);
        
        // Remove dangerous attributes using DOM filtering
        $cleaned = self::filterAttributes($filtered, $attributes);
        
        return new static($cleaned, $encoding, false);
    }

    /**
     * Create an empty HtmlString.
     *
     * @return self A new empty HtmlString instance.
     */
    public static function empty(): static
    {
        return new static('', 'UTF-8', true);
    }

    /**
     * Get a substring from the HTML string.
     *
     * @param int $start The starting index.
     * @param int|null $length The length of the substring (null for rest).
     * @return static A new HtmlString with the substring.
     * @throws InvalidArgumentException If start is out of bounds.
     */
    public function substring(int $start, ?int $length = null): static
    {
        if ($start < 0 || $start > $this->length()) {
            throw new InvalidArgumentException("Start index {$start} is out of bounds.");
        }

        $substr = mb_substr($this->string, $start, $length, 'UTF-8');
        return new static($substr, 'UTF-8', $this->isEscaped);
    }

    /**
     * Concatenate with another string, ensuring proper escaping.
     *
     * @param string|AbstractUnicodeString $other The string to concatenate.
     * @return static A new HtmlString with the concatenated result.
     */
    public function concat(string|AbstractUnicodeString $other): static
    {
        if ($other instanceof HtmlString) {
            // Both are HtmlStrings - combine appropriately
            $otherString = $other->string;
            $resultEscaped = $this->isEscaped && $other->isEscaped;
        } elseif ($other instanceof AbstractUnicodeString) {
            // Other is AbstractUnicodeString - get its string representation
            $otherString = htmlspecialchars($other->toString(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $resultEscaped = $this->isEscaped;
        } else {
            // Other is raw string - escape it
            $otherString = htmlspecialchars($other, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $resultEscaped = $this->isEscaped;
        }

        return new static($this->string . $otherString, 'UTF-8', $resultEscaped);
    }

    /**
     * Convert the HTML string to lowercase while preserving HTML tags.
     *
     * @return static A new HtmlString in lowercase.
     */
    public function toLowerCase(): static
    {
        if ($this->isEscaped) {
            // Simple case for escaped content
            $lower = mb_strtolower($this->string, 'UTF-8');
        } else {
            // Preserve HTML tags while converting text to lowercase
            $lower = preg_replace_callback(
                '/>[^<]*</',
                fn($matches) => mb_strtolower($matches[0], 'UTF-8'),
                $this->string
            );
            if ($lower === null) {
                $lower = mb_strtolower($this->string, 'UTF-8');
            }
        }

        return new static($lower, 'UTF-8', $this->isEscaped);
    }

    /**
     * Convert the HTML string to uppercase while preserving HTML tags.
     *
     * @return static A new HtmlString in uppercase.
     */
    public function toUpperCase(): static
    {
        if ($this->isEscaped) {
            // Simple case for escaped content
            $upper = mb_strtoupper($this->string, 'UTF-8');
        } else {
            // Preserve HTML tags while converting text to uppercase
            $upper = preg_replace_callback(
                '/>[^<]*</',
                fn($matches) => mb_strtoupper($matches[0], 'UTF-8'),
                $this->string
            );
            if ($upper === null) {
                $upper = mb_strtoupper($this->string, 'UTF-8');
            }
        }

        return new static($upper, 'UTF-8', $this->isEscaped);
    }

    /**
     * Convert the HTML string to title case while preserving HTML tags.
     *
     * @return static A new HtmlString in title case.
     */
    public function toTitleCase(): static
    {
        if ($this->isEscaped) {
            // Simple case for escaped content
            $title = mb_convert_case($this->string, MB_CASE_TITLE, 'UTF-8');
        } else {
            // Preserve HTML tags while converting text to title case
            $title = preg_replace_callback(
                '/>[^<]*</',
                fn($matches) => mb_convert_case($matches[0], MB_CASE_TITLE, 'UTF-8'),
                $this->string
            );
            if ($title === null) {
                $title = mb_convert_case($this->string, MB_CASE_TITLE, 'UTF-8');
            }
        }

        return new static($title, 'UTF-8', $this->isEscaped);
    }

    /**
     * Strip all HTML tags and return as plain text.
     *
     * @return UnicodeString A new UnicodeString with HTML tags removed.
     */
    public function toPlainText(): UnicodeString
    {
        $plain = strip_tags($this->string);
        return new UnicodeString(html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Get the raw HTML content.
     *
     * @return string The raw HTML string.
     */
    public function toHtml(): string
    {
        return $this->string;
    }

    /**
     * Check if the content is HTML-escaped.
     *
     * @return bool True if the content is escaped.
     */
    public function isEscaped(): bool
    {
        return $this->isEscaped;
    }

    /**
     * Escape the HTML content if not already escaped.
     *
     * @return static A new HtmlString with escaped content.
     */
    public function escape(): static
    {
        if ($this->isEscaped) {
            return $this;
        }

        $escaped = htmlspecialchars($this->string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return new static($escaped, 'UTF-8', true);
    }

    /**
     * Unescape the HTML content if escaped.
     *
     * @return static A new HtmlString with unescaped content.
     */
    public function unescape(): static
    {
        if (!$this->isEscaped) {
            return $this;
        }

        $unescaped = html_entity_decode($this->string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return new static($unescaped, 'UTF-8', false);
    }

    /**
     * Validate that the HTML is well-formed.
     *
     * @return bool True if the HTML is well-formed.
     */
    public function isValidHtml(): bool
    {
        if ($this->isEscaped) {
            return true; // Escaped content is always valid
        }

        // Use DOMDocument to validate HTML structure
        $dom = new \DOMDocument();
        $originalErrorSetting = libxml_use_internal_errors(true);
        
        try {
            $result = $dom->loadHTML(
                '<!DOCTYPE html><html><body>' . $this->string . '</body></html>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );
            
            $errors = libxml_get_errors();
            libxml_clear_errors();
            
            return $result && empty($errors);
        } finally {
            libxml_use_internal_errors($originalErrorSetting);
        }
    }

    /**
     * Get HTML tag count.
     *
     * @return int The number of HTML tags in the string.
     */
    public function getTagCount(): int
    {
        if ($this->isEscaped) {
            return 0;
        }

        return preg_match_all('/<[^>]+>/', $this->string);
    }

    /**
     * Extract all HTML tags from the string.
     *
     * @return array An array of HTML tags found in the string.
     */
    public function extractTags(): array
    {
        if ($this->isEscaped) {
            return [];
        }

        preg_match_all('/<([a-zA-Z][a-zA-Z0-9]*)[^>]*>/', $this->string, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Check if the string contains potentially dangerous content.
     *
     * @return bool True if dangerous content is detected.
     */
    public function hasDangerousContent(): bool
    {
        if ($this->isEscaped) {
            return false;
        }

        $dangerousPatterns = [
            '/javascript:/i',
            '/on\w+\s*=/i',  // Event handlers
            '/<script[^>]*>/i',
            '/<iframe[^>]*>/i',
            '/<object[^>]*>/i',
            '/<embed[^>]*>/i',
            '/<form[^>]*>/i',
            '/data:text\/html/i',
            '/vbscript:/i'
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $this->string)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter dangerous attributes from HTML content.
     *
     * @param string $html The HTML content to filter.
     * @param array $allowedAttributes The allowed attributes.
     * @return string The filtered HTML content.
     */
    private static function filterAttributes(string $html, array $allowedAttributes): string
    {
        // Use preg_replace_callback to handle all attributes in each tag
        $pattern = '/<([^>\/\s]+)([^>]*)>/';
        
        return preg_replace_callback($pattern, function($matches) use ($allowedAttributes) {
            $tagName = $matches[1];
            $attributes = $matches[2];
            
            // Extract all attributes
            $attrPattern = '/\s+(\w+)\s*=\s*(["\'])([^"\']*)\2/';
            $filteredAttributes = '';
            
            preg_replace_callback($attrPattern, function($attrMatches) use ($allowedAttributes, &$filteredAttributes) {
                $attrName = $attrMatches[1];
                $quote = $attrMatches[2];
                $attrValue = $attrMatches[3];
                
                // Check if attribute is allowed
                if (in_array($attrName, $allowedAttributes, true)) {
                    // Additional validation for URLs
                    if (($attrName === 'href' || $attrName === 'src') && 
                        preg_match('/^(javascript|vbscript|data):/i', $attrValue)) {
                        return ''; // Skip dangerous URLs
                    }
                    
                    $filteredAttributes .= ' ' . $attrName . '=' . $quote . $attrValue . $quote;
                }
                return '';
            }, $attributes);
            
            return '<' . $tagName . $filteredAttributes . '>';
        }, $html);
    }

    /**
     * Configure allowed tags for safe HTML mode.
     *
     * @param array $tags The allowed HTML tags.
     * @return void
     */
    public static function setAllowedTags(array $tags): void
    {
        self::$allowedTags = $tags;
    }

    /**
     * Configure allowed attributes for safe HTML mode.
     *
     * @param array $attributes The allowed HTML attributes.
     * @return void
     */
    public static function setAllowedAttributes(array $attributes): void
    {
        self::$allowedAttributes = $attributes;
    }

    /**
     * Get currently allowed tags.
     *
     * @return array The allowed HTML tags.
     */
    public static function getAllowedTags(): array
    {
        return self::$allowedTags;
    }

    /**
     * Get currently allowed attributes.
     *
     * @return array The allowed HTML attributes.
     */
    public static function getAllowedAttributes(): array
    {
        return self::$allowedAttributes;
    }
}
