<?php

/**
 * @file plugins/generic/latexConverter/classes/Constants.php
 *
 * @copyright (c) 2021-2025 TIB Hannover
 * @copyright (c) 2021-2025 Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Constants
 *
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Constants
 */

namespace APP\plugins\generic\latexConverter\classes;

use PKP\security\Role;

class Constants
{
    public const string ZIP_FILE_TYPE = 'application/zip';

    public const string TEX_FILE_TYPE = 'text/x-tex';

    public const string TEX_EXTENSION = 'tex';

    public const string PDF_EXTENSION = 'pdf';

    public const string LOG_EXTENSION = 'log';

    public const string  SETTING_LATEX_PATH_EXECUTABLE = 'LatexConverter_PathToExecutable';

    public const string SETTING_AUTHORISED_MIME_TYPES = 'LatexConverter_AuthorisedMimeTypes';

    public const array EXTENSIONS = [
        'tex' => ['tex'],
        'pdf' => ['pdf'],
        'log' => ['log'],
        'text' => ['txt'],
        'image' => ['gif', 'jpg', 'jpeg', 'png', 'jpe'],
        'html' => ['htm', 'html'],
        'style' => ['css']
    ];

    public const string TEX_MAIN_FILENAME = 'main.' . self::TEX_EXTENSION;

    public const array AUTHORISED_ROLES = [
        Role::ROLE_ID_SITE_ADMIN,
        Role::ROLE_ID_MANAGER,
        Role::ROLE_ID_SUB_EDITOR,
        Role::ROLE_ID_ASSISTANT
    ];
}
