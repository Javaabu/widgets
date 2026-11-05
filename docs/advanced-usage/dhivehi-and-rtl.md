---
title: Dhivehi and RTL
sidebar_position: 6
---

# Translations

Every string the package shows is translated with `__()`. English strings are the keys, in `lang/en.json` and `lang/dv.json`. This includes the admin pages, validation messages, the built-in widgets' names and labels, screen reader announcements, and the permission descriptions (`View widgets`, `Edit widgets`). The React pages get their strings from the controller, already in the current locale.

To change a translation, publish the files:

```bash
php artisan vendor:publish --tag=widgets-translations
```

Labels you give locations and your own widgets go through `__()` too, so add them to your app's `lang/dv.json`.

# Right to left

- The views use logical layout only: flex and grid gaps and `inline`/`block` properties, with no `left`/`right` utilities. They follow the page's `dir`.
- `widgets::styles` uses logical CSS properties (`margin-block`, `padding-inline`, `max-inline-size`).
- When reordering with the keyboard, the left and right arrow keys follow the reading direction.
- Widget titles in the admin list have `dir="auto"`, so mixed Dhivehi and English titles display correctly.
- Translatable settings give the Dhivehi input the Thaana keyboard (javaabu/stencil, javaabu/forms). For settings that only take Dhivehi, add `->thaana()` to the field.

# For review

These translations should be checked by a Dhivehi speaker:

| English | Dhivehi | Note |
| --- | --- | --- |
| Widget, Widgets | ވިޖެޓް، ވިޖެޓްތައް | Transliterated |
| Alternative text | ފޮޓޯގެ ތަފްޞީލު | Literally "the image's description" |
| Sidebar | ސައިޑްބާ | Transliterated |
| Footer | ފޫޓަރ | Transliterated |
| Move :widget | :widget ހިއްލާ | The drag handle's label |
| :widget picked up. Position :position of :total. | :widget ނެގީ. :total ގެ ތެރެއިން :position ވަނަ ތަން. | Screen reader announcement |
| To move a widget with the keyboard, … | ކީބޯޑުން ވިޖެޓެއް ހިއްލަން، … | Key names (Space, Escape) are transliterated |
| {1} :location holds only one widget.\|[2,*] … | {1} :location ގައި ބެހެއްޓޭނީ ހަމައެކަނި އެއް ވިޖެޓް.\|… | Pluralisation |
| This location is full. | މި ތަން ފުރިފައި. | |
