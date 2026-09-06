<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class HeroiconRegistry
{
    /**
     * All available OUTLINE icon names, discovered from disk and cached
     * forever. Handles two possible package layouts:
     *
     *  - v2 style: resources/svg/outline/{name}.svg          (no prefix)
     *  - v1 style: resources/svg/o-{name}.svg                (flat, prefixed)
     *
     * Only outline icons are indexed since category-icon.blade.php always
     * renders via the 'heroicon-o-' prefix — solid variants are ignored.
     */
    public static function names(): array
    {
        return Cache::rememberForever('heroicon_available_names', function () {
            $names = [];

            // v2-style: subfolder, filenames have no style prefix.
            $outlineDir = base_path('vendor/blade-ui-kit/blade-heroicons/resources/svg/outline');
            if (is_dir($outlineDir)) {
                foreach (glob($outlineDir . '/*.svg') as $file) {
                    $names[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }

            // v1-style: flat folder, filenames prefixed with 'o-' for
            // outline / 's-' for solid — strip the prefix so the stored
            // name matches what category-icon.blade.php expects.
            $flatDir = base_path('vendor/blade-ui-kit/blade-heroicons/resources/svg');
            if (is_dir($flatDir)) {
                foreach (glob($flatDir . '/o-*.svg') as $file) {
                    $base = pathinfo($file, PATHINFO_FILENAME); // e.g. 'o-document-duplicate'
                    $names[] = preg_replace('/^o-/', '', $base); // -> 'document-duplicate'
                }
            }

            return collect($names)->unique()->sort()->values()->all();
        });
    }

    public static function exists(string $name): bool
    {
        return in_array($name, self::names(), true);
    }

    /**
     * Simple substring search over the full icon list, capped so the
     * results grid never renders an unreasonable number of buttons.
     */
    public static function search(string $term, int $limit = 24): array
    {
        $term = strtolower(trim($term));
        if ($term === '') {
            return [];
        }

        return collect(self::names())
            ->filter(fn ($name) => str_contains($name, $term))
            ->take($limit)
            ->values()
            ->all();
    }
}