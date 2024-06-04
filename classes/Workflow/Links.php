<?php
/**
 * @file classes/Workflow/Links.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Links
 * @brief Links
 */

namespace APP\plugins\generic\latexConverter\classes\Workflow;

use APP\facades\Repo;
use APP\plugins\generic\latexConverter\classes\Constants;
use APP\plugins\generic\latexConverter\LatexConverterPlugin;
use PKP\core\PKPApplication;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\linkAction\request\PostAndRedirectAction;

class Links
{
    /** @var LatexConverterPlugin */
    public LatexConverterPlugin $plugin;

    /** @param LatexConverterPlugin $plugin */
    public function __construct(LatexConverterPlugin &$plugin)
    {
        $this->plugin = &$plugin;
    }

    /**
     * Adds additional links to submission files grid row
     *
     * @param $hookName string The name of the invoked hook
     * @param $args array Hook arguments [PKPTemplateManager, $template, $cache_id, $compile_id, &$result]
     * @return void
     */
    public function execute(string $hookName, array $args): void
    {
        $request = $this->plugin->getRequest();
        $dispatcher = $request->getDispatcher();

        $templateMgr = $args[0];
        $template = $args[1];
        if ($template == 'controllers/grid/gridRow.tpl') {
            $row = $templateMgr->getTemplateVars('row');
            $data = $row->getData();
            if (is_array($data) && isset($data['submissionFile'])) {
                $submissionFile = $data['submissionFile'];
                $fileExtension = strtolower($submissionFile->getData('mimetype'));
                $stageId = (int)$request->getUserVar('stageId');
                $submissionId = $submissionFile->getData('submissionId');
                $submission = Repo::submission()->get($submissionId);
                $submissionStageId = $submission->getData('stageId');
                $roles = $request->getUser()->getRoles($request->getContext()->getId());
                $isAuthorized = false;
                foreach ($roles as $role) {
                    if (in_array($role->getId(),
                        Constants::AUTHORISED_ROLES)) {
                        $isAuthorized = true;
                        break;
                    }
                }

                // ensure that the conversion is run on the appropriate workflow stage
                if ($isAuthorized
                    && $stageId == WORKFLOW_STAGE_ID_PRODUCTION
                    && $submissionStageId == WORKFLOW_STAGE_ID_PRODUCTION
                ) {
                    $actionArgs = array(
                        'submissionId' => $submissionId,
                        'submissionFileId' => $submissionFile->getId(),
                        'stageId' => $stageId,
                        'archiveType' => Constants::ZIP_FILE_TYPE);

                    $pathRedirect = $dispatcher->url(
                        $request,
                        PKPApplication::ROUTE_PAGE,
                        null,
                        'workflow',
                        'access',
                        $submissionId
                    );

                    // only show link if file is zip
                    if (strtolower($fileExtension) == Constants::ZIP_FILE_TYPE) {
                        $path = $dispatcher->url(
                            $request,
                            PKPApplication::ROUTE_PAGE,
                            null,
                            'latexConverter',
                            'extractShow',
                            null,
                            $actionArgs
                        );

                        $row->addAction(
                            new LinkAction(
                                'latexconverter_extract_zip',
                                new AjaxModal($path, __('plugins.generic.latexConverter.modal.extract.title')),
                                __('plugins.generic.latexConverter.button.extract'),
                                null
                            )
                        );
                    } // only show link if file is tex and is not dependent file (assocId is null)
                    elseif (strtolower($fileExtension) == Constants::TEX_FILE_TYPE
                        && empty($submissionFile->getData('assocId'))) {
                        $disableLink = false;
                        $latexExe = $this->plugin->getSetting(
                            $this->plugin->getRequest()->getContext()->getId(),
                            Constants::SETTING_LATEX_PATH_EXECUTABLE);

                        $linkText = 'plugins.generic.latexConverter.button.convert';
                        if (strlen($latexExe) == 0) {
                            $linkText = 'plugins.generic.latexConverter.executable.notConfigured';
                            $disableLink = true;
                        } elseif (!is_executable($latexExe)) {
                            $linkText = 'plugins.generic.latexConverter.executable.notFound';
                            $disableLink = true;
                        }

                        $path = $dispatcher->url(
                            $request,
                            PKPApplication::ROUTE_PAGE,
                            null,
                            'latexConverter',
                            'convert',
                            null,
                            $actionArgs,
                            null,
                            $disableLink
                        );

                        $row->addAction(
                            new LinkAction(
                                'latexconverter_convert_to_pdf',
                                new PostAndRedirectAction($path, $pathRedirect),
                                __($linkText)
                            )
                        );
                    }
                }
            }
        }
    }
}