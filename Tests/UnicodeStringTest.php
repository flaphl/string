<?php

/**
 * This file is part of the Flaphl package.
 * 
 * (c) Jade Phyressi <jade@flaphl.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flaphl\Element\String\Tests;

use Flaphl\Element\String\UnicodeString;
use Flaphl\Element\String\Exceptions\InvalidArgumentException;
use Flaphl\Element\String\Exceptions\UnicodeException;
use PHPUnit\Framework\TestCase;

/**
 * Test case for UnicodeString class.
 * 
 * @package Flaphl\Element\String\Tests
 * @author Jade Phyressi <jade@flaphl.com>
 */
class UnicodeStringTest extends TestCase
{
    public function testConstructor(): void
    {
        $string = new UnicodeString('Hello World');
        $this->assertEquals('Hello World', $string->toString());
        $this->assertEquals('UTF-8', $string->getEncoding());
    }

    public function testConstructorWithEmptyString(): void
    {
        $string = new UnicodeString('');
        $this->assertTrue($string->isEmpty());
        $this->assertEquals(0, $string->length());
    }

    public function testConstructorWithEncoding(): void
    {
        $latin1String = mb_convert_encoding('Héllo', 'ISO-8859-1', 'UTF-8');
        $string = new UnicodeString($latin1String, 'ISO-8859-1');
        $this->assertEquals('Héllo', $string->toString());
    }

    public function testConstructorWithInvalidEncoding(): void
    {
        $this->expectException(UnicodeException::class);
        $this->expectExceptionMessage('Cannot convert string from INVALID to UTF-8');
        new UnicodeString('test', 'INVALID');
    }

    public function testConstructorWithInvalidUtf8(): void
    {
        $this->expectException(UnicodeException::class);
        $this->expectExceptionMessage('Invalid UTF-8 string provided');
        new UnicodeString("\xFF\xFE");
    }

    public function testOf(): void
    {
        $string = UnicodeString::of('Hello');
        $this->assertInstanceOf(UnicodeString::class, $string);
        $this->assertEquals('Hello', $string->toString());
    }

    public function testEmpty(): void
    {
        $string = UnicodeString::empty();
        $this->assertTrue($string->isEmpty());
        $this->assertEquals(0, $string->length());
    }

    public function testLength(): void
    {
        $string = new UnicodeString('Hello');
        $this->assertEquals(5, $string->length());

        // Test with Unicode characters
        $unicode = new UnicodeString('Héllö 🌍');
        $this->assertEquals(7, $unicode->length());
    }

    public function testByteLength(): void
    {
        $string = new UnicodeString('Hello');
        $this->assertEquals(5, $string->byteLength());

        // Unicode string has more bytes than characters
        $unicode = new UnicodeString('Héllö 🌍');
        $this->assertGreaterThan($unicode->length(), $unicode->byteLength());
    }

    public function testIsEmpty(): void
    {
        $empty = new UnicodeString('');
        $this->assertTrue($empty->isEmpty());

        $notEmpty = new UnicodeString('test');
        $this->assertFalse($notEmpty->isEmpty());
    }

    public function testCharAt(): void
    {
        $string = new UnicodeString('Hello');
        $this->assertEquals('H', $string->charAt(0));
        $this->assertEquals('e', $string->charAt(1));
        $this->assertEquals('o', $string->charAt(4));

        // Unicode character
        $unicode = new UnicodeString('🌍');
        $this->assertEquals('🌍', $unicode->charAt(0));
    }

    public function testCharAtOutOfBounds(): void
    {
        $string = new UnicodeString('Hello');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Index 5 is out of bounds for string length 5');
        $string->charAt(5);
    }

    public function testCharAtNegativeIndex(): void
    {
        $string = new UnicodeString('Hello');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Index -1 is out of bounds for string length 5');
        $string->charAt(-1);
    }

    public function testCodePointAt(): void
    {
        $string = new UnicodeString('A');
        $this->assertEquals(65, $string->codePointAt(0)); // 'A' = 65

        $unicode = new UnicodeString('🌍');
        $this->assertEquals(127757, $unicode->codePointAt(0)); // Earth emoji
    }

    public function testSubstring(): void
    {
        $string = new UnicodeString('Hello World');
        
        $sub1 = $string->substring(0, 5);
        $this->assertEquals('Hello', $sub1->toString());
        
        $sub2 = $string->substring(6);
        $this->assertEquals('World', $sub2->toString());
        
        $sub3 = $string->substring(6, 3);
        $this->assertEquals('Wor', $sub3->toString());
    }

    public function testSubstringOutOfBounds(): void
    {
        $string = new UnicodeString('Hello');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Start index 6 is out of bounds');
        $string->substring(6);
    }

