<?php
/**
 * @file plugins/generic/latexConverter/classes/Action/Convert.inc.php
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Convert
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Action Convert for the Handler
 */

namespace TIBHannover\LatexConverter\Action;

import('lib.pkp.classes.file.PrivateFileManager');

use Config;
use JSONMessage;
use NotificationManager;
use PrivateFileManager;
use Services;
use SubmissionDAO;
use TIBHannover\LatexConverter\Models\ArticleSubmissionFile;
use TIBHannover\LatexConverter\Models\Cleanup;

class Convert
{
    /**
     * @var object LatexConverterPlugin
     */
    protected object $plugin;

    /**
     * @var PrivateFileManager
     */
    protected PrivateFileManager $fileManager;

    /**
     * @var NotificationManager
     */
    protected NotificationManager $notificationManager;

    /**
     * @var mixed Request
     */
    protected mixed $request;

    /**
     * @var object Submission
     */
    protected object $submission;

    /**
     * @var string
     */
    protected string $timeStamp;

    /**
     * @var int
     */
    protected int $submissionId;

    /**
     * @var object SubmissionFile
     */
    protected object $submissionFile;

    /**
     * @var int
     */
    protected int $submissionFileId;

    /**
     * Absolute path to the work directory
     * e.g. /tmp/latexConverter_20230701_150101
     * @var string
     */
    protected string $workingDirAbsolutePath;

    /**
     * Path to directory for files of this submission
     * e.g. journals/1/articles/51
     * @var string
     */
    protected string $submissionFilesRelativeDir;

    /**
     * Absolute path to ojs files directory
     * e.g. /var/www/ojs_files
     * @var string
     */
    protected string $ojsFilesAbsoluteBaseDir;

    /**
     * The main and dependent files for this submission id
     * @var array
     */
    protected array $submissionFileAndDependents = [];

    /**
     * The name of the main tex file
     * e.g. main.tex
     * @var string
     */
    protected string $mainFileName = '';

    /**
     * The names of the dependent files
     * e.g. [ 'image1.png', ... ]
     * @var string[]
     */
    protected array $dependentFileNames = [];

    /**
     * Generated PDF file
     * e.g. main.pdf
     * @var string
     */
    protected string $pdfFile = '';

    /**
     * Name of the log file
     * e.g. main.log
     * @var string
     */
    protected string $logFile = '';

    function __construct($plugin, $request, $params)
    {
        $this->timeStamp = date('Ymd_His');

        $this->plugin = $plugin;

        $this->notificationManager = new NotificationManager();

        $this->request = $request;

        $this->submissionFileId = (int)$this->request->getUserVar('submissionFileId');
        $this->submissionFile = Services::get('submissionFile')->get($this->submissionFileId);

        $this->mainFileName = $this->submissionFile->getData('name')[$this->submissionFile->getData('locale')];

        $this->pdfFile = str_replace('.' . LATEX_CONVERTER_TEX_EXTENSION,
            '.' . LATEX_CONVERTER_PDF_EXTENSION, $this->mainFileName);

        $this->logFile = str_replace('.' . LATEX_CONVERTER_TEX_EXTENSION,
            '.' . LATEX_CONVERTER_LOG_EXTENSION, $this->mainFileName);

        $submissionDao = new SubmissionDAO();
        $this->submissionId = (int)$this->submissionFile->getData('submissionId');
        $this->submission = $submissionDao->getById($this->submissionId);

        $this->workingDirAbsolutePath =
            tempnam(sys_get_temp_dir(), LATEX_CONVERTER_PLUGIN_NAME . '_') . '_' . $this->timeStamp;

        $this->submissionFilesRelativeDir = Services::get('submissionFile')->getSubmissionDir(
            $this->submission->getData('contextId'), $this->submissionId);

        $this->ojsFilesAbsoluteBaseDir = Config::getVar('files', 'files_dir');
    }

