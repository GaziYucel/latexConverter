<?php

/**
 * @file classes/Settings/MimeTypes.php
 *
 * @copyright (c) 2021-2025 TIB Hannover
 * @copyright (c) 2021-2025 Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Production
 *
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Production
 */

namespace APP\plugins\generic\latexConverter\classes\Settings;

use APP\plugins\generic\latexConverter\classes\Constants;
use APP\plugins\generic\latexConverter\LatexConverterPlugin;
use PKP\submissionFile\SubmissionFile;

class MimeTypes
{
    public LatexConverterPlugin $plugin;

    public function __construct(LatexConverterPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Add mimetypes which support dependent files.
     */
    public function execute(string $hookName, array $args): void
    {
        $result = &$args[0];
        $submissionFile = $args[1];

        $fileStage = $submissionFile->getData('fileStage');
        $excludedFileStages = [
            SubmissionFile::SUBMISSION_FILE_DEPENDENT,
            SubmissionFile::SUBMISSION_FILE_QUERY,
        ];

        $allowedMimetypes = ['text/x-tex', 'application/x-tex'];
        $allowedMimetypesDb = $this->plugin->getSetting($this->plugin->getCurrentContextId(),
            Constants::SETTING_AUTHORISED_MIME_TYPES);
        if (!empty($allowedMimetypesDb))
            $allowedMimetypes = array_filter(preg_split("/\r\n|\n|\r/", $allowedMimetypesDb));

        $result =
            !in_array($fileStage, $excludedFileStages) &&
            in_array($submissionFile->getData('mimetype'), $allowedMimetypes);
    }
}
