<?php
/**
 * @file plugins/generic/latexConverter/LatexConverterPlugin.php
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yücel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class LatexConverterPlugin
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Plugin LatexConverter
 */

namespace APP\plugins\generic\latexConverter;

use APP\plugins\generic\latexConverter\classes\Constants;
use APP\plugins\generic\latexConverter\classes\Settings\Actions;
use APP\plugins\generic\latexConverter\classes\Settings\Manage;
use APP\plugins\generic\latexConverter\classes\Settings\MimeTypes;
use APP\plugins\generic\latexConverter\classes\Workflow\Links;
use PKP\config\Config;
use PKP\core\JSONMessage;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

define('LATEX_CONVERTER_PLUGIN_NAME', basename(__FILE__, '.php'));

class LatexConverterPlugin extends GenericPlugin
{
    /** @copydoc Plugin::register */
    function register($category, $path, $mainContextId = null): bool
    {
        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled()) {
                $links = new Links($this);
                $mimeTypes = new MimeTypes($this);
                Hook::add('LoadHandler', [$this, 'registerHandler']);
                Hook::add('TemplateManager::fetch', [$links, 'execute']);
                Hook::add('SubmissionFile::supportsDependentFiles', [$mimeTypes, 'execute']);

                $this->_registerTemplateResource();
            }

            return true;
        }

        return false;
    }

    /** Register PluginHandler */
    public function registerHandler(string $hookName, array $args): bool
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

    /** @copydoc Plugin::getActions() */
    public function getActions($request, $actionArgs): array
    {
        $actions = new Actions($this);
        return $actions->execute($request, $actionArgs, parent::getActions($request, $actionArgs));
    }

    /** @copydoc Plugin::manage() */
    public function manage($args, $request): JSONMessage
    {
        $manage = new Manage($this);
        return $manage->execute($args, $request);
    }

    /** @copydoc PKPPlugin::getDisplayName */
    public function getDisplayName(): string
    {
        return __('plugins.generic.latexConverter.displayName');
    }

    /** @copydoc PKPPlugin::getDescription */
    public function getDescription(): string
    {
        return __('plugins.generic.latexConverter.description');
    }

    /** @copydoc PKPPlugin::getSetting */
    public function getSetting($contextId, $name): mixed
    {
        switch ($name) {
            case Constants::SETTING_LATEX_PATH_EXECUTABLE:
                $config_value = Config::getVar('latex', 'latexExe');
                break;
            default:
                return parent::getSetting($contextId, $name);
        }

        return $config_value ?: parent::getSetting($contextId, $name);
    }
}

// For backwards compatibility -- expect this to be removed approx. OJS/OMP/OPS 3.6
if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\latexConverter\LatexConverterPlugin', '\LatexConverterPlugin');
}
