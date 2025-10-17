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

use InvalidArgumentException as BaseInvalidArgumentException;

/**
 * Exception thrown when an invalid argument is passed to a String element method.
 * 
 * This exception extends PHP's built-in InvalidArgumentException
 * and implements the String element's ExceptionInterface for
 * consistent exception handling within the Flaphl framework.
 * 
 * @package Flaphl\Element\String\Exceptions
 * @author Jade Phyressi <jade@flaphl.com>
 */
class InvalidArgumentException extends BaseInvalidArgumentException implements ExceptionInterface
{
}