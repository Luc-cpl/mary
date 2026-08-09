<?php

return [
    /**
     * Default component prefix.
     *
     * Make sure to clear view cache after renaming with `php artisan view:clear`
     *
     *    prefix => ''
     *              <x-button />
     *              <x-card />
     *
     *    prefix => 'mary-'
     *               <x-mary-button />
     *               <x-mary-card />
     *
     */
    'prefix' => '',

    /**
     * Default route prefix.
     *
     * Some maryUI components make network request to its internal routes.
     *
     *      route_prefix => ''
     *          - Spotlight: '/mary/spotlight'
     *          - Editor: '/mary/upload'
     *          - ...
     *
     *      route_prefix => 'my-components'
     *          - Spotlight: '/my-components/mary/spotlight'
     *          - Editor: '/my-components/mary/upload'
     *          - ...
     */
    'route_prefix' => '',

    /**
     * Tailwind CSS prefix.
     *
     * This value must match the prefix configured in your Tailwind import.
     * For example, use `tw` here with `@import "tailwindcss" prefix(tw)`.
     */
    'tailwind_prefix' => null,

    /**
     * Generated class source consumed by Tailwind CSS.
     *
     * Use a path inside your repository if the frontend is built in an
     * environment where PHP is not available.
     */
    'class_source_path' => storage_path('framework/mary/classes.html'),

    /**
     * Blade directories scanned for Mary class candidates.
     */
    'class_source_paths' => [
        resource_path('views'),
    ],

    /**
     * Components settings
     */
    'components' => [
        'spotlight' => [
            'class' => 'App\Support\Spotlight',
        ],
    ],
];
