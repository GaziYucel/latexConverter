<?php
/**
 * @file plugins/generic/latexConverter/classes/Action/Extract.php
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yücel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Extract
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Action Extract for the Handler
 *
 */

namespace APP\plugins\generic\latexConverter\classes\Workflow;

import('lib.pkp.classes.file.PrivateFileManager');
import('lib.pkp.classes.submission.GenreDAO');
import('lib.pkp.classes.form.Form');

use APP\plugins\generic\latexConverter\classes\Constants;
use APP\plugins\generic\latexConverter\classes\Helpers\ZipHelper;
use Exception;
use Form;
use JSONMessage;
use LatexConverterPlugin;
use NotificationManager;
use PKPRequest;
use PrivateFileManager;
use Services;
use SubmissionDAO;
use TemplateManager;
use APP\plugins\generic\latexConverter\classes\Helpers\SubmissionFileHelper;
use APP\plugins\generic\latexConverter\classes\Helpers\FileSystemHelper;

class Extract extends Form
{
    /** @var LatexConverterPlugin */
    protected LatexConverterPlugin $plugin;

    /** @var PrivateFileManager */
    protected PrivateFileManager $fileManager;

    /** @var NotificationManager */
    protected NotificationManager $notificationManager;

    /** @var mixed Request */
    protected mixed $request;

    /** @var object Submission */
    protected object $submission;

    /** @var string */
    protected string $timeStamp;

    /** @var int */
    protected int $submissionId;

    /** @var object SubmissionFile */
    protected object $submissionFile;

    /** @var int */
    protected int $submissionFileId;

    /**
     * Absolute path to the archive file, e.g. /var/www/ojs_files/journals/1/articles/51/648b243110d7e.zip
     *
     * @var string
     */
    protected string $archiveFileAbsolutePath;

    /**
     * Absolute path to the directory with the extracted content of archive, e.g. /var/tmp/648b243110d7e_zip_extracted
     *
     * @var string
     */
    protected string $workingDirAbsolutePath;

    /**
     * Path to directory for files of this submission, e.g. journals/1/articles/51
     *
     * @var string
     */
    protected string $submissionFilesRelativeDir;

    /**
     * The name of the main tex file, e.g. main.tex
     *
     * @var string
     */
    protected string $mainFileName = '';

    /**
     * The names of the dependent files, e.g. [ 'image1.png', ... ]
     *
     * @var string[]
     */
    protected array $dependentFileNames = [];

    /**
     * Name used for id in form
     *
     * @var string
     */
    protected string $latexConverterSelectedFilenameKey = 'latexConverter_SelectedFilename';

    function __construct(LatexConverterPlugin $plugin, PKPRequest $request, $args)
    {
        $this->timeStamp = date('Ymd_His');

        $this->plugin = $plugin;

        $this->fileManager = new PrivateFileManager();

        $this->notificationManager = new NotificationManager();

        $this->request = $request;

        $this->submissionFileId = (int)$this->request->getUserVar('submissionFileId');
        $this->submissionFile = Services::get('submissionFile')->get($this->submissionFileId);

        $this->submissionId = (int)$this->submissionFile->getData('submissionId');
        $submissionDao = new SubmissionDAO();
        $this->submission = $submissionDao->getById($this->submissionId);

        $this->archiveFileAbsolutePath = $this->fileManager->getBasePath() . DIRECTORY_SEPARATOR .
            $this->submissionFile->getData('path');

        $this->workingDirAbsolutePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR .
            LATEX_CONVERTER_PLUGIN_NAME . '_' . $this->timeStamp . '_' . uniqid();

        $this->submissionFilesRelativeDir = Services::get('submissionFile')
            ->getSubmissionDir($this->submission->getData('contextId'), $this->submissionId);

        parent::__construct($this->plugin->getTemplateResource('extract.tpl'));
    }

