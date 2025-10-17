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

use Flaphl\Element\String\StringBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Test case for StringBuilder class.
 * 
 * @package Flaphl\Element\String\Tests
 * @author Jade Phyressi <jade@flaphl.com>
 */
class StringBuilderTest extends TestCase
{
    public function testConstructor(): void
    {
        $builder = new StringBuilder();
        $this->assertEquals('', $builder->getString());
    }

    public function testConstructorWithInitialString(): void
    {
        $builder = new StringBuilder('Hello');
        $this->assertEquals('Hello', $builder->getString());
    }

    public function testAppend(): void
    {
        $builder = new StringBuilder();
        $result = $builder->append('Hello');
        
        $this->assertSame($builder, $result); // Should return self for chaining
        $this->assertEquals('Hello', $builder->getString());
    }

    public function testMultipleAppends(): void
    {
        $builder = new StringBuilder();
        $builder->append('Hello')
                ->append(' ')
                ->append('World')
                ->append('!');
        
        $this->assertEquals('Hello World!', $builder->getString());
    }

    public function testAppendEmptyString(): void
    {
        $builder = new StringBuilder('Hello');
        $builder->append('');
        
        $this->assertEquals('Hello', $builder->getString());
    }

    public function testAppendWithInitialContent(): void
    {
        $builder = new StringBuilder('Initial');
        $builder->append(' Content');
        
        $this->assertEquals('Initial Content', $builder->getString());
    }

    public function testChaining(): void
    {
        $builder = new StringBuilder();
        
        $result = $builder->append('Hello')
                         ->append(' ')
                         ->append('Beautiful')
                         ->append(' ')
                         ->append('World');
        
        $this->assertSame($builder, $result);
        $this->assertEquals('Hello Beautiful World', $builder->getString());
    }

    public function testGetString(): void
    {
        $builder = new StringBuilder('Test Content');
        $this->assertEquals('Test Content', $builder->getString());
        
        $builder->append(' More');
        $this->assertEquals('Test Content More', $builder->getString());
    }

    public function testLargeContent(): void
    {
        $builder = new StringBuilder();
        
        // Test with many small appends
        for ($i = 0; $i < 1000; $i++) {
            $builder->append('x');
        }
        
        $result = $builder->getString();
        $this->assertEquals(1000, strlen($result));
        $this->assertEquals(str_repeat('x', 1000), $result);
    }

    public function testUnicodeSupport(): void
    {
        $builder = new StringBuilder();
        $builder->append('Hello ')
                ->append('🌍')
                ->append(' World');
        
        $result = $builder->getString();
        $this->assertEquals('Hello 🌍 World', $result);
        
        // Test that Unicode characters are preserved
        $this->assertStringContainsString('🌍', $result);
    }

    public function testSpecialCharacters(): void
    {
        $builder = new StringBuilder();
        $builder->append('Line 1\n')
                ->append('Line 2\t')
                ->append('Quoted "text"')
                ->append(" and 'more'");
        
        $expected = 'Line 1\nLine 2\tQuoted "text" and \'more\'';
        $this->assertEquals($expected, $builder->getString());
    }

    public function testNumericContent(): void
    {
        $builder = new StringBuilder();
        $builder->append('Number: ')
                ->append('123')
                ->append('.45');
        
        $this->assertEquals('Number: 123.45', $builder->getString());
    }

    public function testEmptyAppends(): void
    {
        $builder = new StringBuilder();
        $builder->append('')
                ->append('')
                ->append('Content')
                ->append('');
        
        $this->assertEquals('Content', $builder->getString());
    }

    public function testMixedContent(): void
    {
        $builder = new StringBuilder('Start: ');
        $builder->append('Text ')
                ->append('123 ')
                ->append('🚀 ')
                ->append('End');
        
        $this->assertEquals('Start: Text 123 🚀 End', $builder->getString());
    }

    public function testRepeatedGetString(): void
    {
        $builder = new StringBuilder('Hello');
        
        // Multiple calls to getString should return the same result
        $result1 = $builder->getString();
        $result2 = $builder->getString();
        
        $this->assertEquals($result1, $result2);
        $this->assertEquals('Hello', $result1);
        
        // After appending, getString should reflect the change
        $builder->append(' World');
        $result3 = $builder->getString();
        $this->assertEquals('Hello World', $result3);
    }

    public function testBuilderReuse(): void
    {
        $builder = new StringBuilder();
        
        // Build first string
        $builder->append('First')
                ->append(' String');
        $first = $builder->getString();
        $this->assertEquals('First String', $first);
        
        // Continue building
        $builder->append(' Extended');
        $extended = $builder->getString();
        $this->assertEquals('First String Extended', $extended);
    }

    public function testLongTextProcessing(): void
    {
        $builder = new StringBuilder();
        
        $lorem = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. ';
        
        for ($i = 0; $i < 100; $i++) {
            $builder->append($lorem);
        }
        
        $result = $builder->getString();
        $expectedLength = strlen($lorem) * 100;
        
        $this->assertEquals($expectedLength, strlen($result));
        $this->assertStringStartsWith('Lorem ipsum', $result);
        $this->assertStringEndsWith('elit. ', $result);
    }

    public function testPerformanceComparison(): void
    {
        $iterations = 1000;
        
        // Test StringBuilder approach
        $builder = new StringBuilder();
        $start = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $builder->append("Item $i ");
        }
        $builderResult = $builder->getString();
        $builderTime = microtime(true) - $start;
        
        // Test string concatenation approach
        $string = '';
        $start = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $string .= "Item $i ";
        }
        $stringTime = microtime(true) - $start;
        
        // Both should produce the same result
        $this->assertEquals($builderResult, $string);
        
        // StringBuilder should be reasonably performant
        // (This is more of a behavioral test than a strict performance requirement)
        $this->assertIsFloat($builderTime);
        $this->assertIsFloat($stringTime);
    }

    public function testConstructorWithWhitespace(): void
    {
        $builder = new StringBuilder('   ');
        $this->assertEquals('   ', $builder->getString());
        
        $builder->append('content');
        $this->assertEquals('   content', $builder->getString());
    }

    public function testAppendVariousDataTypes(): void
    {
        $builder = new StringBuilder();
        
        // StringBuilder should handle string conversion
        $builder->append('String: ')
                ->append('123')           // numeric string
                ->append(' Boolean: ')
                ->append('true')          // boolean as string
                ->append(' Float: ')
                ->append('3.14');         // float as string
        
        $expected = 'String: 123 Boolean: true Float: 3.14';
        $this->assertEquals($expected, $builder->getString());
    }
}
