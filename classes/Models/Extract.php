<?php

/**
 * @file plugins/generic/latexConverter/classes/Models/Extract.php
 *
 * Copyright (c) 2021-2025 TIB Hannover
 * Copyright (c) 2021-2025 Gazi Yücel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Extract
 *
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Action Extract for the Handler
 */

namespace APP\plugins\generic\latexConverter\classes\Models;

use APP\facades\Repo;
use APP\plugins\generic\latexConverter\classes\Helpers\FileSystemHelper;
use APP\plugins\generic\latexConverter\classes\Helpers\SubmissionFileHelper;
use APP\plugins\generic\latexConverter\classes\Helpers\ZipHelper;
use APP\plugins\generic\latexConverter\LatexConverterPlugin;
use PKP\file\PrivateFileManager;
use PKP\submissionFile\SubmissionFile;

class Extract
{
    protected LatexConverterPlugin $plugin;

    /**
     * Absolute path to the directory with the extracted content of archive.
     * e.g. /var/tmp/latexconverterplugin_20251101_120001_648b243110d7e
     */
    protected string $workDirPath = '';

    function __construct(LatexConverterPlugin $plugin)
    {
        $this->plugin = $plugin;

        $this->workDirPath =
            sys_get_temp_dir() . DIRECTORY_SEPARATOR .
            $this->plugin->getName() . '_' . date('Ymd_His') . '_' . uniqid();
    }

    /**
     * Extracts file and adds to files list.
     */
    public function listFiles(SubmissionFile $submissionFile): array
    {
        $fileManager = new PrivateFileManager();

        return ZipHelper::getZipContentTexFilesFirst(
            $fileManager->getBasePath() . DIRECTORY_SEPARATOR . $submissionFile->getData('path')
        );
    }

    /**
     * Process after selecting main file.
     */
    public function extractFiles(SubmissionFile $submissionFile, string $mainFileName): array
    {
        $dependentFileNames = [];
        $fileManager = new PrivateFileManager();
        $submission = Repo::submission()->get((int)$submissionFile->getData('submissionId'));
        $archiveFileAbsolutePath = $fileManager->getBasePath() . DIRECTORY_SEPARATOR . $submissionFile->getData('path');
        $submissionFilesRelativeDir = Repo::submissionFile()->getSubmissionDir(
            $submission->getData('contextId'),
            (int)$submissionFile->getData('submissionId')
        );

        if (!ZipHelper::extractZip($archiveFileAbsolutePath, $this->workDirPath)) {
            return ['error' => __('plugins.generic.latexConverter.notification.errorOpeningFile')];
        }

        // get all dependent files
        $relativeZipRoot = ZipHelper::getRelativeZipRoot($archiveFileAbsolutePath);
        $absoluteZipPath = $this->workDirPath . DIRECTORY_SEPARATOR . $relativeZipRoot;
        foreach (FileSystemHelper::getDirectoryFilesRecursively($absoluteZipPath) as $file) {
            $fileName = str_replace($absoluteZipPath, '', $file);
            if ($fileName !== $mainFileName) {
                $dependentFileNames[] = $fileName;
            }
        }

        $submissionFileHelper = new SubmissionFileHelper($submissionFile, $mainFileName, $dependentFileNames, $absoluteZipPath);
        if (!$submissionFileHelper->addMainFile()) {
            return ['error' => __('plugins.generic.latexConverter.notification.defaultErrorOccurred')];
        }
        if (!$submissionFileHelper->addDependentFiles()) {
            return ['error' => __('plugins.generic.latexConverter.notification.defaultErrorOccurred')];
        }

        return [array_merge([$mainFileName], $dependentFileNames)];
    }

    function __destruct()
    {
        if (file_exists($this->workDirPath)) {
            FileSystemHelper::removeDirectoryAndContentsRecursively($this->workDirPath);
        }
    }
}
