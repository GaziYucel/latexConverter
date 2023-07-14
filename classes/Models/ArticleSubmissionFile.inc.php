<?php
/**
 * @file plugins/generic/latexConverter/classes/Models/ArticleSubmissionFile.inc.php
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleSubmissionFile
 * @ingroup plugins_generic_latexconverter
 *
 * @brief ArticleSubmissionFile methods
 */

namespace TIBHannover\LatexConverter\Models;

use NotificationManager;
use Services;
use SubmissionFileDAO;

class ArticleSubmissionFile
{
    /**
     * @var NotificationManager
     */
    protected NotificationManager $notificationManager;

    /**
     * @var mixed Request
     */
    protected mixed $request;

    /**
     * @var int
     */
    protected int $submissionId;

    /**
     * @var object SubmissionFile
     */
    protected object $originalSubmissionFile;

    /**
     * This is the newly inserted main file object
     * @var object SubmissionFile
     */
    protected object $newSubmissionFile;

    /**
     * This array is a list of SubmissionFile objects
     * @var array [ SubmissionFile, ... ]
     */
    protected array $newDependentSubmissionFiles = [];

    /**
     * Absolute path to the directory with the extracted content of archive
     * e.g. c:/ojs_files/journals/1/articles/51/648b243110d7e_zip_extracted
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

    public function __construct($request, $submissionId, $originalSubmissionFile, $workingDirAbsolutePath,
                                $submissionFilesRelativeDir, $mainFileName, $dependentFiles)
    {
        $this->notificationManager = new NotificationManager();

        $this->request = $request;
        $this->submissionId = $submissionId;
        $this->originalSubmissionFile = $originalSubmissionFile;
        $this->mainFileName  = $mainFileName;
        $this->dependentFileNames = $dependentFiles;

        $this->submissionFilesRelativeDir = $submissionFilesRelativeDir;
        $this->workingDirAbsolutePath = $workingDirAbsolutePath;
    }

    /**
     * Add the main file
     * @return bool
     */
    public function addMainFile(): bool
    {
        $newFileExtension = pathinfo($this->mainFileName, PATHINFO_EXTENSION);
        $newFileNameReal = uniqid() . '.' . $newFileExtension;
        $newFileNameDisplay = [];
        foreach ($this->originalSubmissionFile->getData('name') as $localeKey => $name) {
            $newFileNameDisplay[$localeKey] = pathinfo($name)['filename'] . '.' . $newFileExtension;
        }

        // add file to file system
        $newFileId = Services::get('file')->add(
            $this->workingDirAbsolutePath . DIRECTORY_SEPARATOR . $this->mainFileName,
            $this->submissionFilesRelativeDir . DIRECTORY_SEPARATOR . $newFileNameReal);

        // add file link to database
        $newFileParams = [
            'fileId' => $newFileId,
            'assocId' => $this->originalSubmissionFile->getData('assocId'),
            'assocType' => $this->originalSubmissionFile->getData('assocType'),
            'fileStage' => $this->originalSubmissionFile->getData('fileStage'),
            'mimetype' => LATEX_CONVERTER_LATEX_FILE_TYPE,
            'locale' => $this->originalSubmissionFile->getData('locale'),
            'genreId' => $this->originalSubmissionFile->getData('genreId'),
            'name' => $newFileNameDisplay,
            'submissionId' => $this->submissionId
        ];
        $submissionFileDao = new SubmissionFileDAO();
        $newFileObject = $submissionFileDao->newDataObject();
        $newFileObject->setAllData($newFileParams);
        $this->newSubmissionFile = Services::get('submissionFile')->add($newFileObject, $this->request);

        if (empty($this->newSubmissionFile)) {
            $this->notificationManager->createTrivialNotification(
                $this->request->getUser(), NOTIFICATION_TYPE_ERROR,
                array('contents' => __('plugins.generic.latexConverter.notification.defaultErrorOccurred')));
            return false;
        }

        return true;
    }

    /**
     * Add dependent files
     * @return bool
     */
    public function addDependentFiles(): bool
    {
        foreach ($this->dependentFileNames as $fileName) {
            $newFileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileNameReal = uniqid() . '.' . $newFileExtension;
            $newFileNameDisplay = [];
            foreach ($this->originalSubmissionFile->getData('name') as $localeKey => $name) {
                $newFileNameDisplay[$localeKey] = pathinfo($fileName, PATHINFO_BASENAME);
            }

            // add file to file system
            $newFileId = Services::get('file')->add(
                $this->workingDirAbsolutePath . DIRECTORY_SEPARATOR . $fileName,
                $this->submissionFilesRelativeDir . DIRECTORY_SEPARATOR . $newFileNameReal);

            // determine genre (see table genres and genre_settings)
            $newFileGenreId = 12; // OTHER
            if (in_array(pathinfo($fileName, PATHINFO_EXTENSION),
                LATEX_CONVERTER_IMAGE_EXTENSIONS)) {
                $newFileGenreId = 10; // IMAGE
            } elseif (in_array(pathinfo($fileName, PATHINFO_EXTENSION),
                LATEX_CONVERTER_STYLE_EXTENSIONS)) {
                $newFileGenreId = 11; // STYLE
            }

            // add file link to database
            $newFileParams = [
                'fileId' => $newFileId,
                'assocId' => $this->newSubmissionFile->getId(),
                'assocType' => ASSOC_TYPE_SUBMISSION_FILE,
                'fileStage' => SUBMISSION_FILE_DEPENDENT,
                'submissionId' => $this->submissionId,
                'genreId' => $newFileGenreId,
                'name' => $newFileNameDisplay
            ];
            $submissionFileDao = new SubmissionFileDAO();
            $newFileObject = $submissionFileDao->newDataObject();
            $newFileObject->setAllData($newFileParams);
            $this->newDependentSubmissionFiles[] = Services::get('submissionFile')->add($newFileObject, $this->request);
        }

        return true;
    }

}