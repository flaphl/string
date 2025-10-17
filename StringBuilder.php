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

/**
 * StringBuilder: Ideal for single-threaded string web applications.
 * 
 * Provides efficient string concatenation and manipulation.
 * @package Flaphl\Element\String
 * @author Jade Phyressi <jade@flaphl.com>
 */

class StringBuilder 
{
    /**
     * @var \SplFixedArray Internal buffer using fixed array for better memory management.
     */
    private \SplFixedArray $buffer;

    /**
     * @var int Current number of chunks in buffer.
     */
    private int $chunkCount = 0;

    /**
     * @var int Current buffer capacity.
     */
    private int $capacity;

    /**
     * @var int Maximum chunk size before consolidation.
     */
    private int $maxChunkSize;

    /**
     * @var string|null Cached result to avoid repeated concatenation.
     */
    private ?string $cachedResult = null;

    /**
     * @var bool Whether the cached result is dirty and needs regeneration.
     */
    private bool $isDirty = false;

    /**
     * Constructor to initialize the StringBuilder with an optional initial string.
     *
     * @param string $initial The initial string to start with.
     * @param int $initialCapacity Initial buffer capacity (default: 16).
     * @param int $maxChunkSize Maximum size of individual chunks (default: 4096).
     */
    public function __construct(string $initial = '', int $initialCapacity = 16, int $maxChunkSize = 4096)
    {
        $this->capacity = max($initialCapacity, 1);
        $this->maxChunkSize = max($maxChunkSize, 256);
        $this->buffer = new \SplFixedArray($this->capacity);
        
        if ($initial !== '') {
            $this->append($initial);
        }
    }

    /**
     * Append a string to the current string with smart chunking.
     *
     * @param string $string The string to append.
     * @return self
     */
    public function append(string $string): self
    {
        if ($string === '') {
            return $this;
        }

        // Invalidate cache
        $this->isDirty = true;
        $this->cachedResult = null;

        // Try to merge with last chunk if it's small enough
        if ($this->chunkCount > 0) {
            $lastChunk = $this->buffer[$this->chunkCount - 1];
            $combinedLength = strlen($lastChunk) + strlen($string);
            
            if ($combinedLength <= $this->maxChunkSize) {
                $this->buffer[$this->chunkCount - 1] = $lastChunk . $string;
                return $this;
            }
        }

        // Need to add as new chunk - expand buffer if necessary
        if ($this->chunkCount >= $this->capacity) {
            $this->expandBuffer();
        }

        $this->buffer[$this->chunkCount] = $string;
        $this->chunkCount++;

        // Consolidate if we have too many small chunks
        if ($this->chunkCount > 32 && $this->shouldConsolidate()) {
            $this->consolidateChunks();
        }

        return $this;
    }

    /**
     * Get the current string with caching for performance.
     *
     * @return string The current concatenated string.
     */
    public function getString(): string
    {
        if (!$this->isDirty && $this->cachedResult !== null) {
            return $this->cachedResult;
        }

        if ($this->chunkCount === 0) {
            $this->cachedResult = '';
        } elseif ($this->chunkCount === 1) {
            $this->cachedResult = $this->buffer[0];
        } else {
            // Build array for implode (faster than string concatenation)
            $chunks = [];
            for ($i = 0; $i < $this->chunkCount; $i++) {
                $chunks[] = $this->buffer[$i];
            }
            $this->cachedResult = implode('', $chunks);
        }

        $this->isDirty = false;
        return $this->cachedResult;
    }

    /**
     * Expand the internal buffer capacity.
     *
     * @return void
     */
    private function expandBuffer(): void
    {
        $newCapacity = $this->capacity * 2;
        $newBuffer = new \SplFixedArray($newCapacity);
        
        for ($i = 0; $i < $this->chunkCount; $i++) {
            $newBuffer[$i] = $this->buffer[$i];
        }
        
        $this->buffer = $newBuffer;
        $this->capacity = $newCapacity;
    }

    /**
     * Check if chunks should be consolidated based on their sizes.
     *
     * @return bool
     */
    private function shouldConsolidate(): bool
    {
        $smallChunks = 0;
        $totalSize = 0;
        
        for ($i = 0; $i < $this->chunkCount; $i++) {
            $chunkSize = strlen($this->buffer[$i]);
            $totalSize += $chunkSize;
            
            if ($chunkSize < 256) {
                $smallChunks++;
            }
        }

        // Consolidate if more than 60% are small chunks or average chunk size is too small
        return $smallChunks > ($this->chunkCount * 0.6) || ($totalSize / $this->chunkCount) < 128;
    }

    /**
     * Consolidate multiple small chunks into fewer larger ones.
     *
     * @return void
     */
    private function consolidateChunks(): void
    {
        if ($this->chunkCount <= 1) {
            return;
        }

        $consolidated = [];
        $currentChunk = '';
        
        for ($i = 0; $i < $this->chunkCount; $i++) {
            $chunk = $this->buffer[$i];
            
            if (strlen($currentChunk) + strlen($chunk) <= $this->maxChunkSize) {
                $currentChunk .= $chunk;
            } else {
                if ($currentChunk !== '') {
                    $consolidated[] = $currentChunk;
                }
                $currentChunk = $chunk;
            }
        }
        
        if ($currentChunk !== '') {
            $consolidated[] = $currentChunk;
        }

        // Update buffer with consolidated chunks
        $this->chunkCount = count($consolidated);
        for ($i = 0; $i < $this->chunkCount; $i++) {
            $this->buffer[$i] = $consolidated[$i];
        }
        
        // Clear remaining slots
        for ($i = $this->chunkCount; $i < $this->capacity; $i++) {
            $this->buffer[$i] = null;
        }
    }

    /**
     * Clear the buffer and reset to initial state.
     *
     * @return self
     */
    public function clear(): self
    {
        $this->chunkCount = 0;
        $this->cachedResult = '';
        $this->isDirty = false;
        
        // Clear buffer references
        for ($i = 0; $i < $this->capacity; $i++) {
            $this->buffer[$i] = null;
        }
        
        return $this;
    }

    /**
     * Get the current length without building the full string.
     *
     * @return int
     */
    public function length(): int
    {
        $length = 0;
        for ($i = 0; $i < $this->chunkCount; $i++) {
            $length += mb_strlen($this->buffer[$i], 'UTF-8');
        }
        return $length;
    }

    /**
     * Check if the buffer is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->chunkCount === 0;
    }
}