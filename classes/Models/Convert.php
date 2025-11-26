<?php

/**
 * @file plugins/generic/latexConverter/classes/Models/Convert.php
 *
 * @copyright (c) 2021-2025 TIB Hannover
 * @copyright (c) 2021-2025 Gazi Yücel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Convert
 *
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Action Convert for the Handler
 */

namespace APP\plugins\generic\latexConverter\classes\Models;

use APP\facades\Repo;
use APP\plugins\generic\latexConverter\classes\Constants;
use APP\plugins\generic\latexConverter\classes\Helpers\FileSystemHelper;
use APP\plugins\generic\latexConverter\classes\Helpers\SubmissionFileHelper;
use APP\plugins\generic\latexConverter\LatexConverterPlugin;
use PKP\config\Config;
use PKP\core\PKPApplication;
use PKP\submissionFile\SubmissionFile;

class Convert
{
    protected LatexConverterPlugin $plugin;
    protected object $submission;
    protected object $submissionFile;

    /**
     * Absolute path to the work directory.
     * e.g. /tmp/latexConverter_20230701_150101
     */
    protected string $workingDirPath;

    /**
     * The dependent files for this submission id.
     */
    protected array $submissionFileDependents;

    /**
     * The name of the main tex file.
     * e.g. main.tex
     */
    protected string $mainFileName = '';

    /**
     * The names of the dependent files.
     * e.g. [ 'image1.png', ... ]
     */
    protected array $dependentFileNames = [];

    function __construct(LatexConverterPlugin $plugin, SubmissionFile $submissionFile)
    {
        $this->plugin = $plugin;
        $this->submissionFile = $submissionFile;

        $this->submission = Repo::submission()->get((int)$this->submissionFile->getData('submissionId'));
        $this->mainFileName = $this->submissionFile->getData('name')[$this->submission->getData('locale')];
        $this->workingDirPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->plugin->getName() . '_' . date('Ymd_His') . '_' . uniqid();
        $this->submissionFileDependents = $this->getSubmissionFileDependents();
    }

    /**
     * Main entry point.
     */
    public function process(): array
    {
        $pdfFileName = str_replace('.' . Constants::TEX_EXTENSION, '.' . Constants::PDF_EXTENSION, $this->mainFileName);
        $logFileName = str_replace('.' . Constants::TEX_EXTENSION, '.' . Constants::LOG_EXTENSION, $this->mainFileName);

        // create working directory
        if (!mkdir($this->workingDirPath, 0777, true)) {
            return ['error' => __('plugins.generic.latexConverter.notification.defaultErrorOccurred')];
        }

        // get dependent files
        if (empty($this->submissionFileDependents)) {
            return ['error' => __('plugins.generic.latexConverter.notification.defaultErrorOccurred')];
        }

        // get files and copy file to working directory
        if (!$this->copyFilesToWorkingDir()) {
            return ['error' => __('plugins.generic.latexConverter.notification.defaultErrorOccurred')];
        }

        // do the conversion to pdf
        if (!$this->convertToPdf()) {
            return ['error' => __('plugins.generic.latexConverter.notification.defaultErrorOccurred')];
        }

        // check if pdf file exists and add this as main file
        if (file_exists($this->workingDirPath . DIRECTORY_SEPARATOR . $pdfFileName)) {
            if (!$this->addFiles($pdfFileName, str_replace('.' . Constants::TEX_EXTENSION, '', $this->mainFileName))) {
                return ['error' => __('plugins.generic.latexConverter.notification.defaultErrorOccurred')];
            }
        } // no pdf file found, check if log file exists and add this as main file
        elseif (file_exists($this->workingDirPath . DIRECTORY_SEPARATOR . $logFileName)) {
            if (!$this->addFiles($logFileName, str_replace('.' . Constants::TEX_EXTENSION, '', $this->mainFileName))) {
                return ['error' => __('plugins.generic.latexConverter.notification.defaultErrorOccurred')];
            }
        } else {
            return ['error' => __('plugins.generic.latexConverter.notification.defaultErrorOccurred')];
        }

        return [
            'pdfFile' => $pdfFileName,
            'logFile' => $logFileName,
            'mainFile' => $this->mainFileName,
            'dependentFiles' => $this->submissionFileDependents
        ];
    }

    /**
     * Get and return main and dependent files for this submissionFile.
     */
    private function getSubmissionFileDependents(): array
    {
        $files = [];

        $allFiles = Repo::submissionFile()->getCollector()
            ->filterByAssoc(
                PKPApplication::ASSOC_TYPE_SUBMISSION_FILE,
                [$this->submissionFile->getId()])
            ->filterBySubmissionIds([$this->submission->getId()])
            ->filterByFileStages([SubmissionFile::SUBMISSION_FILE_DEPENDENT])
            ->includeDependentFiles()
            ->getMany();

        foreach ($allFiles as $file) {
            if ($file->getData('assocId') == $this->submissionFile->getId())
                $files[] = $file;
        }

        return $files;
    }

    /**
     * Get files of submission and copy to working dir.
     */
    private function copyFilesToWorkingDir(): bool
    {
        $filesDirPath = Config::getVar('files', 'files_dir');

        // main file
        copy(
            $filesDirPath . DIRECTORY_SEPARATOR . $this->submissionFile->getData('path'),
            $this->workingDirPath . DIRECTORY_SEPARATOR .
            $this->submissionFile->getData('name')[$this->submission->getData('locale')]
        );

        // dependent files
        foreach ($this->submissionFileDependents as $file) {
            $fromFile = $filesDirPath . DIRECTORY_SEPARATOR . $file->getData('path');
            $toFile = $this->workingDirPath . DIRECTORY_SEPARATOR . $file->getData('name')[$this->submission->getData('locale')];

            if (!file_exists(dirname($toFile))) {
                mkdir(dirname($toFile), 0777, true);
            }

            copy($fromFile, $toFile);
        }

        return true;
    }

    /**
     * Convert LaTex file to pdf.
     */
    private function convertToPdf(): bool
    {
        $latexExec = $this->plugin->getSetting(
            $this->plugin->getRequest()->getContext()->getId(),
            Constants::SETTING_LATEX_PATH_EXECUTABLE
        );

        shell_exec("cd $this->workingDirPath " .
            "&& $latexExec -no-shell-escape -interaction=nonstopmode $this->mainFileName 2>&1");

        return true;
    }

    /**
     * Add output files to submission.
     */
    private function addFiles(string $fileToAdd, string $fileToAddWithoutExtension): bool
    {
        $files = array_map(
            'basename',
            glob($this->workingDirPath . "/" . $fileToAddWithoutExtension . "*"));

        foreach ($files as $file) {
            if ($file !== $this->mainFileName && $file !== $fileToAdd) {
                $this->dependentFileNames[] = $file;
            }
        }

        $submissionFileHelper = new SubmissionFileHelper($this->submissionFile, $fileToAdd, $this->dependentFileNames, $this->workingDirPath);
        if (!$submissionFileHelper->addMainFile()) {
            return false;
        }

        if (!empty($this->dependentFileNames))
            if (!$submissionFileHelper->addDependentFiles()) {
                return false;
            }

        return true;
    }

    function __destruct()
    {
        if (file_exists($this->workingDirPath)) {
            FileSystemHelper::removeDirectoryAndContentsRecursively($this->workingDirPath);
        }
    }
}
