<p align="center"><img width="200" src="https://github.com/robsontenorio/mary-ui.com/blob/main/public/images/mary.png?raw=true"></p>

<p align="center">
    <a href="https://packagist.org/packages/robsontenorio/mary">
        <img src="https://img.shields.io/packagist/dt/robsontenorio/mary?cacheSeconds=60">
    </a>
    <a href="https://packagist.org/packages/robsontenorio/mary">
        <img src="https://img.shields.io/packagist/v/robsontenorio/mary?label=stable&color=blue&cacheSeconds=60">
    </a>
    <a href="https://packagist.org/packages/robsontenorio/mary">
        <img src="https://poser.pugx.org/robsontenorio/mary/license.svg">
    </a>
</p>

## Introduction

The maryUI package is a set of Gorgeous UI components for Livewire powered by daisyUI and Tailwind.

## Official Documentation

You can read the official documentation on the [maryUI website](https://mary-ui.com).

## Optional Tailwind prefix

Mary keeps its current, unprefixed output by default. If your application uses a
Tailwind CSS v4 prefix, publish the Mary configuration and set the same prefix:

```php
// config/mary.php
'tailwind_prefix' => 'tw',
```

The value is the prefix name without `:`. Configure Tailwind, Mary and the
generated class source with the same value:

```css
@import "tailwindcss" prefix(tw);

@source "../../vendor/robsontenorio/mary/src/View/Components/**/*.php";
@source "../../storage/framework/mary/classes.html";

@plugin "daisyui" {
    themes: light --default, dark --prefersdark;
}
```

Generate Mary's static class source before Vite runs:

```shell
php artisan mary:build-class-source
```

The command extracts class candidates directly from maryUI's component source,
your Blade views and Livewire SFCs, then writes static HTML that Tailwind can
scan. It does not render components or load, execute or inspect Tailwind or
DaisyUI. By default, application sources are read from `resources/views`. You
can configure additional view directories when needed:

```php
// config/mary.php
'class_source_paths' => [
    resource_path('views'),
    resource_path('livewire'),
],
```

Another package can register its own Blade or Livewire SFC directory from its
service provider without replacing the configured application paths:

```php
use Mary\Facades\Mary;

public function boot(): void
{
    Mary::addClassSourcePath(__DIR__.'/../resources/views');
}
```

Only candidates passed through `Mary::classes()`, `@maryClass`,
`maryClass()` or the resulting builder's `add()` method are extracted. Calls to
unrelated `add()` methods and values passed through `addRaw()` are ignored. A
convenient `package.json` setup for environments where PHP is available during
the frontend build is:

```json
{
    "scripts": {
        "predev": "php artisan mary:build-class-source",
        "prebuild": "php artisan mary:build-class-source",
        "dev": "vite",
        "build": "vite build"
    }
}
```

The default destination is `storage/framework/mary/classes.html`. You can move
it permanently with `mary.class_source_path`, or override it for one build:

```shell
php artisan mary:build-class-source --path=resources/css/generated/mary-classes.html
```

For a CD frontend build without PHP, configure `class_source_path` with a
versioned path such as `resources/css/generated/mary-classes.html`, generate the
file locally, commit it, and update the CSS `@source` path. Regenerate the file
whenever the Mary version or prefix changes. The prefix in `config/mary.php`,
Tailwind's `prefix(...)`, and the committed generated file must always match.

Classes passed through `addRaw()` or component class customization properties
are left untouched. Mary's internal classes and candidates passed through its
prefix-aware class APIs receive the configured prefix.

Projects without `tailwind_prefix` keep the existing output and do not need to
run this command or change their current setup.

## Sponsor

Let's keep pushing it, [sponsor me](https://github.com/sponsors/robsontenorio) ❤️

## Discord

Come to say hello on [maryUI Discord](https://discord.gg/c2Dv8T2X2s)

## Follow me

[@robsontenorio](https://twitter.com/robsontenorio)

## Contributing

Clone the repository into your project root.

```bash
git clone git@github.com:robsontenorio/mary.git packages/mary
```

Add the local repository to composer config.

```bash
composer config repositories.local '{"type": "path", "url": "packages/mary"}'
```

Require the package again for local symlink.

```bash
composer require robsontenorio/mary:@dev
```

Start the dev server.

```bash
yarn dev
```

You can roll back to the stable version by removing the local repository and requiring the package again.

```bash
composer config --unset repositories.local
composer require robsontenorio/mary
```

## License

<a name="license"></a>

MaryUI is open-sourced software licensed under the [MIT license](/license.md).
