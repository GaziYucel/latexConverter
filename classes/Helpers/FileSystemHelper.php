<?php
/**
 * @file plugins/generic/latexConverter/classes/Helpers/FileSystemHelper.php
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yücel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FileSystemHelper
 * @ingroup plugins_generic_latexconverter
 *
 * @brief FileSystemHelper
 */

namespace APP\plugins\generic\latexConverter\classes\Helpers;

class FileSystemHelper
{
    /**
     * Delete folder and its contents recursively
     *
     * @note Adapted from https://www.php.net/manual/de/function.rmdir.php#117354
     * @param $path string
     * @return bool
     */
    public static function removeDirectoryAndContentsRecursively(string $path): bool
    {
        if (!file_exists($path)) return false;

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
     * Get all files in a directory recursively
     *
     * @param string $path
     * @return array
     */
    public static function getDirectoryFilesRecursively(string $path): array
    {
        $files = [];

        if (empty($path) || !file_exists($path)) return $files;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (!is_dir($file)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
