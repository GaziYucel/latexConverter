<?php

/**
 * @file plugins/generic/latexConverter/classes/Models/SubmissionFileHelper.php
 *
 * Copyright (c) 2021-2025 TIB Hannover
 * Copyright (c) 2021-2025 Gazi Yücel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFileHelper
 *
 * @ingroup plugins_generic_latexconverter
 *
 * @brief SubmissionFileHelper methods
 */

namespace APP\plugins\generic\latexConverter\classes\Helpers;

use APP\core\Services;
use APP\facades\Repo;
use APP\plugins\generic\latexConverter\classes\Constants;
use Exception;
use PKP\core\PKPApplication;
use PKP\submissionFile\SubmissionFile;

class SubmissionFileHelper
{
    protected int $submissionId;
    protected SubmissionFile $originalSubmissionFile;

    /**
     * This is the newly inserted main file object.
     */
    protected int $newSubmissionFileId;

    /**
     * This array is a list of SubmissionFile objects.
     * e.g. [ SubmissionFile, ... ]
     */
    protected array $newDependentSubmissionFiles = [];

    /**
     * Absolute path to the directory with the extracted content of archive.
     * e.g. c:/ojs_files/journals/1/articles/51/648b243110d7e_zip_extracted
     */
    protected string $workingDirPath;

    /**
     * Path to directory for files of this submission.
     * e.g. journals/1/articles/51
     */
    protected string $submissionFilesRelativeDir;

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

    public function __construct(
        SubmissionFile $originalSubmissionFile,
        string         $mainFileName,
        array          $dependentFiles,
        string         $workingDirPath)
    {
        $this->originalSubmissionFile = $originalSubmissionFile;
        $this->mainFileName = $mainFileName;
        $this->dependentFileNames = $dependentFiles;
        $this->workingDirPath = $workingDirPath;

        $this->submissionId = (int)$originalSubmissionFile->getData('submissionId');
        $this->submissionFilesRelativeDir = Repo::submissionFile()->getSubmissionDir(
            Repo::submission()->get($this->submissionId)->getData('contextId'),
            $this->submissionId
        );
    }

    /**
     * Add the main file.
     */
    public function addMainFile(): bool
    {
        $newFileExtension = pathinfo($this->mainFileName, PATHINFO_EXTENSION);
        $newFileNameReal = uniqid() . '.' . $newFileExtension;
        $newFileNameDisplay = [];
        foreach ($this->originalSubmissionFile->getData('name') as $localeKey => $name) {
            $newFileNameDisplay[$localeKey] = $name ? pathinfo($name)['filename'] . '.' . $newFileExtension : null;
        }

        // add file to file system
        $newFileId = Services::get('file')->add(
            $this->workingDirPath . DIRECTORY_SEPARATOR . $this->mainFileName,
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
        try {
            $this->newSubmissionFileId = Repo::submissionFile()->add($newFileObject);
            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Add dependent files.
     */
    public function addDependentFiles(): bool
    {
        $success = true;

        foreach ($this->dependentFileNames as $fileName) {
            $newFileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileNameReal = uniqid() . '.' . $newFileExtension;
            $newFileNameDisplay = [];
            foreach ($this->originalSubmissionFile->getData('name') as $localeKey => $name) {
                $newFileNameDisplay[$localeKey] = $name ? $fileName : null;
            }

            // add file to file system
            $newFileId = Services::get('file')->add(
                $this->workingDirPath . DIRECTORY_SEPARATOR . $fileName,
                $this->submissionFilesRelativeDir . DIRECTORY_SEPARATOR . $newFileNameReal);

            // determine genre (see table genres and genre_settings)
            $newFileGenreId = 12; // OTHER
            if (in_array(pathinfo($fileName, PATHINFO_EXTENSION), Constants::EXTENSIONS['image'])) {
                $newFileGenreId = 10; // IMAGE
            } elseif (in_array(pathinfo($fileName, PATHINFO_EXTENSION), Constants::EXTENSIONS['style'])) {
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

            try {
                $this->newDependentSubmissionFiles[] = Repo::submissionFile()->add($newFileObject);
            } catch (Exception) {
                $success = false;
            }
        }

        return $success;
    }
}
