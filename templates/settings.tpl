{**
 * templates/settings.tpl
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yücel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Settings form for the latexConverterSettings plugin.
 *}
<script>
    $(function () {
        $('#{$smarty.const.LATEX_CONVERTER_PLUGIN_NAME}Settings').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    });
</script>

<form class="pkp_form" method="POST" id="{$smarty.const.LATEX_CONVERTER_PLUGIN_NAME}Settings"
      action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">

    {csrf}

    {fbvFormArea id="{$smarty.const.LATEX_CONVERTER_PLUGIN_NAME}SettingsArea"}

    {fbvFormSection}
        <p>
            {fbvElement
            type="text"
            id="{APP\plugins\generic\latexConverter\classes\Constants::SETTING_LATEX_PATH_EXECUTABLE}"
            value=${APP\plugins\generic\latexConverter\classes\Constants::SETTING_LATEX_PATH_EXECUTABLE}
            label="plugins.generic.latexConverter.settings.path_executable.label"
            required="true"
            }
        </p>
        <p>
            {fbvElement
            type="textarea"
            id="{APP\plugins\generic\latexConverter\classes\Constants::SETTING_AUTHORISED_MIME_TYPES}"
            value=${APP\plugins\generic\latexConverter\classes\Constants::SETTING_AUTHORISED_MIME_TYPES}
            label="plugins.generic.latexConverter.settings.authorised_mime_types.label"
            }
        </p>
    {/fbvFormSection}

    {/fbvFormArea}

    {fbvFormButtons submitText="common.save"}
</form>
