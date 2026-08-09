<?php

namespace Mary\Traits;

use Illuminate\Support\Facades\Blade;
use Mary\Facades\Mary;

trait Toast
{
    public function toast(
        string $type,
        string $title,
        ?string $description = null,
        ?string $position = null,
        string $icon = 'o-information-circle',
        ?string $css = null,
        int $timeout = 3000,
        ?string $redirectTo = null,
        bool $noProgress = false,
        ?string $progressClass = null,
    ) {
        $defaultCss = match ($type) {
            'success' => Mary::classes('alert-success'),
            'warning' => Mary::classes('alert-warning'),
            'error' => Mary::classes('alert-error'),
            default => Mary::classes('alert-info'),
        };
        $iconClasses = Mary::classes('w-7 h-7');

        $toast = [
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'position' => $position,
            'icon' => Blade::render("<x-mary-icon class='{$iconClasses}' name='".$icon."' />"),
            'css' => $css ?? (string) $defaultCss,
            'timeout' => $timeout,
            'noProgress' => $noProgress,
            'progressClass' => $progressClass,
        ];

        $this->js('toast('.json_encode(['toast' => $toast]).')');

        session()->flash('mary.toast.title', $title);
        session()->flash('mary.toast.description', $description);

        if ($redirectTo) {
            return $this->redirect($redirectTo, navigate: true);
        }
    }

    public function success(
        string $title,
        ?string $description = null,
        ?string $position = null,
        string $icon = 'o-check-circle',
        ?string $css = null,
        int $timeout = 3000,
        ?string $redirectTo = null,
        bool $noProgress = false,
        ?string $progressClass = null,
    ) {
        return $this->toast('success', $title, $description, $position, $icon, $css, $timeout, $redirectTo, $noProgress, $progressClass);
    }

    public function warning(
        string $title,
        ?string $description = null,
        ?string $position = null,
        string $icon = 'o-exclamation-triangle',
        ?string $css = null,
        int $timeout = 3000,
        ?string $redirectTo = null,
        bool $noProgress = false,
        ?string $progressClass = null,
    ) {
        return $this->toast('warning', $title, $description, $position, $icon, $css, $timeout, $redirectTo, $noProgress, $progressClass);
    }

    public function error(
        string $title,
        ?string $description = null,
        ?string $position = null,
        string $icon = 'o-x-circle',
        ?string $css = null,
        int $timeout = 3000,
        ?string $redirectTo = null,
        bool $noProgress = false,
        ?string $progressClass = null,
    ) {
        return $this->toast('error', $title, $description, $position, $icon, $css, $timeout, $redirectTo, $noProgress, $progressClass);
    }

    public function info(
        string $title,
        ?string $description = null,
        ?string $position = null,
        string $icon = 'o-information-circle',
        ?string $css = null,
        int $timeout = 3000,
        ?string $redirectTo = null,
        bool $noProgress = false,
        ?string $progressClass = null,
    ) {
        return $this->toast('info', $title, $description, $position, $icon, $css, $timeout, $redirectTo, $noProgress, $progressClass);
    }
}
