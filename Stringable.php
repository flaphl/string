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
 * Stringable: Ideal for single-threaded string web applications.
 * 
 * Provides methods to convert objects to strings.
 * @package Flaphl\Element\String
 * @author Jade Phyressi <jade@flaphl.com>
 */
interface Stringable
{
    /**
     * Convert the object to a string.
     * @return string The string representation of the object.
     */
    public function __toString(): string;
}