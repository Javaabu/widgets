<?php

namespace Javaabu\Widgets\Tests\Http;

use Javaabu\Translatable\Facades\Languages;
use Javaabu\Translatable\Translatable;
use Javaabu\Widgets\Facades\Widgets;
use Javaabu\Widgets\Forms\WidgetForm;
use Javaabu\Widgets\Models\WidgetInstance;
use Javaabu\Widgets\Support\Translations;
use Javaabu\Widgets\Tests\Concerns\InteractsWithTranslatable;
use Javaabu\Widgets\Tests\TestCase;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;

#[RequiresMethod(Translatable::class, 'enabled')]
class TranslatableWidgetsTest extends TestCase
{
    use InteractsWithTranslatable;
    use InteractsWithWidgets;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpWidgets();
        $this->setUpLanguages(['en', 'dv']);
    }

    #[Test]
    public function it_renders_an_input_per_language_for_translatable_settings(): void
    {
        $this->assertTrue(Translations::enabled());

        $this->actingAs($this->user())
            ->get('/widgets/sidebar/create?type=link')
            ->assertOk()
            ->assertSee('name="title_en"', false)
            ->assertSee('name="title_dv"', false)
            ->assertSee('name="label_dv"', false)
            ->assertSee('name="url"', false)
            ->assertDontSee('name="url_dv"', false);
    }

    #[Test]
    public function it_stores_every_language_and_requires_the_primary_one(): void
    {
        $this->actingAs($this->user())
            ->postJson('/widgets/sidebar?type=text', ['text_dv' => 'ލިޔުން'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('text_en');

        $this->actingAs($this->user())
            ->post('/widgets/sidebar?type=text', [
                'title_en' => 'Hello',
                'title_dv' => 'މަރުޙަބާ',
                'text_en' => 'Some text',
                'text_dv' => '',
            ])
            ->assertRedirect('/widgets');

        $widget = WidgetInstance::first();

        $this->assertEquals(['en' => 'Hello', 'dv' => 'މަރުޙަބާ'], $widget->settings['title']);
        $this->assertEquals(['en' => 'Some text', 'dv' => null], $widget->settings['text']);
    }

    #[Test]
    public function it_renders_the_current_language_and_falls_back_to_the_default(): void
    {
        $this->add('text', ['title' => ['en' => 'Hello', 'dv' => 'މަރުޙަބާ'], 'text' => ['en' => 'Only English', 'dv' => null]]);

        $this->assertStringContainsString('Hello', Widgets::render('sidebar')->toHtml());

        Languages::setCurrentLocale('dv');
        Widgets::flush();

        $html = Widgets::render('sidebar')->toHtml();

        $this->assertStringContainsString('<h2 class="widget-title">މަރުޙަބާ</h2>', $html);
        $this->assertStringContainsString('Only English', $html);
        $this->assertEquals('މަރުޙަބާ', Widgets::toArray('sidebar')[0]['settings']['title']);
        $this->assertEquals(['en' => 'Hello', 'dv' => 'މަރުޙަބާ'], WidgetInstance::first()->translations('title'));
    }

    #[Test]
    public function it_fills_each_language_when_editing_and_keeps_languages_not_sent(): void
    {
        $widget = $this->add('text', ['title' => ['en' => 'Hello', 'dv' => 'މަރުޙަބާ', 'ar' => 'مرحبا'], 'text' => ['en' => 'Text']]);

        $this->actingAs($this->user())
            ->get("/widgets/sidebar/{$widget->id}/edit")
            ->assertOk()
            ->assertSee('value="މަރުޙަބާ"', false)
            ->assertSee('value="Hello"', false);

        $this->actingAs($this->user())
            ->patch("/widgets/sidebar/{$widget->id}", ['title_en' => 'Hi', 'title_dv' => 'ހައި', 'text_en' => 'Text'])
            ->assertRedirect();

        $this->assertEquals(['en' => 'Hi', 'dv' => 'ހައި', 'ar' => 'مرحبا'], $widget->fresh()->settings['title']);
    }

    #[Test]
    public function it_reads_values_saved_before_translatable_was_installed(): void
    {
        $widget = $this->add('text', ['title' => 'Plain', 'text' => 'Plain text']);

        $form = WidgetForm::for($widget);

        $this->assertEquals('Plain', $form->getStateValue('title_en'));
        $this->assertStringContainsString('Plain text', Widgets::render('sidebar')->toHtml());
    }

    #[Test]
    public function it_uses_plain_fields_when_turned_off(): void
    {
        $this->withoutTranslatable(function () {
            $this->actingAs($this->user())
                ->get('/widgets/sidebar/create?type=text')
                ->assertOk()
                ->assertSee('name="title"', false)
                ->assertDontSee('name="title_dv"', false);

            $this->actingAs($this->user())
                ->post('/widgets/sidebar?type=text', ['title' => 'ސުރުހީ', 'text' => 'ލިޔުން'])
                ->assertRedirect();

            $this->assertEquals(['title' => 'ސުރުހީ', 'text' => 'ލިޔުން'], WidgetInstance::first()->settings);
        });
    }
}
