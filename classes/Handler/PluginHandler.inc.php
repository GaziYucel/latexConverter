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
    protected object $plugin;

    /**
     * List of methods allowed
     * @var array|string[]
     */
    protected array $allowedMethods = ['extract', 'convert'];

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
     * @param $params
     * @param $request
     * @return JSONMessage
     */
    public function extract($params, $request): JSONMessage
    {
        $action = new Extract($this->plugin, $request, $params);
        return $action->execute();
    }

    /**
     * Converts LaTex file to pdf
     * @param $params
     * @param $request
     * @return JSONMessage
     */
    public function convert($params, $request): JSONMessage
    {
        $action = new Convert($this->plugin, $request, $params);
        return $action->execute();
    }
}