    public function testConcat(): void
    {
        $string1 = new UnicodeString('Hello');
        $string2 = new UnicodeString(' World');
        
        $result = $string1->concat($string2);
        $this->assertEquals('Hello World', $result->toString());
        
        // Test with regular string
        $result2 = $string1->concat(' PHP');
        $this->assertEquals('Hello PHP', $result2->toString());
    }

    public function testContains(): void
    {
        $string = new UnicodeString('Hello World');
        
        $this->assertTrue($string->contains('World'));
        $this->assertTrue($string->contains('ello'));
        $this->assertFalse($string->contains('universe'));
        
        // Case sensitivity
        $this->assertFalse($string->contains('world'));
        $this->assertTrue($string->contains('world', false));
    }

    public function testIndexOf(): void
    {
        $string = new UnicodeString('Hello World');
        
        $this->assertEquals(0, $string->indexOf('Hello'));
        $this->assertEquals(6, $string->indexOf('World'));
        $this->assertEquals(1, $string->indexOf('ello'));
        $this->assertFalse($string->indexOf('universe'));
        
        // Case insensitive
        $this->assertFalse($string->indexOf('world'));
        $this->assertEquals(6, $string->indexOf('world', 0, false));
    }

    public function testLastIndexOf(): void
    {
        $string = new UnicodeString('Hello Hello World');
        
        $this->assertEquals(6, $string->lastIndexOf('Hello'));
        $this->assertEquals(12, $string->lastIndexOf('World'));
        $this->assertFalse($string->lastIndexOf('universe'));
    }

    public function testStartsWith(): void
    {
        $string = new UnicodeString('Hello World');
        
        $this->assertTrue($string->startsWith('Hello'));
        $this->assertTrue($string->startsWith('H'));
        $this->assertFalse($string->startsWith('World'));
        
        // Case insensitive
        $this->assertFalse($string->startsWith('hello'));
        $this->assertTrue($string->startsWith('hello', false));
        
        // Empty string
        $this->assertTrue($string->startsWith(''));
    }

    public function testEndsWith(): void
    {
        $string = new UnicodeString('Hello World');
        
        $this->assertTrue($string->endsWith('World'));
        $this->assertTrue($string->endsWith('d'));
        $this->assertFalse($string->endsWith('Hello'));
        
        // Case insensitive
        $this->assertFalse($string->endsWith('world'));
        $this->assertTrue($string->endsWith('world', false));
        
        // Empty string
        $this->assertTrue($string->endsWith(''));
    }

    public function testToLowerCase(): void
    {
        $string = new UnicodeString('Hello WORLD');
        $lower = $string->toLowerCase();
        
        $this->assertEquals('hello world', $lower->toString());
        $this->assertEquals('Hello WORLD', $string->toString()); // Original unchanged
    }

    public function testToUpperCase(): void
    {
        $string = new UnicodeString('Hello world');
        $upper = $string->toUpperCase();
        
        $this->assertEquals('HELLO WORLD', $upper->toString());
        $this->assertEquals('Hello world', $string->toString()); // Original unchanged
    }

    public function testToTitleCase(): void
    {
        $string = new UnicodeString('hello world');
        $title = $string->toTitleCase();
        
        $this->assertEquals('Hello World', $title->toString());
    }

    public function testTrim(): void
    {
        $string = new UnicodeString('  Hello World  ');
        $trimmed = $string->trim();
        
        $this->assertEquals('Hello World', $trimmed->toString());
        
        // Custom characters
        $custom = new UnicodeString('...Hello World...');
        $trimmedCustom = $custom->trim('.');
        $this->assertEquals('Hello World', $trimmedCustom->toString());
    }

    public function testTrimLeft(): void
    {
        $string = new UnicodeString('  Hello World  ');
        $trimmed = $string->trimLeft();
        
        $this->assertEquals('Hello World  ', $trimmed->toString());
    }

    public function testTrimRight(): void
    {
        $string = new UnicodeString('  Hello World  ');
        $trimmed = $string->trimRight();
        
        $this->assertEquals('  Hello World', $trimmed->toString());
    }

    public function testReplace(): void
    {
        $string = new UnicodeString('Hello World');
        
        $replaced = $string->replace('World', 'Universe');
        $this->assertEquals('Hello Universe', $replaced->toString());
        
        // Case insensitive
        $replaced2 = $string->replace('world', 'Universe', false);
        $this->assertEquals('Hello Universe', $replaced2->toString());
    }

