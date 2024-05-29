<?php
/**
 * @file plugins/generic/latexConverter/classes/Helpers/ZipHelper.php
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yücel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ZipHelper
 * @ingroup plugins_generic_latexconverter
 *
 * @brief ZipHelper
 */

namespace APP\plugins\generic\latexConverter\classes\Helpers;

use APP\plugins\generic\latexConverter\classes\Constants;
use ZipArchive;

class ZipHelper
{
    protected static string $texMainFilename = Constants::texMainFilename;

    protected static string $texExtension = Constants::texExtension;

    /**
     * Return root folder of a ZIP with subdirectories
     *
     * @param string $path
     * @return string
     */
    public static function getRelativeZipRoot(string $path): string
    {
        $local = '';

        $zip = new ZipArchive();

        if (!$zip->open($path)) return $local;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            if ($i === 0
                && substr($zip->statIndex($i)['name'], -1) === DIRECTORY_SEPARATOR) {
                $local = $zip->statIndex($i)['name'];
            } else if ($i > 0
                && substr($zip->statIndex($i - 1)['name'], -1) === DIRECTORY_SEPARATOR
                && substr($zip->statIndex($i)['name'], -1) === DIRECTORY_SEPARATOR) {
                $local = $zip->statIndex($i)['name'];
            }
        }

        if ($zip->status) $zip->close();

        return $local;
    }

    /**
     * Extract zip file
     *
     * @param string $path
     * @param string $extractToPath
     * @return bool
     */
    public static function extractZip(string $path, string $extractToPath): bool
    {
        $zip = new ZipArchive();

        if (!$zip->open($path)) return false;

        if (!mkdir($extractToPath, 0777, true)) return false;

        $zip->extractTo($extractToPath);

        if ($zip->status) $zip->close();

        return true;
    }

    /**
     * Get list of filenames of zip file
     * Only the files in root folder are returned, subdirectories are ignored
     * e.g. [ 'main.tex', '*.tex'..., 'other'... ]
     *
     * @param string $zipPath
     * @return array
     */
    public static function getZipContentTexFilesFirst(string $zipPath): array
    {
        $texFiles = [];
        $otherFiles = [];

        $relativeZipRoot = self::getRelativeZipRoot($zipPath);

        $zip = new ZipArchive();
        if (!$zip->open($zipPath)) return [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = str_replace($relativeZipRoot, '', $stat['name']);

            if (!empty($name) && !str_contains($name, DIRECTORY_SEPARATOR)) {
                // set main.tex first in list
                if ($name === self::$texMainFilename) {
                    array_unshift($texFiles, $name);
                } // set *.tex 2+ in list
                else if (pathinfo($name, PATHINFO_EXTENSION) === self::$texExtension) {
                    $texFiles[] = $name;
                } // set other files after tex files
                else if (!empty(pathinfo($name, PATHINFO_EXTENSION))) {
                    $otherFiles[] = $name;
                }
            }
        }

        if ($zip->status) $zip->close();

        return array_merge($texFiles, $otherFiles);
    }
}
