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

import('classes.handler.Handler');
import('lib.pkp.classes.security.authorization.WorkflowStageAccessPolicy');

use APP\plugins\generic\latexConverter\classes\Constants;
use Handler;
use JSONMessage;
use LatexConverterPlugin;
use PluginRegistry;
use WorkflowStageAccessPolicy;
use APP\plugins\generic\latexConverter\classes\Workflow\Convert;
use APP\plugins\generic\latexConverter\classes\Workflow\Extract;

class PluginHandler extends Handler
{
    /** @var LatexConverterPlugin */
    protected LatexConverterPlugin $plugin;

    /** @var array|string[] List of methods allowed */
    protected array $allowedMethods = ['extractShow', 'extractExecute', 'convert'];

    function __construct()
    {
        parent::__construct();

        /* @var LatexConverterPlugin $plugin */
        $plugin = PluginRegistry::getPlugin('generic', strtolower(LATEX_CONVERTER_PLUGIN_NAME));
        $this->plugin = &$plugin;

        $this->addRoleAssignment(Constants::authorizedRoles, $this->allowedMethods);
    }

    /** @copydoc PKPHandler::authorize() */
    function authorize($request, &$args, $roleAssignments): bool
    {
        $policy = new WorkflowStageAccessPolicy(
            $request,
            $args,
            $roleAssignments,
            'submissionId',
            (int)$request->getUserVar('stageId')
        );

        $this->addPolicy($policy);

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
        $action = new Extract($this->plugin);
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
        $action = new Extract($this->plugin);
        $action->readInputData();
        $action->process();
        return $request->redirectUrlJson(
            $request->getDispatcher()->url(
                $request,
                ROUTE_PAGE,
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
        $action = new Convert($this->plugin);
        return $action->process();
    }
}
