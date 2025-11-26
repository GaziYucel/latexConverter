<?php

/**
 * @file plugins/generic/latexConverter/LatexConverterPlugin.php
 *
 * Copyright (c) 2021-2025 TIB Hannover
 * Copyright (c) 2021-2025 Gazi Yücel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class LatexConverterPlugin
 *
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Plugin LatexConverter
 */

namespace APP\plugins\generic\latexConverter;

use APP\core\Application;
use APP\core\Request;
use APP\plugins\generic\latexConverter\classes\Constants;
use APP\plugins\generic\latexConverter\classes\PluginApiHandler;
use APP\plugins\generic\latexConverter\classes\PluginConfig;
use APP\template\TemplateManager;
use PKP\config\Config;
use PKP\core\JSONMessage;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class LatexConverterPlugin extends GenericPlugin
{
    private PluginConfig $pluginConfig;

    /** @copydoc Plugin::register */
    function register($category, $path, $mainContextId = null): bool
    {
        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled()) {
                $request = Application::get()->getRequest();
                $templateMgr = TemplateManager::getManager($request);

                $apiHandler = new PluginApiHandler($this);
                Hook::add('APIHandler::endpoints::submissions', $apiHandler->addRoute(...));

                $this->pluginConfig = new PluginConfig($this);
                Hook::add('SubmissionFile::supportsDependentFiles', $this->pluginConfig->mimeTypes(...));

                $userRoleIds = array_map(fn($role) => $role->getId(),
                    $request->getUser()->getRoles($request->getContext()->getId()));
                if (!empty(array_intersect($userRoleIds, Constants::AUTHORISED_ROLES))) {
                    $this->addResources($templateMgr, $request);
                }
            }
            return true;
        }
        return false;
    }

    /** @copydoc Plugin::getActions() */
    public function getActions($request, $actionArgs): array
    {
        return $this->pluginConfig->actions($request, $actionArgs,
            parent::getActions($request, $actionArgs));
    }

    /** @copydoc Plugin::manage() */
    public function manage($args, $request): JSONMessage
    {
        return $this->pluginConfig->manage($args, $request);
    }

    /** @copydoc Plugin::getDisplayName */
    public function getDisplayName(): string
    {
        return __('plugins.generic.latexConverter.displayName');
    }

    /** @copydoc Plugin::getDescription */
    public function getDescription(): string
    {
        return __('plugins.generic.latexConverter.description');
    }

    /** @copydoc Plugin::getSetting */
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

    /**
     * Add Vue resources
     */
    public function addResources(TemplateManager $templateMgr, Request $request): void
    {
        $templateMgr->addJavaScript(
            'LatexConverterJs',
            "{$request->getBaseUrl()}/{$this->getPluginPath()}/public/build/build.iife.js",
            [
                'inline' => false,
                'contexts' => ['backend'],
                'priority' => TemplateManager::STYLE_SEQUENCE_LAST
            ]
        );

        $templateMgr->addStyleSheet('backendUiExampleStyle',
            "{$request->getBaseUrl()}/{$this->getPluginPath()}/public/build/build.css",
            [
                'contexts' => ['backend']
            ]
        );
    }
}

// For backwards compatibility -- expect this to be removed approx. OJS/OMP/OPS 3.6
if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\latexConverter\LatexConverterPlugin', '\LatexConverterPlugin');
}
