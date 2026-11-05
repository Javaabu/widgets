<?php

namespace Javaabu\Widgets\Exceptions;

use InvalidArgumentException;
use Javaabu\Widgets\Widget;

class WidgetsException extends InvalidArgumentException
{
    public static function notAWidget(string $class): static
    {
        return new static("[{$class}] must extend " . Widget::class . '.');
    }

    public static function unknownWidget(string $key): static
    {
        return new static("No widget is registered with the key [{$key}]. Register it with Widgets::register().");
    }

    public static function unknownLocation(string $key): static
    {
        return new static("No widget location [{$key}]. Define it with Widgets::location('{$key}', ...).");
    }
}
