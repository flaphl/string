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

/**
 * StringBuffer: Thread-safe, mutable sequence of characters. 
 * Useful for multi-threaded applications and command line tools.
 * 
 * Provides synchronized access to string operations to ensure thread safety
 * when multiple processes or threads are manipulating the same buffer.
 * 
 * @package Flaphl\Element\String
 * @author Jade Phyressi <jade@flaphl.com>
 */
class StringBuffer implements Stringable
{
    /**
     * @var array Buffer storage as array of string parts for efficient manipulation.
     */
    private array $buffer = [];

    /**
     * @var int Current length of the buffer content.
     */
    private int $length = 0;

    /**
     * @var resource|null File lock resource for thread synchronization.
     */
    private $lockFile = null;

    /**
     * @var string Path to the lock file for synchronization.
     */
    private string $lockPath;

    /**
     * Constructor to initialize the StringBuffer with optional initial content.
     *
     * @param string $initial The initial string content.
     */
    public function __construct(string $initial = '')
    {
        $this->lockPath = sys_get_temp_dir() . '/flaphl_stringbuffer_' . uniqid() . '.lock';
        
        if ($initial !== '') {
            $this->append($initial);
        }
    }

    /**
     * Destructor to clean up lock file.
     */
    public function __destruct()
    {
        $this->releaseLock();
        if (file_exists($this->lockPath)) {
            @unlink($this->lockPath);
        }
    }

    /**
     * Acquire exclusive lock for thread-safe operations.
     *
     * @return void
     */
    private function acquireLock(): void
    {
        if ($this->lockFile === null) {
            $this->lockFile = fopen($this->lockPath, 'c+');
            flock($this->lockFile, LOCK_EX);
        }
    }

    /**
     * Release the exclusive lock.
     *
     * @return void
     */
    private function releaseLock(): void
    {
        if ($this->lockFile !== null) {
            flock($this->lockFile, LOCK_UN);
            fclose($this->lockFile);
            $this->lockFile = null;
        }
    }

