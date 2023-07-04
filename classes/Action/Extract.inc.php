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
 * Do the following depending on tex files found:
 *   count = 0 : do nothing
 *   count = 1 : make main file and others dependent files
 *   count > 1 : make 'main.tex' mail file others dependent files
 *   count > 1 : if no 'main.tex' found, do nothing
 *
 */

namespace TIBHannover\LatexConverter\Action;

import('lib.pkp.classes.file.PrivateFileManager');
import('lib.pkp.classes.submission.GenreDAO');

use JSONMessage;
use NotificationManager;
use PrivateFileManager;
use Services;
use SubmissionDAO;
use ZipArchive;
use TIBHannover\LatexConverter\Models\ArticleGalley;
use TIBHannover\LatexConverter\Models\Cleanup;

class Extract
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
     * This is the newly inserted main file object
     * @var object SubmissionFile
     */
    protected object $insertedNewSubmissionFile;

    /**
     * This array is a list of SubmissionFile objects
     * @var array [ SubmissionFile, ... ]
     */
    protected array $insertedNewDependentSubmissionFiles = [];

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

        $this->archiveExtractedAbsoluteDirPath =
            tempnam(sys_get_temp_dir(), LATEX_CONVERTER_PLUGIN_NAME . '_') . '_' . $this->timeStamp;

        $this->submissionFilesRelativeDir = Services::get('submissionFile')->getSubmissionDir(
            $this->submission->getData('contextId'), $this->submissionId);
    }

    /**
     * Main entry point
     * @return JSONMessage
     */
    public function execute(): JSONMessage
    {
        // extract zip, return if false
        if (!$this->extractZip()) return $this->defaultResponse();

        // iterate through archive content: list and decide what to do
        if (!$this->processArchiveContent()) return $this->defaultResponse();

        // add main file
        $articleGalley = new ArticleGalley($this->request, $this->submissionId, $this->submissionFile,
            $this->archiveExtractedAbsoluteDirPath, $this->submissionFilesRelativeDir, $this->mainFileName,
            $this->dependentFileNames);

        if (!empty($this->mainFileName))
            if (!$articleGalley->addMainFile()) return $this->defaultResponse();

        // add dependent files
        if (!empty($this->dependentFileNames))
            if (!$articleGalley->addDependentFiles()) return $this->defaultResponse();

        // all went well, return ok
        return $this->defaultResponse(true);
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

        if(!mkdir($this->archiveExtractedAbsoluteDirPath, 0777, true)){
		return  false;
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
        $texFiles = [];

        $archiveContent = array_diff(scandir($this->archiveExtractedAbsoluteDirPath), ['..', '.']);

        foreach ($archiveContent as $index => $fileName) {
            if (in_array(pathinfo($fileName, PATHINFO_EXTENSION), LATEX_CONVERTER_TEX_EXTENSIONS)) {
                $texFiles[] = $fileName;
            } elseif (!empty(pathinfo($fileName, PATHINFO_EXTENSION))) {
                $this->dependentFileNames[] = $fileName;
            }
        }

        // decide what to do according to tex files found
        if (count($texFiles) === 0) {
            $this->notificationManager->createTrivialNotification(
                $this->request->getUser(), NOTIFICATION_TYPE_ERROR,
                array('contents' => __('plugins.generic.latexConverter.notification.noTexFileFound')));
            return false;
        } else {
            foreach ($texFiles as $fileName) {
                if (pathinfo($fileName, PATHINFO_BASENAME) === LATEX_CONVERTER_MAIN_FILENAME) {
                    $this->mainFileName = $fileName;
                } else {
                    $this->dependentFileNames[] = $fileName;
                }
            }

            // no main file found, notify and return 
            if (empty($this->mainFileName)) {
                $this->notificationManager->createTrivialNotification(
                    $this->request->getUser(), NOTIFICATION_TYPE_ERROR,
                    array('contents' => __('plugins.generic.latexConverter.notification.multipleTexFilesFound',
                        ['value' => LATEX_CONVERTER_MAIN_FILENAME])));
                return false;
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
