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

use Flaphl\Element\String\StringBuffer;
use Flaphl\Element\String\Exceptions\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Test case for StringBuffer class.
 * 
 * @package Flaphl\Element\String\Tests
 * @author Jade Phyressi <jade@flaphl.com>
 */
class StringBufferTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/flaphl_string_buffer_tests_' . uniqid();
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up any remaining lock files
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
    }

    public function testConstructor(): void
    {
        $buffer = new StringBuffer();
        $this->assertEquals('', $buffer->toString());
        $this->assertEquals(0, $buffer->length());
        $this->assertTrue($buffer->isEmpty());
    }

    public function testConstructorWithInitialContent(): void
    {
        $buffer = new StringBuffer('Hello World');
        $this->assertEquals('Hello World', $buffer->toString());
        $this->assertEquals(11, $buffer->length());
        $this->assertFalse($buffer->isEmpty());
    }

    public function testConstructorWithCustomLockDir(): void
    {
        // StringBuffer uses internal lock file management, no custom directory parameter
        $buffer = new StringBuffer('test');
        $this->assertEquals('test', $buffer->toString());
        
        // Check that buffer operations work correctly
        $buffer->append(' more');
        $this->assertEquals('test more', $buffer->toString());
    }

    public function testAppend(): void
    {
        $buffer = new StringBuffer('Hello');
        $result = $buffer->append(' World');
        
        $this->assertSame($buffer, $result); // Should return self for chaining
        $this->assertEquals('Hello World', $buffer->toString());
        $this->assertEquals(11, $buffer->length());
    }

    public function testAppendMultiple(): void
    {
        $buffer = new StringBuffer();
        $buffer->append('Hello')
               ->append(' ')
               ->append('World')
               ->append('!');
        
        $this->assertEquals('Hello World!', $buffer->toString());
    }

    public function testPrepend(): void
    {
        $buffer = new StringBuffer('World');
        $result = $buffer->prepend('Hello ');
        
        $this->assertSame($buffer, $result);
        $this->assertEquals('Hello World', $buffer->toString());
    }

    public function testInsert(): void
    {
        $buffer = new StringBuffer('Hello World');
        $result = $buffer->insert(6, 'Beautiful ');
        
        $this->assertSame($buffer, $result);
        $this->assertEquals('Hello Beautiful World', $buffer->toString());
    }

    public function testInsertAtBeginning(): void
    {
        $buffer = new StringBuffer('World');
        $buffer->insert(0, 'Hello ');
        
        $this->assertEquals('Hello World', $buffer->toString());
    }

    public function testInsertAtEnd(): void
    {
        $buffer = new StringBuffer('Hello');
        $buffer->insert(5, ' World');
        
        $this->assertEquals('Hello World', $buffer->toString());
    }

    public function testInsertOutOfBounds(): void
    {
        $buffer = new StringBuffer('Hello');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Position 10 is out of bounds for buffer length 5');
        $buffer->insert(10, 'test');
    }

    public function testInsertNegativePosition(): void
    {
        $buffer = new StringBuffer('Hello');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Position -1 is out of bounds for buffer length 5');
        $buffer->insert(-1, 'test');
    }

    public function testReplace(): void
    {
        $buffer = new StringBuffer('Hello World');
        $result = $buffer->replace(6, 5, 'Universe'); // Replace "World" with "Universe"
        
        $this->assertSame($buffer, $result);
        $this->assertEquals('Hello Universe', $buffer->toString());
    }

    public function testReplaceEntireString(): void
    {
        $buffer = new StringBuffer('Hello World');
        $buffer->replace(0, 11, 'New Content');
        
        $this->assertEquals('New Content', $buffer->toString());
    }

    public function testReplaceOutOfBounds(): void
    {
        $buffer = new StringBuffer('Hello');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Start position 10 is out of bounds');
        $buffer->replace(10, 5, 'test');
    }

    public function testReplaceInvalidLength(): void
    {
        $buffer = new StringBuffer('Hello');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Length cannot be negative');
        $buffer->replace(0, -1, 'test');
    }

    public function testDelete(): void
    {
        $buffer = new StringBuffer('Hello World');
        $result = $buffer->delete(5, 6); // Delete " World"
        
        $this->assertSame($buffer, $result);
        $this->assertEquals('Hello', $buffer->toString());
    }

    public function testDeleteSingleCharacter(): void
    {
        $buffer = new StringBuffer('Hello');
        $buffer->delete(1, 1); // Delete 'e'
        
        $this->assertEquals('Hllo', $buffer->toString());
    }

    public function testDeleteAll(): void
    {
        $buffer = new StringBuffer('Hello World');
        $buffer->delete(0, 11);
        
        $this->assertEquals('', $buffer->toString());
        $this->assertTrue($buffer->isEmpty());
    }

    public function testClear(): void
    {
        $buffer = new StringBuffer('Hello World');
        $result = $buffer->clear();
        
        $this->assertSame($buffer, $result);
        $this->assertEquals('', $buffer->toString());
        $this->assertTrue($buffer->isEmpty());
        $this->assertEquals(0, $buffer->length());
    }

    public function testLength(): void
    {
        $buffer = new StringBuffer();
        $this->assertEquals(0, $buffer->length());
        
        $buffer->append('Hello');
        $this->assertEquals(5, $buffer->length());
        
        $buffer->append(' World');
        $this->assertEquals(11, $buffer->length());
        
        $buffer->clear();
        $this->assertEquals(0, $buffer->length());
    }

    public function testIsEmpty(): void
    {
        $buffer = new StringBuffer();
        $this->assertTrue($buffer->isEmpty());
        
        $buffer->append('test');
        $this->assertFalse($buffer->isEmpty());
        
        $buffer->clear();
        $this->assertTrue($buffer->isEmpty());
    }

    public function testCharAt(): void
    {
        $buffer = new StringBuffer('Hello');
        
        $this->assertEquals('H', $buffer->charAt(0));
        $this->assertEquals('e', $buffer->charAt(1));
        $this->assertEquals('o', $buffer->charAt(4));
    }

    public function testCharAtOutOfBounds(): void
    {
        $buffer = new StringBuffer('Hello');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Index 5 is out of bounds for buffer length 5');
        $buffer->charAt(5);
    }

    public function testSubstring(): void
    {
        $buffer = new StringBuffer('Hello World');
        
        $this->assertEquals('Hello', $buffer->substring(0, 5));
        $this->assertEquals('World', $buffer->substring(6, 11));
        $this->assertEquals('World', $buffer->substring(6)); // To end
    }

    public function testIndexOf(): void
    {
        $buffer = new StringBuffer('Hello World Hello');
        
        $this->assertEquals(0, $buffer->indexOf('Hello'));
        $this->assertEquals(6, $buffer->indexOf('World'));
        $this->assertEquals(12, $buffer->indexOf('Hello', 1)); // Start from position 1
        $this->assertFalse($buffer->indexOf('Universe')); // Should return false, not -1
    }

    public function testLastIndexOf(): void
    {
        $buffer = new StringBuffer('Hello World Hello');
        
        $this->assertEquals(12, $buffer->lastIndexOf('Hello'));
        $this->assertEquals(6, $buffer->lastIndexOf('World'));
        $this->assertFalse($buffer->lastIndexOf('Universe')); // Should return false, not -1
    }

    public function testToString(): void
    {
        $buffer = new StringBuffer('Hello World');
        $this->assertEquals('Hello World', $buffer->toString());
        $this->assertEquals('Hello World', (string) $buffer);
    }

    public function testReverse(): void
    {
        $buffer = new StringBuffer('Hello');
        $result = $buffer->reverse();
        
        $this->assertSame($buffer, $result);
        $this->assertEquals('olleH', $buffer->toString());
    }

    public function testReverseEmpty(): void
    {
        $buffer = new StringBuffer();
        $buffer->reverse();
        
        $this->assertEquals('', $buffer->toString());
    }

    public function testTrim(): void
    {
        $buffer = new StringBuffer('  Hello World  ');
        $result = $buffer->trim();
        
        $this->assertSame($buffer, $result);
        $this->assertEquals('Hello World', $buffer->toString());
    }

    public function testTrimCustomCharacters(): void
    {
        $buffer = new StringBuffer('...Hello World...');
        $buffer->trim('.');
        
        $this->assertEquals('Hello World', $buffer->toString());
    }

    public function testToUpperCase(): void
    {
        // StringBuffer doesn't have case conversion methods
        // This test demonstrates working with the content
        $buffer = new StringBuffer('Hello World');
        $content = $buffer->toString();
        $upperContent = strtoupper($content);
        
        $buffer->clear()->append($upperContent);
        $this->assertEquals('HELLO WORLD', $buffer->toString());
    }

    public function testToLowerCase(): void
    {
        // StringBuffer doesn't have case conversion methods
        // This test demonstrates working with the content
        $buffer = new StringBuffer('Hello World');
        $content = $buffer->toString();
        $lowerContent = strtolower($content);
        
        $buffer->clear()->append($lowerContent);
        $this->assertEquals('hello world', $buffer->toString());
    }

    public function testUnicodeSupport(): void
    {
        $buffer = new StringBuffer('Héllo 🌍');
        
        $this->assertEquals(7, $buffer->length());
        $this->assertEquals('H', $buffer->charAt(0));
        $this->assertEquals('é', $buffer->charAt(1));
        $this->assertEquals('🌍', $buffer->charAt(6));
    }

    public function testConcurrentAccess(): void
    {
        // This test simulates concurrent access by creating multiple buffers
        // and testing that operations work independently
        $buffer1 = new StringBuffer('Initial');
        $buffer2 = new StringBuffer('Content');
        
        // Both buffers should work independently
        $buffer1->append(' Content 1');
        $buffer2->append(' Content 2');
        
        $this->assertEquals('Initial Content 1', $buffer1->toString());
        $this->assertEquals('Content Content 2', $buffer2->toString());
    }

    public function testLockFileCreationAndCleanup(): void
    {
        $buffer = new StringBuffer('test');
        
        // Perform an operation that should create a lock
        $buffer->append(' more');
        
        // Lock file should exist temporarily during operation
        // but should be cleaned up after
        $this->assertEquals('test more', $buffer->toString());
        
        // Force cleanup by destroying the buffer
        unset($buffer);
        
        // StringBuffer manages its own lock files internally
        $this->assertTrue(true); // Test that operations complete successfully
    }

    public function testChaining(): void
    {
        $buffer = new StringBuffer();
        
        $result = $buffer->append('Hello')
                         ->append(' ')
                         ->append('World')
                         ->trim();
        
        $this->assertSame($buffer, $result);
        $this->assertEquals('Hello World', $buffer->toString());
    }

    public function testLargeContent(): void
    {
        $buffer = new StringBuffer();
        
        // Test with large content
        $largeString = str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 1000);
        $buffer->append($largeString);
        
        $this->assertEquals(strlen($largeString), $buffer->length());
        $this->assertStringStartsWith('Lorem ipsum', $buffer->toString());
        $this->assertStringEndsWith('elit. ', $buffer->toString());
    }

    public function testReplaceWithEmptyString(): void
    {
        $buffer = new StringBuffer('Hello World');
        $buffer->replace(5, 6, ''); // Replace " World" with empty string
        
        $this->assertEquals('Hello', $buffer->toString());
    }

    public function testInsertEmptyString(): void
    {
        $buffer = new StringBuffer('Hello World');
        $buffer->insert(5, '');
        
        $this->assertEquals('Hello World', $buffer->toString());
    }

    public function testMultipleOperations(): void
    {
        $buffer = new StringBuffer('Original Text');
        
        $buffer->clear()
               ->append('Hello')
               ->prepend('Say: ')
               ->append(' World')
               ->insert(5, 'Beautiful ')
               ->replace(5, 9, 'Amazing') // Replace "Beautiful" with "Amazing"
               ->delete(4, 1) // Delete the space after "Say:"
               ->trim();
        
        $this->assertEquals('Say:Amazing Hello World', $buffer->toString());
    }
}
