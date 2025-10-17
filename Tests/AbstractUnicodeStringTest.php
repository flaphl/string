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

use Flaphl\Element\String\AbstractUnicodeString;
use Flaphl\Element\String\Exceptions\InvalidArgumentException;
use Flaphl\Element\String\Exceptions\UnicodeException;
use PHPUnit\Framework\TestCase;

/**
 * Concrete implementation for testing AbstractUnicodeString.
 */
class TestableUnicodeString extends AbstractUnicodeString
{
    public static function of(string $string, string $encoding = 'UTF-8'): static
    {
        return new static($string, $encoding);
    }

    public static function empty(): static
    {
        return new static('');
    }

    public function substring(int $start, ?int $length = null): static
    {
        if ($start < 0 || $start > $this->length()) {
            throw new InvalidArgumentException("Start index {$start} is out of bounds.");
        }

        $substr = mb_substr($this->string, $start, $length, 'UTF-8');
        return new static($substr);
    }

    public function concat(string|AbstractUnicodeString $other): static
    {
        $otherString = $other instanceof AbstractUnicodeString ? $other->toString() : $other;
        return new static($this->string . $otherString);
    }

    public function toLowerCase(): static
    {
        return new static(mb_strtolower($this->string, 'UTF-8'));
    }

    public function toUpperCase(): static
    {
        return new static(mb_strtoupper($this->string, 'UTF-8'));
    }

    public function toTitleCase(): static
    {
        return new static(mb_convert_case($this->string, MB_CASE_TITLE, 'UTF-8'));
    }
}

/**
 * Test case for AbstractUnicodeString class.
 * 
 * @package Flaphl\Element\String\Tests
 * @author Jade Phyressi <jade@flaphl.com>
 */
class AbstractUnicodeStringTest extends TestCase
{
    private function createString(string $content = ''): TestableUnicodeString
    {
        return new TestableUnicodeString($content);
    }

    public function testConstructor(): void
    {
        $string = $this->createString('Hello World');
        $this->assertEquals('Hello World', $string->toString());
        $this->assertEquals('UTF-8', $string->getEncoding());
    }

    public function testConstructorWithEmptyString(): void
    {
        $string = $this->createString('');
        $this->assertTrue($string->isEmpty());
        $this->assertEquals(0, $string->length());
    }

    public function testConstructorWithEncoding(): void
    {
        $latin1String = mb_convert_encoding('Héllo', 'ISO-8859-1', 'UTF-8');
        $string = new TestableUnicodeString($latin1String, 'ISO-8859-1');
        $this->assertEquals('Héllo', $string->toString());
    }

    public function testConstructorWithInvalidEncoding(): void
    {
        $this->expectException(UnicodeException::class);
        $this->expectExceptionMessage('Cannot convert string from INVALID to UTF-8');
        new TestableUnicodeString('test', 'INVALID');
    }

    public function testConstructorWithInvalidUtf8(): void
    {
        $this->expectException(UnicodeException::class);
        $this->expectExceptionMessage('Invalid UTF-8 string provided');
        new TestableUnicodeString("\xFF\xFE");
    }

    public function testLength(): void
    {
        $string = $this->createString('Hello');
        $this->assertEquals(5, $string->length());

        // Test with Unicode characters
        $unicode = $this->createString('Héllö 🌍');
        $this->assertEquals(7, $unicode->length());
    }

    public function testLengthCaching(): void
    {
        $string = $this->createString('Hello');
        
        // First call should calculate and cache
        $length1 = $string->length();
        $this->assertEquals(5, $length1);
        
        // Second call should use cached value
        $length2 = $string->length();
        $this->assertEquals(5, $length2);
        $this->assertSame($length1, $length2);
    }

    public function testByteLength(): void
    {
        $string = $this->createString('Hello');
        $this->assertEquals(5, $string->byteLength());

        // Unicode string has more bytes than characters
        $unicode = $this->createString('Héllö 🌍');
        $this->assertGreaterThan($unicode->length(), $unicode->byteLength());
    }

