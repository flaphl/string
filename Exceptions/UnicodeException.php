<?php

/**
 * This file is part of the Flaphl package.
 * 
 * (c) Jade Phyressi <jade@flaphl.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flaphl\Element\String\Exceptions;

use RuntimeException;

/**
 * Exception thrown when Unicode operations fail or encounter invalid data.
 * 
 * This exception is specifically for Unicode-related errors such as
 * invalid UTF-8 sequences, encoding/decoding failures, or unsupported
 * Unicode operations within the String element.
 * 
 * @package Flaphl\Element\String\Exceptions
 * @author Jade Phyressi <jade@flaphl.com>
 */
class UnicodeException extends RuntimeException implements ExceptionInterface
{
}