<?php

/**
 * @file plugins/generic/latexConverter/classes/Helpers/FileSystemHelper.php
 *
 * @copyright (c) 2021-2025 TIB Hannover
 * @copyright (c) 2021-2025 Gazi Yücel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FileSystemHelper
 *
 * @ingroup plugins_generic_latexconverter
 *
 * @brief FileSystemHelper
 */

namespace APP\plugins\generic\latexConverter\classes\Helpers;


use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FileSystemHelper
{
    /**
     * Delete folder and its contents recursively.
     */
    public static function removeDirectoryAndContentsRecursively(string $path): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        $dir = opendir($path);

        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $full = $path . DIRECTORY_SEPARATOR . $file;
                if (is_dir($full)) {
                    self::removeDirectoryAndContentsRecursively($full);
                } else {
                    unlink($full);
                }
            }
        }

        closedir($dir);

        rmdir($path);

        return true;
    }

    /**
     * Get all files in a directory recursively.
     */
    public static function getDirectoryFilesRecursively(string $path): array
    {
        $files = [];

        if (empty($path) || !file_exists($path)) {
            return $files;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!is_dir($file)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
