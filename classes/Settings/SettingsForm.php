<?php
/**
 * @file plugins/generic/latexConverter/classes/SettingsForm.php
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yücel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SettingsForm
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Form for journal managers to configure the latexConverter plugin
 */

namespace APP\plugins\generic\latexConverter\classes\Settings;

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

class SettingsForm extends Form
{
    /**
     * @var LatexConverterPlugin
     */
    public LatexConverterPlugin $plugin;

    /**
     * Array of variables saved in the database
     *
     * @var string[]
     */
    private array $settings = [
        Constants::settingKeyAuthorisedMimeTypes,
        Constants::settingKeyPathExecutable
    ];

    /**
     * @copydoc Form::__construct()
     */
    public function __construct(LatexConverterPlugin &$plugin)
    {
        parent::__construct($plugin->getTemplateResource('settings.tpl'));

        $this->plugin = &$plugin;

        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
     * Load settings already saved in the database Settings are stored by context,
     * so that each journal or press can have different settings.
     *
     * @copydoc Form::initData()
     */
    public function initData(): void
    {
        $context = Application::get()
            ->getRequest()
            ->getContext();

        $contextId = $context
            ? $context->getId()
            : PKPApplication::CONTEXT_SITE;

        foreach ($this->settings as $key) {
            $this->setData(
                $key,
                $this->plugin->getSetting(
                    $contextId,
                    $key
                )
            );
        }

        parent::initData();
    }

    /**
     * Load data that was submitted with the form
     *
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
     * Fetch any additional data needed for your form.
     *
     * Data assigned to the form using $this->setData() during the
     * initData() or readInputData() methods will be passed to the
     * template.
     *
     * In the example below, the plugin name is passed to the
     * template so that it can be used in the URL that the form is
     * submitted to.
     */
    public function fetch($request, $template = null, $display = false): ?string
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());

        return parent::fetch($request, $template, $display);
    }

    /**
     * Save the settings
     *
     * @copydoc Form::execute()
     * @return null|mixed
     */
    public function execute(...$functionArgs): mixed
    {
        $context = Application::get()
            ->getRequest()
            ->getContext();

        $contextId = $context
            ? $context->getId()
            : Application::CONTEXT_SITE;

        foreach ($this->settings as $key) {
            $data = $this->getData($key);

            if ($key === Constants::settingKeyAuthorisedMimeTypes) {
                if (empty($data)) $data = Constants::settingDefaultAuthorisedMimeTypes;
            }

            $this->plugin->updateSetting(
                $contextId,
                $key,
                $data
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