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

use AjaxModal;
use APP\plugins\generic\latexConverter\classes\Constants;
use APP\plugins\generic\latexConverter\LatexConverterPlugin;
use LinkAction;
use PostAndRedirectAction;
use Services;

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
                $submission = Services::get('submission')->get($submissionId);
                $submissionStageId = $submission->getData('stageId');
                $roles = $request->getUser()->getRoles($request->getContext()->getId());
                $isAuthorized = false;
                foreach ($roles as $role) {
                    if (in_array($role->getId(),
                        Constants::authorizedRoles)) {
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
                        'archiveType' => Constants::zipFileType);

                    $pathRedirect = $dispatcher->url(
                        $request,
                        ROUTE_PAGE,
                        null,
                        'workflow',
                        'access',
                        $submissionId
                    );

                    // only show link if file is zip
                    if (strtolower($fileExtension) == Constants::zipFileType) {

                        $path = $dispatcher->url(
                            $request,
                            ROUTE_PAGE,
                            null,
                            'latexConverter',
                            'extractShow',
                            null,
                            $actionArgs
                        );

                        $row->addAction(
                            new LinkAction(
                                'latexconverter_extract_zip',
                                new AjaxModal(
                                    $path,
                                    __('plugins.generic.latexConverter.modal.extract.title')
                                ),
                                __('plugins.generic.latexConverter.button.extract'),
                                null
                            )
                        );

                    } // only show link if file is tex and is not dependent file (assocId is null)
                    elseif (strtolower($fileExtension) == Constants::texFileType
                        && empty($submissionFile->getData('assocId'))) {

                        $disableLink = true;
                        $linkText = $this->getConvertButton();
                        if ($linkText === 'plugins.generic.latexConverter.button.convert') $disableLink = false;

                        $path = $dispatcher->url(
                            $request,
                            ROUTE_PAGE,
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

    /**
     * Converter button depending on the latex executable status
     *
     * @return  string
     */
    public function getConvertButton(): string
    {
        $latexExe = $this->plugin->getSetting($this->plugin->getRequest()->getContext()->getId(),
            Constants::settingKeyPathExecutable);

        if (strlen($latexExe) == 0) {
            return 'plugins.generic.latexConverter.executable.notConfigured';
        } elseif (!is_executable($latexExe)) {
            return 'plugins.generic.latexConverter.executable.notFound';
        }

        return 'plugins.generic.latexConverter.button.convert';
    }
}
