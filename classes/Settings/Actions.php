<?php
/**
 * @file classes/Settings/Actions.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Actions
 * @brief Actions on the settings page
 */

namespace APP\plugins\generic\latexConverter\classes\Settings;

use APP\plugins\generic\latexConverter\LatexConverterPlugin;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;

class Actions
{
    /** @var LatexConverterPlugin */
    public LatexConverterPlugin $plugin;

    /** @param LatexConverterPlugin $plugin */
    public function __construct(LatexConverterPlugin &$plugin)
    {
        $this->plugin = &$plugin;
    }

    /** @copydoc Plugin::getActions() */
    public function execute($request, $actionArgs, $parentActions): array
    {
        if (!$this->plugin->getEnabled()) return $parentActions;

        $router = $request->getRouter();

        $linkAction[] = new LinkAction(
            'settings',
            new AjaxModal(
                $router->url(
                    $request,
                    null,
                    null,
                    'manage',
                    null,
                    [
                        'verb' => 'settings',
                        'plugin' => $this->plugin->getName(),
                        'category' => 'generic'
                    ]
                ),
                $this->plugin->getDisplayName()
            ),
            __('manager.plugins.settings'),
            null
        );

        array_unshift($parentActions, ...$linkAction);

        return $parentActions;
    }
}