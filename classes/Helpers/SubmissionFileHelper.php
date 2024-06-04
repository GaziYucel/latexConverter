<?php
/**
 * @file plugins/generic/latexConverter/classes/Models/SubmissionFileHelper.php
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yücel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFileHelper
 * @ingroup plugins_generic_latexconverter
 *
 * @brief SubmissionFileHelper methods
 */

namespace APP\plugins\generic\latexConverter\classes\Helpers;

use APP\core\Services;
use APP\facades\Repo;
use APP\notification\NotificationManager;
use APP\plugins\generic\latexConverter\classes\Constants;
use Exception;
use PKP\core\PKPApplication;
use PKP\core\PKPRequest;
use PKP\notification\PKPNotification;
use PKP\submissionFile\SubmissionFile;

class SubmissionFileHelper
{
    /**
     * @var NotificationManager
     */
    protected NotificationManager $notificationManager;

    /**
     * @var PKPRequest
     */
    protected PKPRequest $request;

    /**
     * @var int
     */
    protected int $submissionId;

    /**
     * @var SubmissionFile
     */
    protected SubmissionFile $originalSubmissionFile;

    /**
     * This is the newly inserted main file object
     *
     * @var int
     */
    protected int $newSubmissionFileId;

    /**
     * This array is a list of SubmissionFile objects
     *
     * @var array [ SubmissionFile, ... ]
     */
    protected array $newDependentSubmissionFiles = [];

    /**
     * Absolute path to the directory with the extracted content of archive
     * e.g. c:/ojs_files/journals/1/articles/51/648b243110d7e_zip_extracted
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

    public function __construct($request, $submissionId, $originalSubmissionFile, $workingDirAbsolutePath,
                                $submissionFilesRelativeDir, $mainFileName, $dependentFiles)
    {
        $this->notificationManager = new NotificationManager();

        $this->request = $request;
        $this->submissionId = $submissionId;
        $this->originalSubmissionFile = $originalSubmissionFile;
        $this->mainFileName = $mainFileName;
        $this->dependentFileNames = $dependentFiles;

        $this->submissionFilesRelativeDir = $submissionFilesRelativeDir;
        $this->workingDirAbsolutePath = $workingDirAbsolutePath;
    }

    /**
     * Add the main file
     *
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
            'mimetype' => Constants::TEX_FILE_TYPE,
            'locale' => $this->originalSubmissionFile->getData('locale'),
            'genreId' => $this->originalSubmissionFile->getData('genreId'),
            'name' => $newFileNameDisplay,
            'submissionId' => $this->submissionId
        ];
        $newFileObject = Repo::submissionFile()->newDataObject($newFileParams);
        $this->newSubmissionFileId = Repo::submissionFile()->add($newFileObject);

        if (empty($this->newSubmissionFileId)) {
            $this->notificationManager->createTrivialNotification(
                $this->request->getUser()->getId(),
                PKPNotification::NOTIFICATION_TYPE_ERROR,
                array('contents' => __('plugins.generic.latexConverter.notification.defaultErrorOccurred'))
            );
            return false;
        }

        return true;
    }

    /**
     * Add dependent files
     *
     * @return bool
     */
    public function addDependentFiles(): bool
    {
        foreach ($this->dependentFileNames as $fileName) {
            $newFileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileNameReal = uniqid() . '.' . $newFileExtension;

            $newFileNameDisplay = [];
            foreach ($this->originalSubmissionFile->getData('name') as $localeKey => $name) {
                $newFileNameDisplay[$localeKey] = $fileName;
            }

            // add file to file system
            $newFileId = Services::get('file')->add(
                $this->workingDirAbsolutePath . DIRECTORY_SEPARATOR . $fileName,
                $this->submissionFilesRelativeDir . DIRECTORY_SEPARATOR . $newFileNameReal);

            // determine genre (see table genres and genre_settings)
            $newFileGenreId = 12; // OTHER
            if (in_array(
                pathinfo($fileName, PATHINFO_EXTENSION),
                Constants::EXTENSIONS['image'])
            ) {
                $newFileGenreId = 10; // IMAGE
            } elseif (in_array(
                pathinfo($fileName, PATHINFO_EXTENSION),
                Constants::EXTENSIONS['style'])
            ) {
                $newFileGenreId = 11; // STYLE
            }

            // add file link to database
            $newFileParams = [
                'fileId' => $newFileId,
                'assocId' => $this->newSubmissionFileId,
                'assocType' => PKPApplication::ASSOC_TYPE_SUBMISSION_FILE,
                'fileStage' => SubmissionFile::SUBMISSION_FILE_DEPENDENT,
                'submissionId' => $this->submissionId,
                'genreId' => $newFileGenreId,
                'name' => $newFileNameDisplay
            ];
            $newFileObject = Repo::submissionFile()->newDataObject($newFileParams);
            $this->newDependentSubmissionFiles[] = Repo::submissionFile()->add($newFileObject);
        }

        return true;
    }
}