    /**
     * Append a string to the buffer in a thread-safe manner.
     *
     * @param string $string The string to append.
     * @return self Returns this instance for method chaining.
     */
    public function append(string $string): self
    {
        $this->acquireLock();
        try {
            $this->buffer[] = $string;
            $this->length += mb_strlen($string, 'UTF-8');
            return $this;
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Prepend a string to the buffer in a thread-safe manner.
     *
     * @param string $string The string to prepend.
     * @return self Returns this instance for method chaining.
     */
    public function prepend(string $string): self
    {
        $this->acquireLock();
        try {
            array_unshift($this->buffer, $string);
            $this->length += mb_strlen($string, 'UTF-8');
            return $this;
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Insert a string at the specified position.
     *
     * @param int $offset The position to insert at.
     * @param string $string The string to insert.
     * @return self Returns this instance for method chaining.
     * @throws InvalidArgumentException If offset is negative or greater than length.
     */
    public function insert(int $offset, string $string): self
    {
        if ($offset < 0 || $offset > $this->length) {
            throw new InvalidArgumentException("Position {$offset} is out of bounds for buffer length {$this->length}.");
        }

        $this->acquireLock();
        try {
            // Convert buffer to string, insert, then back to buffer
            $current = implode('', $this->buffer);
            $before = mb_substr($current, 0, $offset, 'UTF-8');
            $after = mb_substr($current, $offset, null, 'UTF-8');
            
            $this->buffer = [$before, $string, $after];
            $this->length += mb_strlen($string, 'UTF-8');
            
            return $this;
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Delete characters from the buffer.
     *
     * @param int $start The starting position.
     * @param int $length The number of characters to delete.
     * @return self Returns this instance for method chaining.
     * @throws InvalidArgumentException If parameters are invalid.
     */
    public function delete(int $start, int $length): self
    {
        if ($start < 0 || $start >= $this->length) {
            throw new InvalidArgumentException("Start position {$start} is out of bounds.");
        }

        if ($length < 0) {
            throw new InvalidArgumentException("Length cannot be negative.");
        }

        $this->acquireLock();
        try {
            $current = implode('', $this->buffer);
            $before = mb_substr($current, 0, $start, 'UTF-8');
            $after = mb_substr($current, $start + $length, null, 'UTF-8');
            
            $this->buffer = [$before . $after];
            $this->length = mb_strlen($before . $after, 'UTF-8');
            
            return $this;
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Replace a portion of the buffer with new content.
     *
     * @param int $start The starting position.
     * @param int $length The number of characters to replace.
     * @param string $replacement The replacement string.
     * @return self Returns this instance for method chaining.
     * @throws InvalidArgumentException If parameters are invalid.
     */
    public function replace(int $start, int $length, string $replacement): self
    {
        if ($start < 0 || $start >= $this->length) {
            throw new InvalidArgumentException("Start position {$start} is out of bounds.");
        }

        if ($length < 0) {
            throw new InvalidArgumentException("Length cannot be negative.");
        }

        $this->acquireLock();
        try {
            $current = implode('', $this->buffer);
            $before = mb_substr($current, 0, $start, 'UTF-8');
            $after = mb_substr($current, $start + $length, null, 'UTF-8');
            
            $newContent = $before . $replacement . $after;
            $this->buffer = [$newContent];
            $this->length = mb_strlen($newContent, 'UTF-8');
            
            return $this;
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Reverse the contents of the buffer.
     *
     * @return self Returns this instance for method chaining.
     */
    public function reverse(): self
    {
        $this->acquireLock();
        try {
            $content = implode('', $this->buffer);
            $reversed = strrev($content);
            $this->buffer = [$reversed];
            return $this;
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Clear the buffer contents.
     *
     * @return self Returns this instance for method chaining.
     */
    public function clear(): self
    {
        $this->acquireLock();
        try {
            $this->buffer = [];
            $this->length = 0;
            return $this;
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Get the current length of the buffer.
     *
     * @return int The length of the buffer contents.
     */
    public function length(): int
    {
        $this->acquireLock();
        try {
            return $this->length;
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Get the capacity of the buffer (current array size).
     *
     * @return int The current capacity.
     */
    public function capacity(): int
    {
        $this->acquireLock();
        try {
            return count($this->buffer);
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Get a character at the specified index.
     *
     * @param int $index The index of the character.
     * @return string The character at the specified index.
     * @throws InvalidArgumentException If index is out of bounds.
     */
    public function charAt(int $index): string
    {
        if ($index < 0 || $index >= $this->length) {
            throw new InvalidArgumentException("Index {$index} is out of bounds for buffer length {$this->length}.");
        }

        $this->acquireLock();
        try {
            $content = implode('', $this->buffer);
            return mb_substr($content, $index, 1, 'UTF-8');
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Set a character at the specified index.
     *
     * @param int $index The index to set.
     * @param string $char The character to set (only first character used).
     * @return self Returns this instance for method chaining.
     * @throws InvalidArgumentException If index is out of bounds.
     */
    public function setCharAt(int $index, string $char): self
    {
        if ($index < 0 || $index >= $this->length) {
            throw new InvalidArgumentException("Index {$index} is out of bounds for buffer length {$this->length}.");
        }

        $this->acquireLock();
        try {
            $content = implode('', $this->buffer);
            $content[$index] = mb_substr($char, 0, 1, 'UTF-8');
            $this->buffer = [$content];
            return $this;
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Get a substring from the buffer.
     *
     * @param int $start The starting position.
     * @param int|null $length The length of the substring (null for rest of string).
     * @return string The substring.
     * @throws InvalidArgumentException If start position is invalid.
     */
    public function substring(int $start, ?int $length = null): string
    {
        if ($start < 0 || $start > $this->length) {
            throw new InvalidArgumentException("Start position {$start} is out of bounds.");
        }

        $this->acquireLock();
        try {
            $content = implode('', $this->buffer);
            return $length === null ? mb_substr($content, $start, null, 'UTF-8') : mb_substr($content, $start, $length, 'UTF-8');
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Find the index of the first occurrence of a substring.
     *
     * @param string $needle The substring to search for.
     * @param int $offset The offset to start searching from.
     * @return int|false The index of the first occurrence, or false if not found.
     */
    public function indexOf(string $needle, int $offset = 0): int|false
    {
        $this->acquireLock();
        try {
            $content = implode('', $this->buffer);
            return mb_strpos($content, $needle, $offset, 'UTF-8');
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Find the index of the last occurrence of a substring.
     *
     * @param string $needle The substring to search for.
     * @param int $offset The offset to start searching from.
     * @return int|false The index of the last occurrence, or false if not found.
     */
    public function lastIndexOf(string $needle, int $offset = 0): int|false
    {
        $this->acquireLock();
        try {
            $content = implode('', $this->buffer);
            return mb_strrpos($content, $needle, $offset, 'UTF-8');
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Check if the buffer is empty.
     *
     * @return bool True if the buffer is empty, false otherwise.
     */
    public function isEmpty(): bool
    {
        return $this->length === 0;
    }

    /**
     * Get the string representation of the buffer.
     *
     * @return string The complete buffer contents as a string.
     */
    public function toString(): string
    {
        $this->acquireLock();
        try {
            return implode('', $this->buffer);
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Magic method to get string representation (implements Stringable).
     *
     * @return string The complete buffer contents as a string.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Create a copy of the buffer.
     *
     * @return self A new StringBuffer instance with the same content.
     */
    public function copy(): self
    {
        $this->acquireLock();
        try {
            $newBuffer = new self();
            $newBuffer->buffer = $this->buffer;
            $newBuffer->length = $this->length;
            return $newBuffer;
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Trim whitespace from both ends of the buffer.
     *
     * @param string $characters Characters to trim (default is whitespace).
     * @return self Returns this instance for method chaining.
     */
    public function trim(string $characters = " \t\n\r\0\x0B"): self
    {
        $this->acquireLock();
        try {
            $content = implode('', $this->buffer);
            $trimmed = trim($content, $characters);
            $this->buffer = [$trimmed];
            $this->length = mb_strlen($trimmed, 'UTF-8');
            return $this;
        } finally {
            $this->releaseLock();
        }
    }
}