<?php

/*
 * Copies the locally installed Li Ador Noirrit font (Unicode weights) into public/ so the browser can load it.
 * The font is NOT in the repository: its Lipighor licence forbids redistribution. Each member downloads it from
 * https://lipighor.com and places the unzipped "LiAdorNoirritMultiweight" folder in resources/fonts/.
 * Without it, Bangla text falls back to Noto Sans Bengali (committed, OFL). Run: composer fonts
 */
$source = __DIR__.'/../resources/fonts/LiAdorNoirritMultiweight/Unicode/';
$target = __DIR__.'/../public/fonts/li-ador-noirrit/';
$weights = ['Regular', 'SemiBold', 'Bold'];

if (! is_dir($source)) {
    fwrite(STDERR, "Li Ador Noirrit not found in resources/fonts/LiAdorNoirritMultiweight/Unicode/.\n");
    fwrite(STDERR, "Download it from https://lipighor.com (see docs/first-time-setup.md). Bangla uses Noto Sans Bengali until then.\n");
    exit(0);
}

if (! is_dir($target) && ! mkdir($target, 0777, true)) {
    fwrite(STDERR, "Could not create public/fonts/li-ador-noirrit/.\n");
    exit(1);
}

foreach ($weights as $weight) {
    $file = "Li Ador Noirrit {$weight}.ttf";
    if (! is_file($source.$file) || ! copy($source.$file, $target.$file)) {
        fwrite(STDERR, "Missing or not copied: {$file}\n");
        exit(1);
    }
}

echo "Li Ador Noirrit (Regular, SemiBold, Bold) copied to public/fonts/li-ador-noirrit/.\n";
