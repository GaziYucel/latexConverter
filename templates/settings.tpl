{**
 * templates/settings.tpl
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Settings form for the latexConverterSettings plugin.
 *}
<script>
    $(function () {ldelim}
        $('#{$smarty.const.LATEX_CONVERTER_PLUGIN_NAME}Settings').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
        {rdelim});
</script>

<form
        class="pkp_form"
        id="{$smarty.const.LATEX_CONVERTER_PLUGIN_NAME}Settings"
        method="POST"
        action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}"
>
    <!-- Always add the csrf token to secure your form -->
    {csrf}

    {fbvFormArea id="latexConverterSettingsArea"}

        {fbvFormSection}
            <p>
                {fbvElement
                    type="text"
                    id="{LatexConverterPlugin::LATEX_CONVERTER_SETTING_KEY_PATH_EXECUTABLE}"
                    value=${LatexConverterPlugin::LATEX_CONVERTER_SETTING_KEY_PATH_EXECUTABLE}
                    label="plugins.generic.latexConverter.settings.path_executable.label"
                    required="true"
                }
            </p>

            <p>
                {fbvElement
                    type="textarea"
                    id="{LatexConverterPlugin::LATEX_CONVERTER_SETTING_KEY_SUPPORTS_DEPENDENT_FILES_MIME_TYPES}"
                    value=${LatexConverterPlugin::LATEX_CONVERTER_SETTING_KEY_SUPPORTS_DEPENDENT_FILES_MIME_TYPES}
                    label="plugins.generic.latexConverter.settings.authorised_mime_types.label"
                }
            </p>
        {/fbvFormSection}

    {/fbvFormArea}

    {fbvFormButtons submitText="common.save"}
</form>
