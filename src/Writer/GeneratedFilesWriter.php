<?php

declare(strict_types=1);

/*
 * This file is part of the App JS Bundle package.
 *
 * (c) SILARHI <dev@silarhi.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silarhi\AppJsBundle\Writer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Keeps a directory of generated files equal to what a dumper returns: writes the files that changed, removes the ones
 * with its extension it no longer returns. The directory belongs to the dumper.
 *
 * @internal
 */
final readonly class GeneratedFilesWriter
{
    public function __construct(private Filesystem $filesystem = new Filesystem())
    {
    }

    /**
     * @param array<string, string> $files     the content of each file, by its path relative to $directory
     * @param string                $extension the extension of the generated files: ts, js, d.ts
     *
     * @return list<string> the paths written or removed
     */
    public function write(string $directory, array $files, string $extension): array
    {
        $changes = $this->getChanges($directory, $files, $extension);
        foreach ($changes as $path) {
            if (isset($files[$path])) {
                $this->filesystem->dumpFile($directory . '/' . $path, $files[$path]);
            } else {
                $this->filesystem->remove($directory . '/' . $path);
            }
        }

        return $changes;
    }

    /**
     * @param array<string, string> $files     the content of each file, by its path relative to $directory
     * @param string                $extension the extension of the generated files: ts, js, d.ts
     *
     * @return list<string> the paths that differ: added, changed or no longer generated
     */
    public function getChanges(string $directory, array $files, string $extension): array
    {
        $changes = [];
        foreach ($files as $path => $content) {
            $file = $directory . '/' . $path;
            if (!is_file($file) || file_get_contents($file) !== $content) {
                $changes[] = $path;
            }
        }

        if (is_dir($directory)) {
            foreach ((new Finder())->files()->in($directory)->name('*.' . $extension)->sortByName() as $file) {
                $path = str_replace('\\', '/', $file->getRelativePathname());
                if (!isset($files[$path])) {
                    $changes[] = $path;
                }
            }
        }

        sort($changes);

        return $changes;
    }
}