    public function testSplit(): void
    {
        $string = new UnicodeString('Hello,World,PHP');
        $parts = $string->split(',');
        
        $this->assertCount(3, $parts);
        $this->assertEquals('Hello', $parts[0]->toString());
        $this->assertEquals('World', $parts[1]->toString());
        $this->assertEquals('PHP', $parts[2]->toString());
        
        // With limit
        $limitedParts = $string->split(',', 2);
        $this->assertCount(2, $limitedParts);
        $this->assertEquals('Hello', $limitedParts[0]->toString());
        $this->assertEquals('World,PHP', $limitedParts[1]->toString());
    }

    public function testReverse(): void
    {
        $string = new UnicodeString('Hello');
        $reversed = $string->reverse();
        
        $this->assertEquals('olleH', $reversed->toString());
        
        // Unicode characters
        $unicode = new UnicodeString('🌍🚀');
        $reversedUnicode = $unicode->reverse();
        $this->assertEquals('🚀🌍', $reversedUnicode->toString());
    }

    public function testRepeat(): void
    {
        $string = new UnicodeString('Hi');
        $repeated = $string->repeat(3);
        
        $this->assertEquals('HiHiHi', $repeated->toString());
        
        // Zero times
        $zero = $string->repeat(0);
        $this->assertTrue($zero->isEmpty());
    }

    public function testRepeatNegative(): void
    {
        $string = new UnicodeString('Hi');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Repeat count cannot be negative');
        $string->repeat(-1);
    }

    public function testPad(): void
    {
        $string = new UnicodeString('Hi');
        
        // Right padding (default)
        $paddedRight = $string->pad(5);
        $this->assertEquals('Hi   ', $paddedRight->toString());
        
        // Left padding
        $paddedLeft = $string->pad(5, ' ', STR_PAD_LEFT);
        $this->assertEquals('   Hi', $paddedLeft->toString());
        
        // Both sides
        $paddedBoth = $string->pad(5, ' ', STR_PAD_BOTH);
        $this->assertEquals(' Hi  ', $paddedBoth->toString());
        
        // Custom padding string
        $customPad = $string->pad(6, '.');
        $this->assertEquals('Hi....', $customPad->toString());
    }

    public function testCompareTo(): void
    {
        $string1 = new UnicodeString('apple');
        $string2 = new UnicodeString('banana');
        $string3 = new UnicodeString('apple');
        
        $this->assertLessThan(0, $string1->compareTo($string2));
        $this->assertGreaterThan(0, $string2->compareTo($string1));
        $this->assertEquals(0, $string1->compareTo($string3));
        
        // Case insensitive
        $upper = new UnicodeString('APPLE');
        $this->assertNotEquals(0, $string1->compareTo($upper));
        $this->assertEquals(0, $string1->compareTo($upper, false));
    }

    public function testEquals(): void
    {
        $string1 = new UnicodeString('Hello');
        $string2 = new UnicodeString('Hello');
        $string3 = new UnicodeString('World');
        
        $this->assertTrue($string1->equals($string2));
        $this->assertFalse($string1->equals($string3));
        
        // Case insensitive
        $upper = new UnicodeString('HELLO');
        $this->assertFalse($string1->equals($upper));
        $this->assertTrue($string1->equals($upper, false));
    }

    public function testToCharArray(): void
    {
        $string = new UnicodeString('Hi🌍');
        $chars = $string->toCharArray();
        
        $this->assertEquals(['H', 'i', '🌍'], $chars);
    }

    public function testToString(): void
    {
        $string = new UnicodeString('Hello World');
        $this->assertEquals('Hello World', $string->toString());
        $this->assertEquals('Hello World', (string) $string);
    }

    public function testGetEncoding(): void
    {
        $string = new UnicodeString('Hello');
        $this->assertEquals('UTF-8', $string->getEncoding());
    }

    public function testIsValidUtf8(): void
    {
        $string = new UnicodeString('Hello 🌍');
        $this->assertTrue($string->isValidUtf8());
    }

    public function testNormalize(): void
    {
        if (!class_exists('Normalizer')) {
            $this->markTestSkipped('Intl extension not available');
        }
        
        // Create a string with combining characters
        $combining = "e\u{0301}"; // e + combining acute accent
        $string = new UnicodeString($combining);
        
        $normalized = $string->normalize();
        $this->assertInstanceOf(UnicodeString::class, $normalized);
    }

    public function testNormalizeWithoutIntl(): void
    {
        if (class_exists('Normalizer')) {
            $this->markTestSkipped('Intl extension is available');
        }
        
        $string = new UnicodeString('test');
        
        $this->expectException(UnicodeException::class);
        $this->expectExceptionMessage('Intl extension is required for Unicode normalization');
        $string->normalize();
    }
}
