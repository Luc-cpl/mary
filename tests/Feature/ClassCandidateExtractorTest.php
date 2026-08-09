<?php

use Illuminate\Support\Facades\File;
use Mary\Support\ClassCandidateExtractor;

beforeEach(function () {
    $this->extractorDirectory = sys_get_temp_dir() . '/mary-tests/extractor-' . bin2hex(random_bytes(6));
    File::ensureDirectoryExists($this->extractorDirectory);
});

afterEach(function () {
    File::deleteDirectory($this->extractorDirectory);
});

it('recognizes supported call styles and ignores commented calls and raw classes', function () {
    $path = $this->extractorDirectory . '/calls.blade.php';

    File::put($path, <<<'SOURCE'
<?php

// Mary::classes('php-comment');
/** app('mary')->classes('doc-comment'); */
Mary :: classes('facade-class');
app ( "mary" ) -> classes('app-class')
    -> add('chained-class')
    -> addRaw('raw-class');
?>

{{-- @maryClass('blade-comment') --}}
<!-- @maryClass('html-comment') -->
<div @maryClass('directive-class') {{ $attributes -> maryClass('attribute-class') }}></div>
SOURCE);

    expect(ClassCandidateExtractor::fromPaths([$path]))->toBe([
        'app-class',
        'attribute-class',
        'chained-class',
        'directive-class',
        'facade-class',
    ]);
});

it('extracts ternary results without treating condition literals as classes', function () {
    $path = $this->extractorDirectory . '/ternary.php';

    File::put($path, <<<'SOURCE'
<?php

Mary::classes($state === 'condition-value' ? 'active' : 'inactive');
Mary::classes($override ?? 'fallback');
SOURCE);

    expect(ClassCandidateExtractor::fromPaths([$path]))->toBe([
        'active',
        'fallback',
        'inactive',
    ]);
});

it('fails when a dynamic candidate cannot be resolved', function () {
    $path = $this->extractorDirectory . '/unresolved.php';

    File::put($path, <<<'SOURCE'
<?php

Mary::classes("btn {$missingClass}");
SOURCE);

    expect(fn () => ClassCandidateExtractor::fromPaths([$path]))
        ->toThrow(RuntimeException::class, 'Unable to resolve dynamic Mary class [{$missingClass}]');
});

it('fails when a configured source path does not exist', function () {
    $path = $this->extractorDirectory . '/missing';

    expect(fn () => ClassCandidateExtractor::fromPaths([$path]))
        ->toThrow(RuntimeException::class, "Mary class source path [{$path}] does not exist.");
});

it('recursively reads PHP sources and returns a sorted unique catalog', function () {
    File::ensureDirectoryExists($this->extractorDirectory . '/nested');
    File::put($this->extractorDirectory . '/first.php', "<?php Mary::classes('zeta shared');");
    File::put($this->extractorDirectory . '/nested/second.blade.php', "<div @maryClass('alpha shared')></div>");
    File::put($this->extractorDirectory . '/ignored.txt', "Mary::classes('ignored')");

    expect(ClassCandidateExtractor::fromPaths([$this->extractorDirectory]))->toBe([
        'alpha',
        'shared',
        'zeta',
    ]);
});