    public function testIsEmpty(): void
    {
        $empty = $this->createString('');
        $this->assertTrue($empty->isEmpty());

        $notEmpty = $this->createString('test');
        $this->assertFalse($notEmpty->isEmpty());
    }

    public function testCharAt(): void
    {
        $string = $this->createString('Hello');
        $this->assertEquals('H', $string->charAt(0));
        $this->assertEquals('e', $string->charAt(1));
        $this->assertEquals('o', $string->charAt(4));

        // Unicode character
        $unicode = $this->createString('🌍');
        $this->assertEquals('🌍', $unicode->charAt(0));
    }

    public function testCharAtOutOfBounds(): void
    {
        $string = $this->createString('Hello');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Index 5 is out of bounds for string length 5');
        $string->charAt(5);
    }

    public function testCharAtNegativeIndex(): void
    {
        $string = $this->createString('Hello');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Index -1 is out of bounds for string length 5');
        $string->charAt(-1);
    }

    public function testCodePointAt(): void
    {
        $string = $this->createString('A');
        $this->assertEquals(65, $string->codePointAt(0)); // 'A' = 65

        $unicode = $this->createString('🌍');
        $this->assertEquals(127757, $unicode->codePointAt(0)); // Earth emoji
    }

    public function testContains(): void
    {
        $string = $this->createString('Hello World');
        
        $this->assertTrue($string->contains('World'));
        $this->assertTrue($string->contains('ello'));
        $this->assertFalse($string->contains('universe'));
        
        // Case sensitivity
        $this->assertFalse($string->contains('world'));
        $this->assertTrue($string->contains('world', false));
        
        // Test with another AbstractUnicodeString
        $needle = $this->createString('World');
        $this->assertTrue($string->contains($needle));
    }

    public function testIndexOf(): void
    {
        $string = $this->createString('Hello World');
        
        $this->assertEquals(0, $string->indexOf('Hello'));
        $this->assertEquals(6, $string->indexOf('World'));
        $this->assertEquals(1, $string->indexOf('ello'));
        $this->assertFalse($string->indexOf('universe'));
        
        // Case insensitive
        $this->assertFalse($string->indexOf('world'));
        $this->assertEquals(6, $string->indexOf('world', 0, false));
        
        // Test with another AbstractUnicodeString
        $needle = $this->createString('World');
        $this->assertEquals(6, $string->indexOf($needle));
    }

    public function testLastIndexOf(): void
    {
        $string = $this->createString('Hello Hello World');
        
        $this->assertEquals(6, $string->lastIndexOf('Hello'));
        $this->assertEquals(12, $string->lastIndexOf('World'));
        $this->assertFalse($string->lastIndexOf('universe'));
        
        // Test with another AbstractUnicodeString
        $needle = $this->createString('Hello');
        $this->assertEquals(6, $string->lastIndexOf($needle));
    }

    public function testStartsWith(): void
    {
        $string = $this->createString('Hello World');
        
        $this->assertTrue($string->startsWith('Hello'));
        $this->assertTrue($string->startsWith('H'));
        $this->assertFalse($string->startsWith('World'));
        
        // Case insensitive
        $this->assertFalse($string->startsWith('hello'));
        $this->assertTrue($string->startsWith('hello', false));
        
        // Empty string
        $this->assertTrue($string->startsWith(''));
        
        // Test with another AbstractUnicodeString
        $prefix = $this->createString('Hello');
        $this->assertTrue($string->startsWith($prefix));
    }

    public function testEndsWith(): void
    {
        $string = $this->createString('Hello World');
        
        $this->assertTrue($string->endsWith('World'));
        $this->assertTrue($string->endsWith('d'));
        $this->assertFalse($string->endsWith('Hello'));
        
        // Case insensitive
        $this->assertFalse($string->endsWith('world'));
        $this->assertTrue($string->endsWith('world', false));
        
        // Empty string
        $this->assertTrue($string->endsWith(''));
        
        // Test with another AbstractUnicodeString
        $suffix = $this->createString('World');
        $this->assertTrue($string->endsWith($suffix));
    }

