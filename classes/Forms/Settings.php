<?php

/**
 * @file plugins/generic/latexConverter/classes/Forms/Settings.php
 *
 * Copyright (c) 2021-2025 TIB Hannover
 * Copyright (c) 2021-2025 Gazi Yücel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Settings
 *
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Form for journal managers to configure the plugin
 */

namespace APP\plugins\generic\latexConverter\classes\Forms;

use APP\core\Application;
use APP\notification\Notification;
use APP\notification\NotificationManager;
use APP\plugins\generic\latexConverter\classes\Constants;
use APP\plugins\generic\latexConverter\LatexConverterPlugin;
use APP\template\TemplateManager;
use PKP\core\PKPApplication;
use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorPost;

class Settings extends Form
{
    public LatexConverterPlugin $plugin;

    private array $settings = [
        Constants::SETTING_AUTHORISED_MIME_TYPES,
        Constants::SETTING_LATEX_PATH_EXECUTABLE
    ];

    public function __construct(LatexConverterPlugin $plugin)
    {
        parent::__construct($plugin->getTemplateResource('settings.tpl'));

        $this->plugin = $plugin;

        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /** @copydoc Form::initData() */
    public function initData(): void
    {
        $context = Application::get()->getRequest()->getContext();

        $contextId = $context
            ? $context->getId()
            : PKPApplication::SITE_CONTEXT_ID;

        foreach ($this->settings as $key) {
            $this->setData($key,
                $this->plugin->getSetting($contextId, $key)
            );
        }

        parent::initData();
    }

    /** @copydoc Form::readInputData() */
    public function readInputData(): void
    {
        foreach ($this->settings as $key) {
            $this->readUserVars([$key]);
        }

        parent::readInputData();
    }

    /** @copydoc Form::fetch() */
    public function fetch($request, $template = null, $display = false): ?string
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());

        return parent::fetch($request, $template, $display);
    }

    /** @copydoc Form::execute() */
    public function execute(...$functionArgs): mixed
    {
        $context = Application::get()->getRequest()->getContext();

        $contextId = $context
            ? $context->getId()
            : Application::SITE_CONTEXT_ID;

        foreach ($this->settings as $key) {
            $this->plugin->updateSetting(
                $contextId,
                $key,
                $this->getData($key)
            );
        }

        $notificationMgr = new NotificationManager();
        $notificationMgr->createTrivialNotification(
            Application::get()->getRequest()->getUser()->getId(),
            Notification::NOTIFICATION_TYPE_SUCCESS,
            ['contents' => __('common.changesSaved')]
        );

        return parent::execute();
    }
}
