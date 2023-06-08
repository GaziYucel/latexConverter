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

const LATEX_CONVERTER_IS_PRODUCTION_KEY = 'LatexConverter_IsProductionEnvironment';
const LATEX_CONVERTER_PLUGIN_PATH = __DIR__;
const LATEX_CONVERTER_ZIP_FILE_TYPE = 'application/zip';
const LATEX_CONVERTER_LATEX_FILE_TYPE = 'text/x-tex';
const LATEX_CONVERTER_AUTHORIZED_ROLES = [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT];

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
        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled()) {
                define('LATEX_CONVERTER_PLUGIN_NAME', $this->getName());

                HookRegistry::register('TemplateManager::fetch', array($this, 'templateFetchCallback'));
                HookRegistry::register('LoadHandler', array($this, 'callbackLoadHandler'));

                $this->_registerTemplateResource();
            }
            return true;
        }
        return false;
    }

    /**
     * Adds additional links to submission files grid row
     * @param $hookName string The name of the invoked hook
     * @param $args array Hook parameters
     * @return void
     */
    public function templateFetchCallback(string $hookName, array $args): void
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
                error_log("fileExtension: $fileExtension");

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
                        'stageId' => $stageId);

                    $pathRedirect = $dispatcher->url($request, ROUTE_PAGE, null,
                        'workflow', 'access', $submissionId);

                    // only show link if file is zip
                    if (strtolower($fileExtension) == LATEX_CONVERTER_ZIP_FILE_TYPE) {

                        $path = $dispatcher->url($request, ROUTE_PAGE, null,
                            'latexConverter', 'extractZip', null, $actionArgs);

                        import('lib.pkp.classes.linkAction.request.PostAndRedirectAction');

                        $row->addAction(new LinkAction(
                            'latexconverter_extract_zip',
                            new PostAndRedirectAction($path, $pathRedirect),
                            __('plugins.generic.latexConverter.button.extractZip')
                        ));
                    } // only show link if file is tex
                    elseif (strtolower($fileExtension) == LATEX_CONVERTER_LATEX_FILE_TYPE) {

                        $path = $dispatcher->url($request, ROUTE_PAGE, null,
                            'latexConverter', 'convertToPdf', null, $actionArgs);

                        import('lib.pkp.classes.linkAction.request.PostAndRedirectAction');

                        $row->addAction(new LinkAction(
                            'latexconverter_convert_to_pdf',
                            new PostAndRedirectAction($path, $pathRedirect),
                            __('plugins.generic.latexConverter.button.convertToPdf')
                        ));
                    }
                }
            }
        }
    }

    /**
     * Execute LatexConverterHandler
     * @param $hookName
     * @param $args
     * @return bool
     */
    public function callbackLoadHandler($hookName, $args): bool
    {
        $page = $args[0];
        $op = $args[1];

        switch ("$page/$op") {
            case "latexConverter/extractZip":
            case "latexConverter/convertToPdf":
                define('HANDLER_CLASS', 'TIBHannover\LatexConverter\Handler\LatexConverterHandler');
                return true;
            default:
                break;
        }

        return false;
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
}
