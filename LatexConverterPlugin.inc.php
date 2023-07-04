<?php
/**
 * @file plugins/generic/latexConverter/LatexConverterPlugin.inc.php
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class LatexConverterPlugin
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Plugin LatexConverter
 */

const LATEX_CONVERTER_PLUGIN_PATH = __DIR__;
const LATEX_CONVERTER_ZIP_FILE_TYPE = 'application/zip';
const LATEX_CONVERTER_LATEX_FILE_TYPE = 'text/x-tex';
const LATEX_CONVERTER_TEX_EXTENSION = 'tex';
const LATEX_CONVERTER_MAIN_FILENAME = 'main.' . LATEX_CONVERTER_TEX_EXTENSION;
const LATEX_CONVERTER_TEX_EXTENSIONS = [LATEX_CONVERTER_TEX_EXTENSION];
const LATEX_CONVERTER_IMAGE_EXTENSIONS = ['gif', 'jpg', 'jpeg', 'png', 'jpe'];
const LATEX_CONVERTER_HTML_EXTENSIONS = ['htm', 'html'];
const LATEX_CONVERTER_STYLE_EXTENSIONS = ['css'];
const LATEX_CONVERTER_AUTHORIZED_ROLES = [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT];
const LATEX_CONVERTER_SETTING_KEY_SUPPORTS_DEPENDENT_FILES_MIME_TYPES = 'LatexConverter_AuthorisedMimeTypes';

require_once(LATEX_CONVERTER_PLUGIN_PATH . '/vendor/autoload.php');

import('lib.pkp.classes.plugins.GenericPlugin');

use TIBHannover\LatexConverter\Components\Forms\SettingsForm;

class LatexConverterPlugin extends GenericPlugin
{
    /**
     * @copydoc Plugin::register
     */
    function register($category, $path, $mainContextId = null): bool
    {
        if (!defined("LATEX_CONVERTER_PLUGIN_NAME"))
            define("LATEX_CONVERTER_PLUGIN_NAME", $this->getName());

        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled()) {
                HookRegistry::register('TemplateManager::fetch', [$this, 'callbackTemplateFetch']);
                HookRegistry::register('LoadHandler', [$this, 'callbackLoadHandler']);

                HookRegistry::register('SubmissionFile::supportsDependentFiles', [$this, 'callbackSupportsDependentFiles']);

                $this->_registerTemplateResource();
            }