    /**
     * Main entry point
     * @return JSONMessage
     */
    public function execute(): JSONMessage
    {
        // create working directory
        if (!mkdir($this->workingDirAbsolutePath, 0777, true)) {
            $this->notificationManager->createTrivialNotification(
                $this->request->getUser(), NOTIFICATION_TYPE_ERROR,
                array('contents' => __('plugins.generic.latexConverter.notification.defaultErrorOccurred')));
            return $this->defaultResponse();
        }

        // get submission file and dependent files
        if (!$this->getSubmissionFileAndDependents()) {
            $this->notificationManager->createTrivialNotification(
                $this->request->getUser(), NOTIFICATION_TYPE_ERROR,
                array('contents' => __('plugins.generic.latexConverter.notification.defaultErrorOccurred')));
            return $this->defaultResponse();
        }

        // get files and copy file to working directory
        if (!$this->copyFilesToWorkingDir()) {
            $this->notificationManager->createTrivialNotification(
                $this->request->getUser(), NOTIFICATION_TYPE_ERROR,
                array('contents' => __('plugins.generic.latexConverter.notification.defaultErrorOccurred')));
            return $this->defaultResponse();
        }

        // do the conversion to pdf
        if (!$this->convertToPdf()) {
            $this->notificationManager->createTrivialNotification(
                $this->request->getUser(), NOTIFICATION_TYPE_ERROR,
                array('contents' => __('plugins.generic.latexConverter.notification.defaultErrorOccurred')));
            return $this->defaultResponse();
        }

        // check if pdf file exists and add this as main file
        if (file_exists($this->workingDirAbsolutePath . DIRECTORY_SEPARATOR . $this->pdfFile)) {
            if (!$this->addFiles($this->pdfFile,
                str_replace('.' . LATEX_CONVERTER_TEX_EXTENSION, '', $this->mainFileName)))
                return $this->defaultResponse();
        } // no pdf file found, check if log file exists and add this as main file
        elseif (file_exists($this->workingDirAbsolutePath . DIRECTORY_SEPARATOR . $this->logFile)) {
            if (!$this->addFiles($this->logFile,
                str_replace('.' . LATEX_CONVERTER_TEX_EXTENSION, '', $this->mainFileName)))
                return $this->defaultResponse();
        } else {
            return $this->defaultResponse();
        }

        // all went well, return ok
        return $this->defaultResponse(true);
    }

    /**
     * Get and return main and dependent files for this submissionFile
     * @return bool
     */
    private function getSubmissionFileAndDependents(): bool
    {
        $allFiles = Services::get('submissionFile')->getMany([
            'assocIds' => [$this->submissionId],
            'submissionIds' => [$this->submissionId],
            'includeDependentFiles' => true
        ]);

        foreach ($allFiles as $file) {
            if ($file->getData('id') == $this->submissionFileId)
                $this->mainFileName = $file->getData('name')[$file->getData('locale')];

            if ($file->getData('id') == $this->submissionFileId ||
                $file->getData('assocId') == $this->submissionFileId)
                $this->submissionFileAndDependents[] = $file;
        }

        if (empty($this->submissionFileAndDependents)) return false;

        return true;
    }

    /**
     * Get files of submission and copy to working dir
     * @return bool
     */
    private function copyFilesToWorkingDir(): bool
    {
        foreach ($this->submissionFileAndDependents as $file) {
            copy($this->ojsFilesAbsoluteBaseDir . DIRECTORY_SEPARATOR . $file->getData('path'),
                $this->workingDirAbsolutePath . DIRECTORY_SEPARATOR . $file->getData('name')[$file->getData('locale')]);
        }

        return true;
    }

    /**
     * Convert LaTex file to pdf
     * @return bool
     */
    private function convertToPdf(): bool
    {
        $pdfLatex = $this->plugin->getSetting(
            $this->request->getContext()->getId(),
            LATEX_CONVERTER_SETTING_KEY_PATH_EXECUTABLE);

        if (empty($pdfLatex)) {
            $this->notificationManager->createTrivialNotification(
                $this->request->getUser(), NOTIFICATION_TYPE_ERROR,
                array('contents' => __('plugins.generic.latexConverter.notification.defaultErrorOccurred')));
            return false;
        }

        $pdfLatex .= ' -no-shell-escape -interaction=nonstopmode';

        shell_exec("cd $this->workingDirAbsolutePath && $pdfLatex $this->mainFileName 2>&1");

        return true;
    }

    /**
     * Add output files to submission
     * @param string $fileToAdd
     * @param string $fileToAddWithoutExtension
     * @return bool
     */
    private function addFiles(string $fileToAdd, string $fileToAddWithoutExtension): bool
    {
        $files = array_map('basename', glob($this->workingDirAbsolutePath . "/" . $fileToAddWithoutExtension . "*"));

        foreach ($files as $file)
            if ($file !== $this->mainFileName && $file !== $fileToAdd)
                $this->dependentFileNames[] = $file;

        $articleSubmissionFile = new ArticleSubmissionFile($this->request, $this->submissionId, $this->submissionFile,
            $this->workingDirAbsolutePath, $this->submissionFilesRelativeDir, $fileToAdd,
            $this->dependentFileNames);

        if (!$articleSubmissionFile->addMainFile()) return false;

        if (!empty($this->dependentFileNames))
            if (!$articleSubmissionFile->addDependentFiles()) return false;

        return true;
    }

    /**
     * Default response
     * Only submissionId is returned as a JSONMessage
     * @param bool $status
     * @return JSONMessage
     */
    private function defaultResponse(bool $status = false): JSONMessage
    {
        return new JSONMessage($status, ['submissionId' => $this->submissionId]);
    }

    function __destruct()
    {
        if (file_exists($this->workingDirAbsolutePath)) {
            $cleanup = new Cleanup();
            $cleanup->removeDirectoryAndContentsRecursively($this->workingDirAbsolutePath);
        }
    }
}
