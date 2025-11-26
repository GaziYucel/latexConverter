<?php

/**
 * @file plugins/generic/latexConverter/classes/PluginConfig.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2021-2025 Gazi Yücel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PluginConfig
 *
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Plugin settings page
 */

namespace APP\plugins\generic\latexConverter\classes;

use APP\plugins\generic\latexConverter\classes\forms\Settings;
use APP\plugins\generic\latexConverter\LatexConverterPlugin;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\submissionFile\SubmissionFile;

class PluginConfig
{
    public LatexConverterPlugin $plugin;

    public function __construct(LatexConverterPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Add links to plugin actions.
     */
    public function actions($request, $actionArgs, $parentActions): array
    {
        if (!$this->plugin->getEnabled()) {
            return $parentActions;
        }

        $router = $request->getRouter();

        $linkAction[] = new LinkAction(
            'settings',
            new AjaxModal(
                $router->url(
                    $request,
                    null,
                    null,
                    'manage',
                    null,
                    [
                        'verb' => 'settings',
                        'plugin' => $this->plugin->getName(),
                        'category' => 'generic'
                    ]
                ),
                $this->plugin->getDisplayName()
            ),
            __('manager.plugins.settings'),
            null
        );

        array_unshift($parentActions, ...$linkAction);

        return $parentActions;
    }

    /**
     * Manage actions.
     */
    public function manage($args, $request): JSONMessage
    {
        switch ($request->getUserVar('verb')) {
            case 'settings':
                $form = new Settings($this->plugin);

                // Return initial form if not submitted
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

    /**
     * Add mimetypes which support dependent files.
     */
    public function mimeTypes(string $hookName, array $args): void
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
