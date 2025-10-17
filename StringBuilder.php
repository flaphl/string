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
     * @var string The internal string storage.
     */
    private string $string = '';

    /**
     * @var array An array to hold parts of the string for efficient concatenation.
     */
    private array $parts = [];

    /**
     * Constructor to initialize the StringBuilder with an optional initial string.
     *
     * @param string $initial The initial string to start with.
     */
    public function __construct(string $initial = '')
    {
        $this->string = '';
        if ($initial !== '') {
            $this->append($initial);
        }
    }

    /**
     * Append a string to the current string.
     *
     * @param string $string The string to append.
     * @return self
     */
    public function append(string $string): self
    {
        $this->parts[] = $string;
        return $this;
    }

    /**
     * Get the current string.
     *
     * @return string The current concatenated string.
     */
    public function getString(): string
    {
        return implode('', $this->parts) . $this->string;
    }
}