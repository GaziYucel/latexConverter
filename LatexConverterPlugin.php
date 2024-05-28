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

require_once(__DIR__ . '/vendor/autoload.php');

use APP\plugins\generic\latexConverter\classes\Constants;
use APP\plugins\generic\latexConverter\classes\Handler\LoadHandler;
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
    /**
     * @copydoc Plugin::register
     */
    function register($category, $path, $mainContextId = null): bool
    {
        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled()) {
                $loadHandler = new LoadHandler();
                $links = new Links($this);
                $mimeTypes = new MimeTypes($this);
                Hook::add('LoadHandler', [$loadHandler, 'execute']);
                Hook::add('TemplateManager::fetch', [$links, 'execute']);
                Hook::add('SubmissionFile::supportsDependentFiles', [$mimeTypes, 'execute']);

                $this->_registerTemplateResource();
            }

            return true;
        }

        return false;
    }

    /** @copydoc Plugin::getActions() */
    public function getActions($request, $actionArgs): array
    {
        if (!$this->getEnabled()) return parent::getActions($request, $actionArgs);

        $actions = new Actions($this);
        return $actions->execute($request, $actionArgs, parent::getActions($request, $actionArgs));
    }

    /** @copydoc Plugin::manage() */
    public function manage($args, $request): JSONMessage
    {
        $manage = new Manage($this);
        return $manage->execute($args, $request);
    }

    /* Plugin required methods */

    /**
     * @copydoc PKPPlugin::getDisplayName
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.latexConverter.displayName');
    }

    /**
     * @copydoc PKPPlugin::getDescription
     */
    public function getDescription(): string
    {
        return __('plugins.generic.latexConverter.description');
    }

    /**
     * Overrides parent getSetting
     * @param $contextId
     * @param $name
     * @return mixed|null
     */
    public function getSetting($contextId, $name): mixed
    {
        switch ($name) {
            case Constants::settingKeyPathExecutable:
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
