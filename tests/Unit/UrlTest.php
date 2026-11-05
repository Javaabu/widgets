<?php

namespace Javaabu\Widgets\Tests\Unit;

use Javaabu\Widgets\Support\Url;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    /**
     * @return array<string, array{mixed, bool}>
     */
    public static function urls(): array
    {
        return [
            'https' => ['https://example.com/a?b=c', true],
            'http' => ['http://example.com', true],
            'path' => ['/about', true],
            'root' => ['/', true],
            'fragment' => ['#contact', true],
            'mailto' => ['mailto:info@example.com', true],
            'tel' => ['tel:+9603000000', true],
            'javascript' => ['javascript:alert(1)', false],
            'javascript with spaces' => [' JavaScript:alert(1)', false],
            'data' => ['data:text/html,hi', false],
            'protocol relative' => ['//evil.example', false],
            'bare' => ['example.com', false],
            'empty' => ['', false],
            'null' => [null, false],
            'array' => [['https://example.com'], false],
        ];
    }

    #[Test]
    #[DataProvider('urls')]
    public function it_only_allows_safe_urls(mixed $url, bool $safe): void
    {
        $this->assertSame($safe, Url::isSafe($url));
        $this->assertSame($safe ? trim($url) : null, Url::safe($url));
    }

    #[Test]
    public function it_tells_external_links(): void
    {
        $this->assertTrue(Url::isExternal('https://example.com'));
        $this->assertFalse(Url::isExternal('/about'));
        $this->assertFalse(Url::isExternal('mailto:a@b.c'));
    }
}