    public function testTrim(): void
    {
        $string = $this->createString('  Hello World  ');
        $trimmed = $string->trim();
        
        $this->assertEquals('Hello World', $trimmed->toString());
        $this->assertNotSame($string, $trimmed); // Should return new instance
        
        // Custom characters
        $custom = $this->createString('...Hello World...');
        $trimmedCustom = $custom->trim('.');
        $this->assertEquals('Hello World', $trimmedCustom->toString());
    }

    public function testTrimLeft(): void
    {
        $string = $this->createString('  Hello World  ');
        $trimmed = $string->trimLeft();
        
        $this->assertEquals('Hello World  ', $trimmed->toString());
        $this->assertNotSame($string, $trimmed);
    }

    public function testTrimRight(): void
    {
        $string = $this->createString('  Hello World  ');
        $trimmed = $string->trimRight();
        
        $this->assertEquals('  Hello World', $trimmed->toString());
        $this->assertNotSame($string, $trimmed);
    }

    public function testReplace(): void
    {
        $string = $this->createString('Hello World');
        
        $replaced = $string->replace('World', 'Universe');
        $this->assertEquals('Hello Universe', $replaced->toString());
        $this->assertNotSame($string, $replaced);
        
        // Case insensitive
        $replaced2 = $string->replace('world', 'Universe', false);
        $this->assertEquals('Hello Universe', $replaced2->toString());
        
        // Test with another AbstractUnicodeString
        $search = $this->createString('World');
        $replacement = $this->createString('Universe');
        $replaced3 = $string->replace($search, $replacement);
        $this->assertEquals('Hello Universe', $replaced3->toString());
    }

    public function testSplit(): void
    {
        $string = $this->createString('Hello,World,PHP');
        $parts = $string->split(',');
        
        $this->assertCount(3, $parts);
        $this->assertInstanceOf(TestableUnicodeString::class, $parts[0]);
        $this->assertEquals('Hello', $parts[0]->toString());
        $this->assertEquals('World', $parts[1]->toString());
        $this->assertEquals('PHP', $parts[2]->toString());
        
        // With limit
        $limitedParts = $string->split(',', 2);
        $this->assertCount(2, $limitedParts);
        $this->assertEquals('Hello', $limitedParts[0]->toString());
        $this->assertEquals('World,PHP', $limitedParts[1]->toString());
        
        // Test with another AbstractUnicodeString
        $delimiter = $this->createString(',');
        $parts2 = $string->split($delimiter);
        $this->assertCount(3, $parts2);
    }

    public function testReverse(): void
    {
        $string = $this->createString('Hello');
        $reversed = $string->reverse();
        
        $this->assertEquals('olleH', $reversed->toString());
        $this->assertNotSame($string, $reversed);
        
        // Unicode characters
        $unicode = $this->createString('🌍🚀');
        $reversedUnicode = $unicode->reverse();
        $this->assertEquals('🚀🌍', $reversedUnicode->toString());
    }

    public function testRepeat(): void
    {
        $string = $this->createString('Hi');
        $repeated = $string->repeat(3);
        
        $this->assertEquals('HiHiHi', $repeated->toString());
        $this->assertNotSame($string, $repeated);
        
        // Zero times
        $zero = $string->repeat(0);
        $this->assertTrue($zero->isEmpty());
    }

    public function testRepeatNegative(): void
    {
        $string = $this->createString('Hi');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Repeat count cannot be negative');
        $string->repeat(-1);
    }

    public function testPad(): void
    {
        $string = $this->createString('Hi');
        
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
        
        // Length less than or equal to current length
        $noPad = $string->pad(2);
        $this->assertSame($string, $noPad); // Should return same instance
    }

