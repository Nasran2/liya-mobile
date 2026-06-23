<?php

$log = '';

function deleteDirectoryContents($dir)
{
    global $log;
    if (! is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->getFilename() === '.gitignore') {
            continue;
        }

        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
            $log .= 'Deleted cache file: '.$file->getFilename().'<br>';
        }
    }
}

// 1. Clear Bootstrap Cache
$bootstrapCacheDir = __DIR__.'/../bootstrap/cache';
deleteDirectoryContents($bootstrapCacheDir);

// 2. Clear Views Cache including Livewire SFC extracted views
$viewsCacheDir = __DIR__.'/../storage/framework/views';
deleteDirectoryContents($viewsCacheDir);

echo '<h3>Caches Cleared Successfully!</h3>';
echo $log ?: 'No cache files found.<br>';

echo '<br><p><strong>Now please visit the /pos page again to see if it works!</strong></p>';
