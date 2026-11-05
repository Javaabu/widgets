<?php

namespace Javaabu\Widgets\Assets;

use Javaabu\Manifest\Package;

/**
 * The package's frontend assets, registered with javaabu/manifest
 */
class Assets
{
    public const string PACKAGE = 'widgets';

    /** Styles for the built-in widgets (logical properties only) */
    public const string STYLES = 'widgets::styles';

    /** The Blade admin page's script: drag and drop and keyboard ordering */
    public const string ADMIN = 'widgets::admin';

    public static function register(Package $package): void
    {
        $package
            ->js('js')
            ->pages()
            ->dist('../dist')
            ->style('styles', 'css/widgets.css')
            ->script('admin', 'js/blade/admin.ts')
            ->npm([
                '@javaabu/forms' => 'github:Javaabu/react-forms#next-gen',
                '@javaabu/onu' => '^0.2.1',
            ], [
                'react' => '^19.0',
                'react-dom' => '^19.0',
                '@inertiajs/react' => '^2.0 || ^3.0',
            ]);
    }
}