    public function testCompareTo(): void
    {
        $string1 = $this->createString('apple');
        $string2 = $this->createString('banana');
        $string3 = $this->createString('apple');
        
        $this->assertLessThan(0, $string1->compareTo($string2));
        $this->assertGreaterThan(0, $string2->compareTo($string1));
        $this->assertEquals(0, $string1->compareTo($string3));
        
        // Case insensitive
        $upper = $this->createString('APPLE');
        $this->assertNotEquals(0, $string1->compareTo($upper));
        $this->assertEquals(0, $string1->compareTo($upper, false));
        
        // Test with regular string
        $this->assertEquals(0, $string1->compareTo('apple'));
    }

    public function testEquals(): void
    {
        $string1 = $this->createString('Hello');
        $string2 = $this->createString('Hello');
        $string3 = $this->createString('World');
        
        $this->assertTrue($string1->equals($string2));
        $this->assertFalse($string1->equals($string3));
        
        // Case insensitive
        $upper = $this->createString('HELLO');
        $this->assertFalse($string1->equals($upper));
        $this->assertTrue($string1->equals($upper, false));
        
        // Test with regular string
        $this->assertTrue($string1->equals('Hello'));
    }

    public function testToCharArray(): void
    {
        $string = $this->createString('Hi🌍');
        $chars = $string->toCharArray();
        
        $this->assertEquals(['H', 'i', '🌍'], $chars);
    }

    public function testToString(): void
    {
        $string = $this->createString('Hello World');
        $this->assertEquals('Hello World', $string->toString());
        $this->assertEquals('Hello World', (string) $string);
    }

    public function testGetEncoding(): void
    {
        $string = $this->createString('Hello');
        $this->assertEquals('UTF-8', $string->getEncoding());
    }

    public function testIsValidUtf8(): void
    {
        $string = $this->createString('Hello 🌍');
        $this->assertTrue($string->isValidUtf8());
    }

    public function testNormalize(): void
    {
        if (!class_exists('Normalizer')) {
            $this->markTestSkipped('Intl extension not available');
        }
        
        // Create a string with combining characters
        $combining = "e\u{0301}"; // e + combining acute accent
        $string = $this->createString($combining);
        
        $normalized = $string->normalize();
        $this->assertInstanceOf(TestableUnicodeString::class, $normalized);
    }

    public function testNormalizeWithoutIntl(): void
    {
        if (class_exists('Normalizer')) {
            $this->markTestSkipped('Intl extension is available');
        }
        
        $string = $this->createString('test');
        
        $this->expectException(UnicodeException::class);
        $this->expectExceptionMessage('Intl extension is required for Unicode normalization');
        $string->normalize();
    }

    public function testAbstractMethods(): void
    {
        // Test the abstract methods are properly implemented
        $string = TestableUnicodeString::of('Hello World');
        $this->assertEquals('Hello World', $string->toString());
        
        $empty = TestableUnicodeString::empty();
        $this->assertTrue($empty->isEmpty());
        
        $substring = $string->substring(6, 5);
        $this->assertEquals('World', $substring->toString());
        
        $concat = $string->concat(' Test');
        $this->assertEquals('Hello World Test', $concat->toString());
        
        $lower = $string->toLowerCase();
        $this->assertEquals('hello world', $lower->toString());
        
        $upper = $string->toUpperCase();
        $this->assertEquals('HELLO WORLD', $upper->toString());
        
        $title = $string->toTitleCase();
        $this->assertEquals('Hello World', $title->toString());
    }

    public function testImmutability(): void
    {
        $original = $this->createString('Hello World');
        
        // All operations should return new instances
        $this->assertNotSame($original, $original->trim());
        $this->assertNotSame($original, $original->replace('World', 'Universe'));
        $this->assertNotSame($original, $original->toLowerCase());
        $this->assertNotSame($original, $original->toUpperCase());
        $this->assertNotSame($original, $original->substring(0, 5));
        $this->assertNotSame($original, $original->concat(' Test'));
        $this->assertNotSame($original, $original->reverse());
        $this->assertNotSame($original, $original->repeat(2));
        
        // Original should remain unchanged
        $this->assertEquals('Hello World', $original->toString());
    }
}
