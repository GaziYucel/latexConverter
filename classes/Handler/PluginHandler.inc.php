<?php
/**
 * @file plugins/generic/latexConverter/classes/Handler/LatexConverterHandler.inc.php
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class LatexConverterHandler
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Handler for the plugin LatexConverter
 */

namespace TIBHannover\LatexConverter\Handler;

import('classes.handler.Handler');

use Handler;
use JSONMessage;
use PluginRegistry;
use WorkflowStageAccessPolicy;
use TIBHannover\LatexConverter\Action\Convert;
use TIBHannover\LatexConverter\Action\Extract;

class PluginHandler extends Handler
{
    /**
     * @var object LatexConverterPlugin
     */
    protected object $plugin;

    /**
     * List of methods allowed
     * @var array|string[]
     */
    protected array $allowedMethods = ['extractShow', 'extractExecute', 'convert'];

    function __construct()
    {
        parent::__construct();

        $this->plugin = PluginRegistry::getPlugin('generic', LATEX_CONVERTER_PLUGIN_NAME);
        $this->addRoleAssignment(LATEX_CONVERTER_AUTHORIZED_ROLES, $this->allowedMethods);
    }

    /**
     * Overridden method from Handler
     * @copydoc PKPHandler::authorize()
     */
    function authorize($request, &$args, $roleAssignments): bool
    {
        import('lib.pkp.classes.security.authorization.WorkflowStageAccessPolicy');
        $this->addPolicy(new WorkflowStageAccessPolicy($request, $args, $roleAssignments,
            'submissionId', (int)$request->getUserVar('stageId')));

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Extracts file and adds to files list
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
     * @param $args
     * @param $request
     * @return JSONMessage JSON object
     */
    public function extractExecute($args, $request): JSONMessage
    {
        $action = new Extract($this->plugin, $request, $args);

        $action->readInputData();

        $action->process();

        return $request->redirectUrlJson($request->getDispatcher()->url($request, ROUTE_PAGE, null, 'workflow', 'access', null,
            array(
                'submissionId' => $request->getUserVar('submissionId'),
                'stageId' => $request->getUserVar('stageId')
            )
        ));
    }

    /**
     * Converts LaTex file to pdf
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
