<?php

namespace Javaabu\Widgets\Tests\TestSupport\Policies;

class DenyAllPolicy
{
    public function before(): bool
    {
        return false;
    }
}
