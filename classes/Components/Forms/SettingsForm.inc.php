<?php
/**
 * @file plugins/generic/latexConverter/classes/SettingsForm.inc.php
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SettingsForm
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Form for journal managers to configure the latexConverter plugin
 */

namespace TIBHannover\LatexConverter\Components\Forms;

import('lib.pkp.classes.form.Form');

use LatexConverterPlugin;
use FormValidatorPost;
use FormValidatorCSRF;
use Application;
use TemplateManager;
use NotificationManager;

class SettingsForm extends \Form
{
    /**
     * @var $plugin LatexConverterPlugin
     */
    public LatexConverterPlugin $plugin;

    /**
     * Array of variables saved in the database
     * @var string[]
     */
    private array $settings = [
        LATEX_CONVERTER_SETTING_KEY_SUPPORTS_DEPENDENT_FILES_MIME_TYPES,
        LATEX_CONVERTER_SETTING_KEY_PATH_EXECUTABLE
    ];

    /**
     * @copydoc Form::__construct()
     */
    public function __construct($plugin)
    {
        // Define the settings template and store a copy of the plugin object
        parent::__construct($plugin->getTemplateResource('settings.tpl'));
        $this->plugin = $plugin;

        // Always add POST and CSRF validation to secure your form.
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
     * Load settings already saved in the database Settings are stored by context, so that each journal or press can have different settings.
     * @copydoc Form::initData()
     */
    public function initData(): void
    {
        $context = Application::get()->getRequest()->getContext();
        $contextId = $context ? $context->getId() : CONTEXT_SITE;
        foreach ($this->settings as $key) {
            $this->setData($key, $this->plugin->getSetting($contextId, $key));
        }

        parent::initData();
    }

    /**
     * Load data that was submitted with the form
     * @copydoc Form::readInputData()
     */
    public function readInputData(): void
    {
        foreach ($this->settings as $key) {
            $this->readUserVars([$key]);
        }
        parent::readInputData();
    }

    /**
     * Fetch any additional data needed for your form. Data assigned to the form using $this->setData()
     * during the initData() or readInputData() methods will be passed to the template.
     * @copydoc Form::fetch()
     */
    public function fetch($request, $template = null, $display = false): ?string
    {
        // Pass the plugin name to the template so that it can be
        // used in the URL that the form is submitted to
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());

        return parent::fetch($request, $template, $display);
    }

    /**
     * Save the settings
     * @copydoc Form::execute()
     * @return null|mixed
     */
    public function execute(...$functionArgs): mixed
    {
        $context = Application::get()->getRequest()->getContext();
        $contextId = $context ? $context->getId() : CONTEXT_SITE;

        foreach ($this->settings as $key) {
            $value = $this->getData($key);
            $this->plugin->updateSetting($contextId, $key, $value);
        }

        // Tell the user that the save was successful.
        import('classes.notification.NotificationManager');
        $notificationMgr = new NotificationManager();
        $notificationMgr->createTrivialNotification(
            Application::get()->getRequest()->getUser()->getId(),
            NOTIFICATION_TYPE_SUCCESS,
            ['contents' => __('common.changesSaved')]
        );

        return parent::execute();
    }
}