            return true;
        }

        return false;
    }

    /**
     * Adds additional links to submission files grid row
     * @param $hookName string The name of the invoked hook
     * @param $args array Hook parameters [PKPTemplateManager, $template, $cache_id, $compile_id, &$result]
     * @return void
     */
    public function callbackTemplateFetch(string $hookName, array $args): void
    {
        $request = $this->getRequest();
        $dispatcher = $request->getDispatcher();

        $templateMgr = $args[0];
        $resourceName = $args[1];
        if ($resourceName == 'controllers/grid/gridRow.tpl') {
            $row = $templateMgr->getTemplateVars('row');
            $data = $row->getData();
            if (is_array($data) && (isset($data['submissionFile']))) {
                $submissionFile = $data['submissionFile'];
                $fileExtension = strtolower($submissionFile->getData('mimetype'));

                $stageId = (int)$request->getUserVar('stageId');
                $submissionId = $submissionFile->getData('submissionId');
                $submission = Services::get('submission')->get($submissionId);
                $submissionStageId = $submission->getData('stageId');
                $roles = $request->getUser()->getRoles($request->getContext()->getId());

                $isAuthorized = false;
                foreach ($roles as $role) {
                    if (in_array($role->getId(), LATEX_CONVERTER_AUTHORIZED_ROLES)) {
                        $isAuthorized = true;
                        break;
                    }
                }

                // ensure that the conversion is run on the appropriate workflow stage
                if ($isAuthorized && $stageId == WORKFLOW_STAGE_ID_PRODUCTION &&
                    $submissionStageId == WORKFLOW_STAGE_ID_PRODUCTION
                ) {
                    $actionArgs = array(
                        'submissionId' => $submissionId,
                        'submissionFileId' => $submissionFile->getId(),
                        'stageId' => $stageId,
                        'archiveType' => LATEX_CONVERTER_ZIP_FILE_TYPE);

                    $pathRedirect = $dispatcher->url($request, ROUTE_PAGE, null,
                        'workflow', 'access', $submissionId);

                    // only show link if file is zip
                    if (strtolower($fileExtension) == LATEX_CONVERTER_ZIP_FILE_TYPE) {

                        $path = $dispatcher->url($request, ROUTE_PAGE, null,
                            'latexConverter', 'extract', null, $actionArgs);

                        import('lib.pkp.classes.linkAction.request.PostAndRedirectAction');

                        $row->addAction(new LinkAction(
                            'latexconverter_extract_zip',
                            new PostAndRedirectAction($path, $pathRedirect),
                            __('plugins.generic.latexConverter.button.extract')
                        ));
                    } // only show link if file is tex and is not dependent file (assocId is null)
                    elseif (strtolower($fileExtension) == LATEX_CONVERTER_LATEX_FILE_TYPE
                        && empty($submissionFile->getData('assocId'))) {

                        $path = $dispatcher->url($request, ROUTE_PAGE, null,
                            'latexConverter', 'convert', null, $actionArgs);

                        import('lib.pkp.classes.linkAction.request.PostAndRedirectAction');

                        $row->addAction(new LinkAction(
                            'latexconverter_convert_to_pdf',
                            new PostAndRedirectAction($path, $pathRedirect),
                            __('plugins.generic.latexConverter.button.convert')
                        ));
                    }
                }
            }
        }
    }

    /**
     * Execute PluginHandler
     * @param $hookName string
     * @param $args array Hook parameters [&$page, &$op, &$sourceFile]
     * @return bool
     */
    public function callbackLoadHandler(string $hookName, array $args): bool
    {
        $page = $args[0];
        $op = $args[1];

        switch ("$page/$op") {
            case "latexConverter/extract":
            case "latexConverter/convert":
                define('HANDLER_CLASS', 'TIBHannover\LatexConverter\Handler\PluginHandler');
                return true;
            default:
                break;
        }

        return false;
    }

    /**
     * Add mimetypes which support dependent files
     * @param $hookName string
     * @param $args array Hook parameters [&$result, $submissionFile]
     * @return void
     */
    public function callbackSupportsDependentFiles(string $hookName, array $args): void
    {
        $result = &$args[0];
        $submissionFile = $args[1];

        $fileStage = $submissionFile->getData('fileStage');
        $excludedFileStages = [
            SUBMISSION_FILE_DEPENDENT,
            SUBMISSION_FILE_QUERY,
        ];

        $allowedMimetypes = ['text/x-tex', 'application/x-tex'];
        $allowedMimetypesDb = $this->getSetting($this->getCurrentContextId(),
            LATEX_CONVERTER_SETTING_KEY_SUPPORTS_DEPENDENT_FILES_MIME_TYPES);
        if (!empty($allowedMimetypesDb))
            $allowedMimetypes = array_filter(preg_split("/\r\n|\n|\r/", $allowedMimetypesDb));

        $result =
            !in_array($fileStage, $excludedFileStages) &&
            in_array($submissionFile->getData('mimetype'), $allowedMimetypes);
    }

    /**
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $actionArgs): array
    {
        $actions = parent::getActions($request, $actionArgs);
        if (!$this->getEnabled()) return $actions;

        import('lib.pkp.classes.linkAction.request.AjaxModal');
        $router = $request->getRouter();

        $linkAction[] = new \LinkAction(
            'settings',
            new \AjaxModal(
                $router->url(
                    $request, null, null, 'manage', null,
                    array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')),
                $this->getDisplayName()),
            __('manager.plugins.settings'),
            null);

        array_unshift($actions, ...$linkAction);

        return $actions;
    }

    /**
     * @copydoc Plugin::manage()
     */
    public function manage($args, $request): \JSONMessage
    {
        $context = $request->getContext();
        switch ($request->getUserVar('verb')) {
            case 'settings':
                // Load the custom form
                $form = new SettingsForm($this);

                // Fetch the form the first time it loads, before the user has tried to save it
                if (!$request->getUserVar('save')) {
                    $form->initData();
                    return new \JSONMessage(true, $form->fetch($request));
                }

                // Validate and save the form data
                $form->readInputData();
                if ($form->validate()) {
                    $form->execute();
                    return new \JSONMessage(true);
                }
                break;
            default:
                break;
        }
        return parent::manage($args, $request);
    }

    /* Plugin required methods */

    /**
     * @copydoc PKPPlugin::getDisplayName
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.latexConverter.displayName');
    }

    /**
     * @copydoc PKPPlugin::getDescription
     */
    public function getDescription(): string
    {
        return __('plugins.generic.latexConverter.description');
    }

	public static function logFilePath() {
		return Config::getVar('files', 'files_dir') . '/latexConverter.log';
	}

	/**
	 * Write a message with specified level to log
	 *
	 * @param  $message string Message to write
	 * @param  $level   string Error level to add to message
	 * @return void
	 */
	protected static function writeLog($message, $level) {
		$fineStamp = date('Y-m-d H:i:s') . substr(microtime(), 1, 4);
		error_log("$fineStamp $level $message\n", 3, self::logFilePath());
	}

	public function logError($message) {
		self::writeLog($message, 'ERROR');
	}

}
