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

class LatexConverterHandler extends Handler
{
    /**
     *
     * @var object LatexConverterPlugin
     */
    protected object $plugin;

    /**
     * List of methods allowed
     * @var array|string[]
     */
    protected array $allowedMethods = ['extractZip', 'convertToPdf'];

    function __construct()
    {
        parent::__construct();
        $this->plugin = PluginRegistry::getPlugin('generic', LATEX_CONVERTER_PLUGIN_NAME);

        $this->addRoleAssignment(LATEX_CONVERTER_AUTHORIZED_ROLES, $this->allowedMethods);
    }

    /**
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
     * Extracts zip file and adds to files list
     * @param $args
     * @param $request
     * @return JSONMessage
     */
    public function extractZip($args, $request): JSONMessage
    {
        error_log("LatexConverterHandler > extractZip");

        return new JSONMessage(true, array(
            'submissionId' => '51'
        ));
    }

    /**
     * Converts LaTex file to pdf
     * @param $args
     * @param $request
     * @return JSONMessage
     */
    public function convertToPdf($args, $request): JSONMessage
    {
        error_log("LatexConverterHandler > convertToPdf");

        return new JSONMessage(true, array(
            'submissionId' => '51'
        ));
    }
}
