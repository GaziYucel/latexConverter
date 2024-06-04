<?php
/**
 * @file classes/Constants.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Constants
 * @brief Constants
 */

namespace APP\plugins\generic\latexConverter\classes;

use Role;

class Constants
{
    public const ZIP_FILE_TYPE = 'application/zip';

    public const TEX_FILE_TYPE = 'text/x-tex';

    public const TEX_EXTENSION = 'tex';

    public const PDF_EXTENSION = 'pdf';

    public const LOG_EXTENSION = 'log';

    public const SETTING_LATEX_PATH_EXECUTABLE = 'LatexConverter_PathToExecutable';

    public const SETTING_AUTHORISED_MIME_TYPES = 'LatexConverter_AuthorisedMimeTypes';

    public const EXTENSIONS = [
        'tex' => ['tex'],
        'pdf' => ['pdf'],
        'log' => ['log'],
        'text' => ['txt'],
        'image' => ['gif', 'jpg', 'jpeg', 'png', 'jpe'],
        'html' => ['htm', 'html'],
        'style' => ['css']
    ];

    public const TEX_MAIN_FILENAME = 'main.' . self::TEX_EXTENSION;

    public const AUTHORISED_ROLES = [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT];
}
