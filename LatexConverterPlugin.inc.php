<?php
/**
 * @file plugins/generic/latexConverter/LatexConverterPlugin.inc.php
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class LatexConverterPlugin
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Plugin LatexConverter
 */

const LATEX_CONVERTER_IS_PRODUCTION_KEY = 'LatexConverter_IsProductionEnvironment';
const LATEX_CONVERTER_PLUGIN_PATH = __DIR__;

require_once(LATEX_CONVERTER_PLUGIN_PATH . '/vendor/autoload.php');

use TIBHannover\LatexConverter\Components\Forms\SettingsForm;

import('lib.pkp.classes.plugins.GenericPlugin');

class LatexConverterPlugin extends GenericPlugin
{
    /**
     * @copydoc Plugin::register
     */
    function register($category, $path, $mainContextId = null): bool
    {
        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled()) {
                // Register callbacks.
                HookRegistry::register('TemplateManager::fetch', array($this, 'templateFetchCallback'));
                HookRegistry::register('LoadHandler', array($this, 'callbackLoadHandler'));
                $this->_registerTemplateResource();
            }
            return true;
        }
        return false;
    }

    /**
     * Adds additional links to submission files grid row
     * @param $hookName string The name of the invoked hook
     * @param $args array Hook parameters
     * @return void
     */
    public function templateFetchCallback(string $hookName, array $args): void
    {

    }

    /**
     * Execute LatexConverterHandler
     * @param $hookName
     * @param $args
     * @return bool
     */
    public function callbackLoadHandler($hookName, $args): bool
    {
        return false;
    }

    /**
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $actionArgs): array
    {
        $actions = parent::getActions($request, $actionArgs);
        if (!$this->getEnabled()) return $actions;

        import('lib.pkp.classes.linkAction.request.AjaxModal');
        $router = $request->getRouter();

        $linkAction[] = new \LinkAction(
            'settings',
            new \AjaxModal(
                $router->url(
                    $request, null, null, 'manage', null,
                    array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')),
                $this->getDisplayName()),
            __('manager.plugins.settings'),
            null);

        array_unshift($actions, ...$linkAction);

        return $actions;
    }

    /**
     * @copydoc Plugin::manage()
     */
    public function manage($args, $request): \JSONMessage
    {
        $context = $request->getContext();
        switch ($request->getUserVar('verb')) {
            case 'settings':
                // Load the custom form
                $form = new SettingsForm($this);

                // Fetch the form the first time it loads, before the user has tried to save it
                if (!$request->getUserVar('save')) {
                    $form->initData();
                    return new \JSONMessage(true, $form->fetch($request));
                }

                // Validate and save the form data
                $form->readInputData();
                if ($form->validate()) {
                    $form->execute();
                    return new \JSONMessage(true);
                }
        }
        return parent::manage($args, $request);
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
}
