<?php
/**
 * @file plugins/generic/latexConverter/classes/Handler/LatexConverterHandler.php
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yücel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class LatexConverterHandler
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Handler for the plugin LatexConverter
 */

namespace APP\plugins\generic\latexConverter\classes\Handler;

use APP\handler\Handler;
use APP\plugins\generic\latexConverter\classes\Constants;
use APP\plugins\generic\latexConverter\classes\Workflow\Convert;
use APP\plugins\generic\latexConverter\classes\Workflow\Extract;
use APP\plugins\generic\latexConverter\LatexConverterPlugin;
use PKP\core\JSONMessage;
use PKP\core\PKPApplication;
use PKP\plugins\PluginRegistry;
use PKP\security\authorization\WorkflowStageAccessPolicy;

class PluginHandler extends Handler
{
    /**
     * @var LatexConverterPlugin
     */
    protected LatexConverterPlugin $plugin;

    /**
     * List of methods allowed
     *
     * @var array|string[]
     */
    protected array $allowedMethods = ['extractShow', 'extractExecute', 'convert'];

    function __construct()
    {
        parent::__construct();

        /* @var LatexConverterPlugin $plugin */
        $plugin = PluginRegistry::getPlugin('generic', strtolower(LATEX_CONVERTER_PLUGIN_NAME));
        $this->plugin = $plugin;

        $this->addRoleAssignment(
            Constants::authorizedRoles,
            $this->allowedMethods
        );
    }

    /**
     * Overridden method from Handler
     *
     * @copydoc PKPHandler::authorize()
     */
    function authorize($request, &$args, $roleAssignments): bool
    {
        $this->addPolicy(
            new WorkflowStageAccessPolicy(
                $request,
                $args,
                $roleAssignments,
                'submissionId',
                (int)$request->getUserVar('stageId')
            )
        );

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Extracts file and adds to files list
     *
     * @param $args
     * @param $request
     * @return JSONMessage
     */
    public function extractShow($args, $request): JSONMessage
    {
        $action = new Extract($this->plugin, $request, $args);

        $action->initData();

        return new JSONMessage(true, $action->fetch($request));
    }

    /**
     * Create article from selected main file
     *
     * @param $args
     * @param $request
     * @return JSONMessage JSON object
     */
    public function extractExecute($args, $request): JSONMessage
    {
        $action = new Extract($this->plugin, $request, $args);

        $action->readInputData();

        $action->process();

        return $request->redirectUrlJson(
            $request->getDispatcher()->url(
                $request,
                PKPApplication::ROUTE_PAGE,
                null,
                'workflow',
                'access',
                null,
                [
                    'submissionId' => $request->getUserVar('submissionId'),
                    'stageId' => $request->getUserVar('stageId')
                ]
            )
        );
    }

    /**
     * Converts LaTex file to pdf
     *
     * @param $args
     * @param $request
     * @return JSONMessage
     */
    public function convert($args, $request): JSONMessage
    {
        $action = new Convert($this->plugin, $request, $args);

        return $action->process();
    }
}