    /**
     * Display the form.
     *
     * @param $request
     * @param $template
     * @param bool $display
     * @return string
     */
    function fetch($request, $template = null, $display = false): string
    {
        try {
            $templateMgr = TemplateManager::getManager($request);

            $templateMgr->assign([
                'latexConverterSelectedFilenameKey' => $this->latexConverterSelectedFilenameKey,
                'filenames' => ZipHelper::getZipContentTexFilesFirst($this->archiveFileAbsolutePath),
                'submissionId' => $this->submissionId,
                'stageId' => $request->getUserVar('stageId'),
                'fileStage' => $request->getUserVar('fileStage'),
                'submissionFileId' => $this->submissionFileId,
                'archiveType' => $this->request->getUserVar("archiveType")
            ]);

        } catch (Exception $ex) {
            error_log($ex->getMessage());
        }

        return parent::fetch($request, $template, $display);
    }

    /**
     * Assign form data to user-submitted data.
     *
     * @return void
     */
    function readInputData(): void
    {
        $this->readUserVars([$this->latexConverterSelectedFilenameKey]);
    }

    /**
     * Process after selecting main file
     *
     * @return JSONMessage
     */
    public function process(): JSONMessage
    {
        $this->mainFileName = $this->getData($this->latexConverterSelectedFilenameKey);

        // no selected file found, notify and return
        if (empty($this->mainFileName)) {
            $this->notificationManager->createTrivialNotification(
                $this->request->getUser()->getId(),
                NOTIFICATION_TYPE_ERROR,
                array('contents' => __('plugins.generic.latexConverter.notification.noFileSelected')));
            return $this->defaultResponse();
        }

        // check archive type, if not zip return false
        if ($this->request->getUserVar("archiveType") !== Constants::zipFileType) {
            $this->notificationManager
                ->createTrivialNotification(
                    $this->request->getUser()->getId(),
                    NOTIFICATION_TYPE_ERROR,
                    array('contents' => __('plugins.generic.latexConverter.notification.noValidZipFile'))
                );

            return $this->defaultResponse();
        }

        // extract zip, return if false
        if (!ZipHelper::extractZip($this->archiveFileAbsolutePath, $this->workingDirAbsolutePath)) {
            $this->notificationManager->createTrivialNotification(
                $this->request->getUser()->getId(),
                NOTIFICATION_TYPE_ERROR,
                array('contents' => __('plugins.generic.latexConverter.notification.errorOpeningFile')));

            return $this->defaultResponse();
        }

        $relativeZipRoot = ZipHelper::getRelativeZipRoot($this->archiveFileAbsolutePath);

        // get all dependent files
        $allFiles = FileSystemHelper::getDirectoryFilesRecursively(
            $this->workingDirAbsolutePath . DIRECTORY_SEPARATOR . $relativeZipRoot);

        for ($i = 0; $i < count($allFiles); $i++) {
            $filePath = $this->workingDirAbsolutePath . DIRECTORY_SEPARATOR .
                $relativeZipRoot . DIRECTORY_SEPARATOR . $allFiles[$i];
            $fileName = str_replace(
                $this->workingDirAbsolutePath . DIRECTORY_SEPARATOR . $relativeZipRoot,
                '',
                $allFiles[$i]
            );

            if ($fileName !== $this->mainFileName) {
                $this->dependentFileNames[] = $fileName;
            }
        }

        // add main file
        $submissionFileHelper =
            new SubmissionFileHelper(
                $this->request,
                $this->submissionId,
                $this->submissionFile,
                $this->workingDirAbsolutePath . DIRECTORY_SEPARATOR . $relativeZipRoot,
                $this->submissionFilesRelativeDir,
                $this->mainFileName,
                $this->dependentFileNames);

        if (!empty($this->mainFileName))
            if (!$submissionFileHelper->addMainFile()) return $this->defaultResponse();

        // add dependent files
        if (!empty($this->dependentFileNames))
            if (!$submissionFileHelper->addDependentFiles()) return $this->defaultResponse();

        // all went well, return ok
        return $this->defaultResponse(true);
    }

    /**
     * Default response, only submissionId is returned as a JSONMessage
     *
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
            FileSystemHelper::removeDirectoryAndContentsRecursively($this->workingDirAbsolutePath);
        }
    }
}
