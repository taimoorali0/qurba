<?php
// ===== QURBA: Quran/Urdu text must keep its line height on every screen size =====
// Tailwind's responsive sizes (md:text-3xl …) also set a tight line-height. An element that picks its own
// leading-* and also has a responsive size must repeat the leading for that breakpoint, or lines overlap.
it('repeats custom line heights for responsive Quran and Urdu text sizes', function () {
    $problems = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('js'))) as $file) {
        if ($file->getExtension() !== 'vue') continue;
        preg_match_all('/class="([^"]*\bfont-(?:quran|urdu)\b[^"]*)"/', file_get_contents($file->getPathname()), $m);
        foreach ($m[1] as $classes) {
            if (! preg_match('/(?:^|\s)leading-/', $classes)) continue; // the global rule covers these
            preg_match_all('/\b(sm|md|lg|xl):text-(?:xs|sm|base|lg|[2-9]?xl)\b/', $classes, $bp);
            foreach (array_unique($bp[1]) as $b) {
                if (! preg_match("/\\b{$b}:leading-/", $classes)) $problems[] = basename($file->getPathname()).": {$classes}";
            }
        }
    }
    expect($problems)->toBe([]);
});
