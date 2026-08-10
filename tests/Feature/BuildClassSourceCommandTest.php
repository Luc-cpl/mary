<?php

use Illuminate\Support\Facades\File;
use Mary\Facades\Mary;
use Mary\Support\ClassSourceRegistry;

beforeEach(function () {
    $this->classSourceDirectory = sys_get_temp_dir().'/mary-tests/class-source-'.bin2hex(random_bytes(6));

    $this->app->instance(
        ClassSourceRegistry::class,
        new ClassSourceRegistry([dirname(__DIR__).'/Fixtures/ClassSources/views'])
    );

    $this->unprefixedClassSource = <<<'HTML'
<!doctype html>
<html>
<body>
!block
-mt-4
[&_.item]:block
btn
dark:hover:text-white
flex
gap-2
h-4
hidden
hover:bg-primary
lg:tooltip
lg:tooltip-bottom
lg:tooltip-left
lg:tooltip-right
lg:tooltip-top
opacity-50
text-sm
tooltip-bottom
tooltip-left
tooltip-right
w-4
w-[calc(100%-1rem)]
</body>
</html>
HTML;
    $this->unprefixedClassSource .= "\n";

    $this->prefixedClassSource = <<<'HTML'
<!doctype html>
<html>
<body>
tw:!block
tw:-mt-4
tw:[&_.item]:block
tw:btn
tw:dark:hover:text-white
tw:flex
tw:gap-2
tw:h-4
tw:hidden
tw:hover:bg-primary
tw:lg:tooltip
tw:lg:tooltip-bottom
tw:lg:tooltip-left
tw:lg:tooltip-right
tw:lg:tooltip-top
tw:opacity-50
tw:text-sm
tw:tooltip-bottom
tw:tooltip-left
tw:tooltip-right
tw:w-4
tw:w-[calc(100%-1rem)]
</body>
</html>
HTML;
    $this->prefixedClassSource .= "\n";
});

afterEach(function () {
    File::deleteDirectory($this->classSourceDirectory);
});

it('writes the exact unprefixed source to the default storage location', function () {
    $path = $this->classSourceDirectory.'/storage/framework/mary/classes.html';
    config()->set('mary.class_source_path', $path);
    config()->set('mary.tailwind_prefix', null);

    $this->artisan('mary:build-class-source')->assertSuccessful();

    expect(File::get($path))->toBe($this->unprefixedClassSource);
});

it('creates a configured custom directory and prefixes every candidate', function () {
    $path = $this->classSourceDirectory.'/resources/css/generated/mary-classes.html';
    config()->set('mary.class_source_path', $path);
    config()->set('mary.tailwind_prefix', 'tw');

    $this->artisan('mary:build-class-source')->assertSuccessful();

    expect(File::get($path))->toBe($this->prefixedClassSource);
});

it('allows path to be overridden for a single build', function () {
    $configured = $this->classSourceDirectory.'/configured.html';
    $override = $this->classSourceDirectory.'/nested/override.html';
    config()->set('mary.class_source_path', $configured);
    config()->set('mary.tailwind_prefix', 'ui');

    $this->artisan('mary:build-class-source', ['--path' => $override])->assertSuccessful();

    expect(File::exists($configured))->toBeFalse()
        ->and(File::exists($override))->toBeTrue();
});

it('treats an empty prefix as disabled', function () {
    $path = $this->classSourceDirectory.'/empty-prefix.html';
    config()->set('mary.class_source_path', $path);
    config()->set('mary.tailwind_prefix', '');

    $this->artisan('mary:build-class-source')->assertSuccessful();

    expect(File::get($path))->toBe($this->unprefixedClassSource);
});

it('rejects a prefix that cannot be used by Tailwind prefix()', function () {
    config()->set('mary.class_source_path', $this->classSourceDirectory.'/invalid.html');
    config()->set('mary.tailwind_prefix', 'tw-2');

    $this->artisan('mary:build-class-source')->assertFailed();
});

it('includes Blade sources registered by another package', function () {
    $path = $this->classSourceDirectory.'/extended.html';
    config()->set('mary.class_source_path', $path);

    Mary::addClassSourcePath(dirname(__DIR__).'/Fixtures/AdditionalClassSources/views');

    $this->artisan('mary:build-class-source')->assertSuccessful();

    expect(File::get($path))->toContain("package-candidate\n");
});
