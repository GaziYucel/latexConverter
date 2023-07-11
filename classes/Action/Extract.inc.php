<?php
/**
 * @file plugins/generic/latexConverter/classes/Action/Extract.inc.php
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Extract
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Action Extract for the Handler
 *
 */

namespace TIBHannover\LatexConverter\Action;

import('lib.pkp.classes.file.PrivateFileManager');
import('lib.pkp.classes.submission.GenreDAO');
import('lib.pkp.classes.form.Form');

use Form;
use JSONMessage;
use NotificationManager;
use PrivateFileManager;
use Services;
use SubmissionDAO;
use TemplateManager;
use ZipArchive;
use TIBHannover\LatexConverter\Models\ArticleSubmissionFile;
use TIBHannover\LatexConverter\Models\Cleanup;

class Extract extends Form
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
     * Absolute path to the archive file
     * e.g. /var/www/ojs_files/journals/1/articles/51/648b243110d7e.zip
     * @var string
     */
    protected string $archiveAbsoluteFilePath;

    /**
     * Absolute path to the directory with the extracted content of archive
     * e.g. /var/tmp/648b243110d7e_zip_extracted
     * @var string
     */
    protected string $archiveExtractedAbsoluteDirPath;

    /**
     * Path to directory for files of this submission
     * e.g. journals/1/articles/51
     * @var string
     */
    protected string $submissionFilesRelativeDir;

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
     * Name used for id in form
     * @var string
     */
    protected string $latexConverterSelectedFilenameKey = 'latexConverter_SelectedFilename';

    function __construct($plugin, $request, $params)
    {
        $this->timeStamp = date('Ymd_His');

        $this->plugin = $plugin;

        $this->fileManager = new PrivateFileManager();

        $this->notificationManager = new NotificationManager();

        $this->request = $request;

        $this->submissionFileId = (int)$this->request->getUserVar('submissionFileId');
        $this->submissionFile = Services::get('submissionFile')->get($this->submissionFileId);

        $submissionDao = new SubmissionDAO();
        $this->submissionId = (int)$this->submissionFile->getData('submissionId');
        $this->submission = $submissionDao->getById($this->submissionId);

        $this->archiveAbsoluteFilePath = $this->fileManager->getBasePath() . DIRECTORY_SEPARATOR .
            $this->submissionFile->getData('path');

        $this->archiveExtractedAbsoluteDirPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR .
            LATEX_CONVERTER_PLUGIN_NAME . '_' . $this->timeStamp . '_' . uniqid();
        // tempnam(sys_get_temp_dir(), LATEX_CONVERTER_PLUGIN_NAME . '_') . '_' . $this->timeStamp;

        error_log($this->archiveExtractedAbsoluteDirPath);

        $this->submissionFilesRelativeDir = Services::get('submissionFile')->getSubmissionDir(
            $this->submission->getData('contextId'), $this->submissionId);

        parent::__construct($plugin->getTemplateResource('extract.tpl'));
    }

    /**
     * Display the form.
     * @param $request
     * @param $template
     * @param bool $display
     * @return string
     */
    function fetch($request, $template = null, $display = false): string
    {
        $templateMgr = TemplateManager::getManager($request);

        $templateMgr->assign([
            'latexConverterSelectedFilenameKey' => $this->latexConverterSelectedFilenameKey,
            'filenames' => $this->getZipContentTexFilesFirst(),
            'submissionId' => $this->submissionId,
            'stageId' => $request->getUserVar('stageId'),
            'fileStage' => $request->getUserVar('fileStage'),
            'submissionFileId' => $this->submissionFileId,
            'archiveType' => $this->request->getUserVar("archiveType")
        ]);

        return parent::fetch($request, $template, $display);
    }

    /**
     * Assign form data to user-submitted data.
     */
    function readInputData(): void
    {
        $this->readUserVars([$this->latexConverterSelectedFilenameKey]);
    }

    /**
     * Execute the forms action
     * @param ...$functionArgs
     */
    public function execute(...$functionArgs): mixed
    {
        $this->mainFileName = $this->getData($this->latexConverterSelectedFilenameKey);

        // no main file found, notify and return
        if (empty($this->mainFileName)) {
            $this->notificationManager->createTrivialNotification(
                $this->request->getUser(), NOTIFICATION_TYPE_ERROR,
                array('contents' => __('plugins.generic.latexConverter.notification.noFileSelected')));
            return $this->defaultResponse();
        }

        // extract zip, return if false
        if (!$this->extractZip()) return $this->defaultResponse();

        // iterate through archive content: list and decide what to do
        if (!$this->processArchiveContent()) return $this->defaultResponse();

        // add main file
        $articleSubmissionFile = new ArticleSubmissionFile($this->request, $this->submissionId, $this->submissionFile,
            $this->archiveExtractedAbsoluteDirPath, $this->submissionFilesRelativeDir, $this->mainFileName,
            $this->dependentFileNames);

        if (!empty($this->mainFileName))
            if (!$articleSubmissionFile->addMainFile()) return $this->defaultResponse();

        // add dependent files
        if (!empty($this->dependentFileNames))
            if (!$articleSubmissionFile->addDependentFiles()) return $this->defaultResponse();

        // all went well, return ok
        return $this->defaultResponse(true);
    }

    /**
     * Get list of filenames of zip file
     * @return array
     */
    private function getZipContentTexFilesFirst(): array
    {
        $texFiles = [];

        $otherFiles = [];

        $zip = new ZipArchive();

        $zip->open($this->archiveAbsoluteFilePath);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $fileName = basename($stat['name']);
            if (in_array(pathinfo($fileName, PATHINFO_EXTENSION), LATEX_CONVERTER_TEX_EXTENSIONS)) {
                $texFiles[] = $fileName;
            } elseif (!empty(pathinfo($fileName, PATHINFO_EXTENSION))) {
                $otherFiles[] = $fileName;
            }
        }

        $zip->close();

        return array_merge($texFiles, $otherFiles);
    }

    /**
     * Extract zip file
     * @return bool
     */
    private function extractZip(): bool
    {
        // check archive type, if not zip return false
        if ($this->request->getUserVar("archiveType") !== LATEX_CONVERTER_ZIP_FILE_TYPE) {
            $this->notificationManager->createTrivialNotification(
                $this->request->getUser(), NOTIFICATION_TYPE_ERROR,
                array('contents' => __('plugins.generic.latexConverter.notification.noValidZipFile')));

            return false;
        }

        $zip = new ZipArchive();

        if (!$zip->open($this->archiveAbsoluteFilePath)) {
            $this->notificationManager->createTrivialNotification(
                $this->request->getUser(), NOTIFICATION_TYPE_ERROR,
                array('contents' => __('plugins.generic.latexConverter.notification.errorOpeningFile')));
            return false;
        }

        if (!mkdir($this->archiveExtractedAbsoluteDirPath, 0777, true)) {
            return false;
        }

        $zip->extractTo($this->archiveExtractedAbsoluteDirPath);

        $zip->close();

        return true;
    }

    /**
     * Iterate through directory and get found files
     *     - $this->mainFile holds the main tex file
     *     - $this->dependentFiles is an array of all other files
     * @return bool
     */
    private function processArchiveContent(): bool
    {
        $archiveContent = array_diff(scandir($this->archiveExtractedAbsoluteDirPath), ['..', '.']);

        foreach ($archiveContent as $index => $fileName) {
            if($fileName !== $this->mainFileName){
                $this->dependentFileNames[] = $fileName;
            }
        }

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
        if (file_exists($this->archiveExtractedAbsoluteDirPath)) {
            $cleanup = new Cleanup();
            $cleanup->removeDirectoryAndContentsRecursively($this->archiveExtractedAbsoluteDirPath);
        }
    }
}
