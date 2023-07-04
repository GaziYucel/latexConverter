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
use TIBHannover\LatexConverter\Models\ArticleGalley;
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
     * The name of the main tex file
     * e.g. main.tex
     * @var string
     */
    protected string $mainFileName = '';

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

    /**
     * The names of the dependent files
     * e.g. [ 'image1.png', ... ]
     * @var string[]
     */
    protected array $dependentFileNames = [];

    function __construct($plugin, $request, $params)
    {
        $this->timeStamp = date('Ymd_His');

        $this->plugin = $plugin;

        $this->notificationManager = new NotificationManager();

        $this->request = $request;

        $this->submissionFileId = (int)$this->request->getUserVar('submissionFileId');
        $this->submissionFile = Services::get('submissionFile')->get($this->submissionFileId);

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

        // get files and copy file to working directory
        if (!$this->getAndCopyFilesToWorkingDir()) {
            $this->notificationManager->createTrivialNotification(
                $this->request->getUser(), NOTIFICATION_TYPE_ERROR,
                array('contents' => __('plugins.generic.latexConverter.notification.defaultErrorOccurred')));
            return $this->defaultResponse();
        }

        // construct pdf file name
        $this->pdfFile = str_replace('.' . LATEX_CONVERTER_TEX_EXTENSION,
            '.' . LATEX_CONVERTER_PDF_EXTENSION, $this->mainFileName);

        // construct log file name
        $this->logFile = str_replace('.' . LATEX_CONVERTER_TEX_EXTENSION,
            '.' . LATEX_CONVERTER_LOG_EXTENSION, $this->mainFileName);

        // do the conversion to pdf
        if (!$this->convertToPdf()) {
            $this->notificationManager->createTrivialNotification(
                $this->request->getUser(), NOTIFICATION_TYPE_ERROR,
                array('contents' => __('plugins.generic.latexConverter.notification.defaultErrorOccurred')));
            return $this->defaultResponse();
        }

        // add converted pdf to submission files
        $articleGalley = new ArticleGalley($this->request, $this->submissionId, $this->submissionFile,
            $this->workingDirAbsolutePath, $this->submissionFilesRelativeDir, $this->pdfFile,
            $this->dependentFileNames);
        if (!$articleGalley->addMainFile()) return $this->defaultResponse();

        // all went well, return ok
        return $this->defaultResponse(true);
    }

    /**
     * Convert LaTex file to pdf
     * @return bool
     */
    private function convertToPdf(): bool
    {

        $pdfLatexExe = $this->plugin->getSetting($this->request->getContext()->getId(),
            LATEX_CONVERTER_SETTING_KEY_PATH_EXECUTABLE);

        if (empty($pdfLatexExe)) {
            $this->notificationManager->createTrivialNotification(
                $this->request->getUser(), NOTIFICATION_TYPE_ERROR,
                array('contents' => __('plugins.generic.latexConverter.notification.defaultErrorOccurred')));
            return false;
        }

        $pdfLatexExe .= ' -no-shell-escape -interaction=nonstopmode';

        shell_exec("cd $this->workingDirAbsolutePath && $pdfLatexExe $this->mainFileName");

        return true;
    }

    /**
     * Get files of submission and copy to working dir
     * @return bool
     */
    private function getAndCopyFilesToWorkingDir(): bool
    {
        $files = Services::get('submissionFile')->getMany([
            'assocIds' => [$this->submissionId],
            'submissionIds' => [$this->submissionId],
            'includeDependentFiles' => true
        ]);

        foreach ($files as $file) {
            $fileName = $file->getData('name')[$file->getData('locale')];
            $filePath = $file->getData('path');

            if ($file->getData('id') == $this->submissionFileId)
                $this->mainFileName = $fileName;

            if ($file->getData('id') == $this->submissionFileId ||
                $file->getData('assocId') == $this->submissionFileId) {
                copy($this->ojsFilesAbsoluteBaseDir . DIRECTORY_SEPARATOR . $filePath,
                    $this->workingDirAbsolutePath . DIRECTORY_SEPARATOR . $fileName);
            }
        }

        if (empty($this->mainFileName)) return false;

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
