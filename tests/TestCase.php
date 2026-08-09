<?php

namespace Mary\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Livewire\LivewireServiceProvider;
use Mary\Facades\Mary;
use Mary\MaryServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            LivewireServiceProvider::class,
            MaryServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        if (! class_exists('Mary', false)) {
            class_alias(Mary::class, 'Mary');
        }

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('mary.prefix', '');
        $app['config']->set('mary.tailwind_prefix', null);
        $app['config']->set('view.compiled', sys_get_temp_dir().'/mary-tests/views');
    }
}
