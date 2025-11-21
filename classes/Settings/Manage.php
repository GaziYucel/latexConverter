<?php

/**
 * @file classes/Settings/Manage.php
 *
 * @copyright (c) 2021-2025 TIB Hannover
 * @copyright (c) 2021-2025 Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Manage
 *
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Manage settings page
 */

namespace APP\plugins\generic\latexConverter\classes\Settings;

use APP\plugins\generic\latexConverter\LatexConverterPlugin;
use PKP\core\JSONMessage;

class Manage
{
    public LatexConverterPlugin $plugin;

    public function __construct(LatexConverterPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /** @copydoc Plugin::manage() */
    public function execute($args, $request): JSONMessage
    {
        switch ($request->getUserVar('verb')) {
            case 'settings':
                // Load the custom form
                $form = new SettingsForm($this->plugin);

                // Fetch the form the first time it loads, before the user has tried to save it
                if (!$request->getUserVar('save')) {
                    $form->initData();
                    return new JSONMessage(true, $form->fetch($request));
                }

                // Validate and save the form data
                $form->readInputData();
                if ($form->validate()) {
                    $form->execute();
                    return new JSONMessage(true);
                }
                break;
            default:
                break;
        }

        return new JSONMessage(false);
    }
}
