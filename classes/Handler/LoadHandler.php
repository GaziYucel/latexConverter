<?php
/**
 * @file classes/Handler/LoadHandler.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class LoadHandler
 * @brief LoadHandler
 */

namespace APP\plugins\generic\latexConverter\classes\Handler;

class LoadHandler
{
    /**
     * Execute PluginHandler
     * @param $hookName string
     * @param $args array Hook arguments [&$page, &$op, &$sourceFile]
     * @return bool
     */
    public function execute(string $hookName, array $args): bool
    {
        $page = $args[0];
        $op = $args[1];

        switch ("$page/$op") {
            case "latexConverter/extractShow":
            case "latexConverter/extractExecute":
            case "latexConverter/convert":
                define('HANDLER_CLASS', '\APP\plugins\generic\latexConverter\classes\Handler\PluginHandler');
                return true;
            default:
                break;
        }

        return false;
    }
}
