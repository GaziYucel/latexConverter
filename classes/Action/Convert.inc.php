<?php
/**
 * @file plugins/generic/latexConverter/classes/Action/Convert.inc.php
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Convert
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Action Convert for the Handler
 */

namespace TIBHannover\LatexConverter\Action;

use JSONMessage;

class Convert
{
    /**
     * @var object LatexConverterPlugin
     */
    protected object $plugin;

    function __construct($plugin, $request, $params)
    {
        $this->plugin = $plugin;
    }

    /**
     * Main entry point
     * @return JSONMessage
     */
    public function execute(): JSONMessage
    {
        return $this->convert();
    }

    /**
     * Converts LaTex file to pdf
     * @return JSONMessage
     */
    private function convert(): JSONMessage
    {
        return new JSONMessage(true, ['submissionId' => '51']);
    }
}