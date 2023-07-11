<?php
/**
 * @file plugins/generic/latexConverter/classes/Models/Cleanup.inc.php
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Cleanup
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Cleanup methods
 */

namespace TIBHannover\LatexConverter\Models;

class Cleanup
{
    /**
     * Delete folder and its contents recursively
     * @note Adapted from https://www.php.net/manual/de/function.rmdir.php#117354
     * @param $src
     * @return void
     */
    public function removeDirectoryAndContentsRecursively($src): void
    {
        $dir = opendir($src);

        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $full = $src . '/' . $file;
                if (is_dir($full)) {
                    $this->removeDirectoryAndContentsRecursively($full);
                } else {
                    unlink($full);
                }
            }
        }

        closedir($dir);

        rmdir($src);
    }
}