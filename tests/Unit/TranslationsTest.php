<?php

namespace Javaabu\Widgets\Tests\Unit;

use Javaabu\Widgets\Support\Translations;
use Javaabu\Widgets\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TranslationsTest extends TestCase
{
    #[Test]
    public function it_tells_locale_maps(): void
    {
        $this->assertTrue(Translations::isLocaleMap(['en' => 'Hi', 'dv' => 'ހައި']));
        $this->assertTrue(Translations::isLocaleMap(['pt_BR' => 'Oi']));
        $this->assertFalse(Translations::isLocaleMap(['Hi', 'ހައި']));
        $this->assertFalse(Translations::isLocaleMap(['title' => 'Hi']));
        $this->assertFalse(Translations::isLocaleMap([]));
        $this->assertFalse(Translations::isLocaleMap('Hi'));
    }

    #[Test]
    public function it_picks_the_current_locale_then_falls_back(): void
    {
        $values = ['en' => 'Hello', 'dv' => 'ހެލޯ'];

        $this->app->setLocale('dv');
        $this->assertEquals('ހެލޯ', Translations::pick($values));
        $this->assertEquals('Hello', Translations::pick($values, 'en'));
        $this->assertEquals('Hello', Translations::pick(['en' => 'Hello', 'dv' => ''], 'dv'));
        $this->assertEquals('ހެލޯ', Translations::pick(['fr' => null, 'dv' => 'ހެލޯ'], 'fr'));
        $this->assertNull(Translations::pick(['en' => null], 'dv'));
    }
}
