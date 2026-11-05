---
title: AI assistants
sidebar_position: 1.25
---

The package ships guidance for AI coding assistants.

- **Laravel Boost**: `php artisan boost:install` (or `boost:update`) picks up the package's guideline (`resources/boost/guidelines/core.blade.php`) and the `javaabu-widgets` skill (`resources/boost/skills/javaabu-widgets`). The skill covers defining locations, writing widgets, the admin routes, React, translatable settings and common mistakes.
- **llms.txt**: `vendor/javaabu/widgets/llms.txt` has the rules for using the package and an index of every docs page. Point an assistant at it when Boost isn't installed.
