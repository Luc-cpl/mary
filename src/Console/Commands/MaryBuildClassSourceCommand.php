<?php

namespace Mary\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Mary\ClassBuilder;
use Mary\Support\ClassCandidateExtractor;
use Mary\Support\ClassSourceRegistry;
use RuntimeException;

class MaryBuildClassSourceCommand extends Command
{
    protected $signature = 'mary:build-class-source {--path= : Override the generated class source path}';

    protected $description = 'Generate the static HTML source for Mary CSS classes';

    public function __construct(protected ClassSourceRegistry $classSources)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $prefix = config('mary.tailwind_prefix');

        if (($prefix !== null && ! is_string($prefix))
            || (is_string($prefix) && trim($prefix) !== '' && ! preg_match('/^[a-z]+[:-]?$/', $prefix))) {
            $this->components->error('The Mary Tailwind prefix must contain lowercase ASCII letters and may end with : or -.');

            return self::FAILURE;
        }

        $path = $this->resolvePath($this->option('path') ?: config('mary.class_source_path'));
        $builder = new ClassBuilder($prefix);
        $classes = ClassCandidateExtractor::fromPaths($this->classSources->all());
        $classes = array_map(
            fn (string $class): string => (string) $builder->add($class),
            $classes
        );
        $classes = implode("\n", $classes);

        File::ensureDirectoryExists(dirname($path));

        $temporaryPath = tempnam(dirname($path), 'mary-classes-');

        if ($temporaryPath === false) {
            throw new RuntimeException("Unable to create a temporary file in [{$path}].");
        }

        File::put($temporaryPath, "<!doctype html>\n<html>\n<body>\n{$classes}\n</body>\n</html>\n");

        if (! rename($temporaryPath, $path)) {
            File::delete($temporaryPath);

            throw new RuntimeException("Unable to write Mary class source to [{$path}].");
        }

        $this->components->info("Mary class source generated at [{$path}].");

        return self::SUCCESS;
    }

    protected function resolvePath(?string $path): string
    {
        if (blank($path)) {
            throw new RuntimeException('Mary class source path is not configured.');
        }

        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            return $path;
        }

        return base_path($path);
    }
}
