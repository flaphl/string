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

use Flaphl\Element\String\HtmlString;
use Flaphl\Element\String\UnicodeString;
use Flaphl\Element\String\Exceptions\InvalidArgumentException;
use Flaphl\Element\String\Exceptions\UnicodeException;
use PHPUnit\Framework\TestCase;

/**
 * Test case for HtmlString class.
 * 
 * @package Flaphl\Element\String\Tests
 * @author Jade Phyressi <jade@flaphl.com>
 */
class HtmlStringTest extends TestCase
{
    public function testConstructor(): void
    {
        $html = new HtmlString('<p>Hello World</p>');
        $this->assertEquals('<p>Hello World</p>', $html->toString());
        $this->assertFalse($html->isEscaped());
    }

    public function testConstructorWithEscaped(): void
    {
        $html = new HtmlString('&lt;p&gt;Hello&lt;/p&gt;', 'UTF-8', true);
        $this->assertEquals('&lt;p&gt;Hello&lt;/p&gt;', $html->toString());
        $this->assertTrue($html->isEscaped());
    }

    public function testOf(): void
    {
        $html = HtmlString::of('<script>alert("xss")</script>');
        $this->assertEquals('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $html->toString());
        $this->assertTrue($html->isEscaped());
    }

    public function testTrusted(): void
    {
        $html = HtmlString::trusted('<p>Hello <strong>World</strong></p>');
        $this->assertEquals('<p>Hello <strong>World</strong></p>', $html->toString());
        $this->assertFalse($html->isEscaped());
    }

    public function testSafe(): void
    {
        $input = '<p>Hello</p><script>alert("xss")</script><strong>World</strong>';
        $safe = HtmlString::safe($input);
        
        // Should remove script tags but keep allowed tags
        $this->assertStringContainsString('<p>Hello</p>', $safe->toString());
        $this->assertStringContainsString('<strong>World</strong>', $safe->toString());
        $this->assertStringNotContainsString('<script>', $safe->toString());
    }

    public function testSafeWithCustomTags(): void
    {
        $input = '<p>Hello</p><div>World</div><span>Test</span>';
        $safe = HtmlString::safe($input, ['p', 'span']);
        
        $result = $safe->toString();
        $this->assertStringContainsString('<p>Hello</p>', $result);
        $this->assertStringContainsString('<span>Test</span>', $result);
        $this->assertStringNotContainsString('<div>', $result);
    }

    public function testEmpty(): void
    {
        $empty = HtmlString::empty();
        $this->assertTrue($empty->isEmpty());
        $this->assertTrue($empty->isEscaped());
    }

    public function testSubstring(): void
    {
        $html = new HtmlString('<p>Hello World</p>');
        $sub = $html->substring(3, 5);
        $this->assertEquals('Hello', $sub->toString());
        $this->assertFalse($sub->isEscaped());
    }

    public function testConcatWithHtmlString(): void
    {
        $html1 = new HtmlString('<p>Hello</p>', 'UTF-8', true);
        $html2 = new HtmlString('<p>World</p>', 'UTF-8', true);
        
        $result = $html1->concat($html2);
        $this->assertEquals('<p>Hello</p><p>World</p>', $result->toString());
        $this->assertTrue($result->isEscaped());
    }

    public function testConcatWithRawString(): void
    {
        $html = new HtmlString('<p>Hello</p>');
        $result = $html->concat(' & World');
        
        $this->assertEquals('<p>Hello</p> &amp; World', $result->toString());
        $this->assertFalse($result->isEscaped());
    }

    public function testConcatWithUnicodeString(): void
    {
        $html = new HtmlString('<p>Hello</p>');
        $unicode = new UnicodeString(' World');
        
        $result = $html->concat($unicode);
        $this->assertEquals('<p>Hello</p> World', $result->toString());
    }

    public function testToLowerCase(): void
    {
        $html = new HtmlString('<P>HELLO WORLD</P>');
        $lower = $html->toLowerCase();
        
        // Should preserve HTML tags case but convert text
        $this->assertStringContainsString('hello world', $lower->toString());
    }

    public function testToUpperCase(): void
    {
        $html = new HtmlString('<p>hello world</p>');
        $upper = $html->toUpperCase();
        
        // Should preserve HTML tags case but convert text
        $this->assertStringContainsString('HELLO WORLD', $upper->toString());
    }

    public function testToTitleCase(): void
    {
        $html = new HtmlString('<p>hello world</p>');
        $title = $html->toTitleCase();
        
        $this->assertStringContainsString('Hello World', $title->toString());
    }

    public function testCaseConversionWithEscapedContent(): void
    {
        $html = HtmlString::of('HELLO WORLD');
        $lower = $html->toLowerCase();
        
        $this->assertEquals('hello world', $lower->toString());
        $this->assertTrue($lower->isEscaped());
    }

    public function testToPlainText(): void
    {
        $html = new HtmlString('<p>Hello <strong>World</strong></p>');
        $plain = $html->toPlainText();
        
        $this->assertInstanceOf(UnicodeString::class, $plain);
        $this->assertEquals('Hello World', $plain->toString());
    }

    public function testToPlainTextWithEntities(): void
    {
        $html = new HtmlString('<p>Hello &amp; World</p>');
        $plain = $html->toPlainText();
        
        $this->assertEquals('Hello & World', $plain->toString());
    }

    public function testToHtml(): void
    {
        $html = new HtmlString('<p>Hello World</p>');
        $this->assertEquals('<p>Hello World</p>', $html->toHtml());
    }

    public function testEscape(): void
    {
        $html = new HtmlString('<p>Hello & World</p>');
        $escaped = $html->escape();
        
        $this->assertEquals('&lt;p&gt;Hello &amp; World&lt;/p&gt;', $escaped->toString());
        $this->assertTrue($escaped->isEscaped());
        
        // Already escaped should return same instance
        $escapedAgain = $escaped->escape();
        $this->assertSame($escaped, $escapedAgain);
    }

    public function testUnescape(): void
    {
        $escaped = HtmlString::of('<p>Hello & World</p>');
        $unescaped = $escaped->unescape();
        
        $this->assertEquals('<p>Hello & World</p>', $unescaped->toString());
        $this->assertFalse($unescaped->isEscaped());
        
        // Already unescaped should return same instance
        $unescapedAgain = $unescaped->unescape();
        $this->assertSame($unescaped, $unescapedAgain);
    }

    public function testIsValidHtml(): void
    {
        $validHtml = new HtmlString('<p>Hello <strong>World</strong></p>');
        $this->assertTrue($validHtml->isValidHtml());
        
        $invalidHtml = new HtmlString('<p>Hello <strong>World</p>'); // Unclosed tag
        $this->assertFalse($invalidHtml->isValidHtml());
        
        // Escaped content is always valid
        $escaped = HtmlString::of('<invalid>');
        $this->assertTrue($escaped->isValidHtml());
    }

    public function testGetTagCount(): void
    {
        $html = new HtmlString('<p>Hello <strong>World</strong></p>');
        $this->assertEquals(4, $html->getTagCount()); // <p>, <strong>, </strong>, </p>
        
        $escaped = HtmlString::of('<p>Hello</p>');
        $this->assertEquals(0, $escaped->getTagCount());
    }

    public function testExtractTags(): void
    {
        $html = new HtmlString('<p>Hello <strong>World</strong> <em>Test</em></p>');
        $tags = $html->extractTags();
        
        $this->assertContains('p', $tags);
        $this->assertContains('strong', $tags);
        $this->assertContains('em', $tags);
        $this->assertCount(3, $tags);
        
        $escaped = HtmlString::of('<p>Hello</p>');
        $this->assertEmpty($escaped->extractTags());
    }

    public function testHasDangerousContent(): void
    {
        $safe = new HtmlString('<p>Hello World</p>');
        $this->assertFalse($safe->hasDangerousContent());
        
        $dangerous1 = new HtmlString('<script>alert("xss")</script>');
        $this->assertTrue($dangerous1->hasDangerousContent());
        
        $dangerous2 = new HtmlString('<img src="javascript:alert(1)">');
        $this->assertTrue($dangerous2->hasDangerousContent());
        
        $dangerous3 = new HtmlString('<div onclick="alert(1)">Hello</div>');
        $this->assertTrue($dangerous3->hasDangerousContent());
        
        $dangerous4 = new HtmlString('<iframe src="evil.com"></iframe>');
        $this->assertTrue($dangerous4->hasDangerousContent());
        
        // Escaped content is never dangerous
        $escaped = HtmlString::of('<script>alert("xss")</script>');
        $this->assertFalse($escaped->hasDangerousContent());
    }

    public function testFilterAttributesWithDangerousUrls(): void
    {
        $input = '<a href="javascript:alert(1)">Link</a><img src="data:text/html,<script>alert(1)</script>">';
        $safe = HtmlString::safe($input);
        
        $result = $safe->toString();
        $this->assertStringNotContainsString('javascript:', $result);
        $this->assertStringNotContainsString('data:text/html', $result);
    }

    public function testSetAllowedTags(): void
    {
        $originalTags = HtmlString::getAllowedTags();
        
        HtmlString::setAllowedTags(['p', 'br']);
        $this->assertEquals(['p', 'br'], HtmlString::getAllowedTags());
        
        $input = '<p>Hello</p><div>World</div><br>';
        $safe = HtmlString::safe($input);
        
        $result = $safe->toString();
        $this->assertStringContainsString('<p>Hello</p>', $result);
        $this->assertStringContainsString('<br>', $result);
        $this->assertStringNotContainsString('<div>', $result);
        
        // Restore original tags
        HtmlString::setAllowedTags($originalTags);
    }

    public function testSetAllowedAttributes(): void
    {
        $originalAttributes = HtmlString::getAllowedAttributes();
        
        HtmlString::setAllowedAttributes(['class']);
        $this->assertEquals(['class'], HtmlString::getAllowedAttributes());
        
        $input = '<p class="test" id="main">Hello</p>';
        $safe = HtmlString::safe($input);
        
        $result = $safe->toString();
        $this->assertStringContainsString('class="test"', $result);
        $this->assertStringNotContainsString('id="main"', $result);
        
        // Restore original attributes
        HtmlString::setAllowedAttributes($originalAttributes);
    }

    public function testGetAllowedTags(): void
    {
        $tags = HtmlString::getAllowedTags();
        $this->assertIsArray($tags);
        $this->assertContains('p', $tags);
        $this->assertContains('strong', $tags);
    }

    public function testGetAllowedAttributes(): void
    {
        $attributes = HtmlString::getAllowedAttributes();
        $this->assertIsArray($attributes);
        $this->assertContains('class', $attributes);
        $this->assertContains('href', $attributes);
    }

    public function testLength(): void
    {
        $html = new HtmlString('<p>Hello</p>');
        $this->assertEquals(12, $html->length()); // Includes HTML tags
        
        $unicode = new HtmlString('<p>Héllo 🌍</p>');
        $this->assertEquals(14, $unicode->length()); // Unicode characters count properly
    }

    public function testInheritedMethods(): void
    {
        $html = new HtmlString('<p>Hello World</p>');
        
        // Test inherited methods work correctly
        $this->assertTrue($html->contains('Hello'));
        $this->assertEquals(3, $html->indexOf('Hello'));
        $this->assertTrue($html->startsWith('<p>'));
        $this->assertTrue($html->endsWith('</p>'));
    }

    public function testFilterAttributesWithComplexHtml(): void
    {
        $input = '<div class="container" onclick="alert(1)" data-test="value">
                    <p style="color: red;" class="text">Hello</p>
                    <a href="https://example.com" target="_blank" onclick="evil()">Link</a>
                  </div>';
        
        $safe = HtmlString::safe($input);
        $result = $safe->toString();
        
        // Should keep allowed attributes
        $this->assertStringContainsString('class="container"', $result);
        $this->assertStringContainsString('class="text"', $result);
        $this->assertStringContainsString('href="https://example.com"', $result);
        $this->assertStringContainsString('target="_blank"', $result);
        
        // Should remove dangerous attributes
        $this->assertStringNotContainsString('onclick=', $result);
        $this->assertStringNotContainsString('style=', $result);
        $this->assertStringNotContainsString('data-test=', $result);
    }

    public function testEmptyAndWhitespaceHandling(): void
    {
        $empty = new HtmlString('');
        $this->assertTrue($empty->isEmpty());
        
        $whitespace = new HtmlString('   ');
        $this->assertFalse($whitespace->isEmpty());
        $this->assertEquals(3, $whitespace->length());
        
        $htmlWithWhitespace = new HtmlString('<p>  Hello  </p>');
        $trimmed = $htmlWithWhitespace->trim();
        $this->assertEquals('<p>  Hello  </p>', $trimmed->toString()); // HTML content preserved
    }
}
