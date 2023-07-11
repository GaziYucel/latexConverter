<script>
    $(function () {ldelim}
        $('#latexConverterSettings').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
        {rdelim});
</script>

<form
        class="pkp_form"
        id="latexConverterSettings"
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
                    id="{$smarty.const.LATEX_CONVERTER_SETTING_KEY_PATH_EXECUTABLE}"
                    value=${$smarty.const.LATEX_CONVERTER_SETTING_KEY_PATH_EXECUTABLE}
                    label="plugins.generic.latexConverter.settings.path_executable.label"
                    required="true"
                }
            </p>

            <p>
                {fbvElement
                    type="textarea"
                    id="{$smarty.const.LATEX_CONVERTER_SETTING_KEY_SUPPORTS_DEPENDENT_FILES_MIME_TYPES}"
                    value=${$smarty.const.LATEX_CONVERTER_SETTING_KEY_SUPPORTS_DEPENDENT_FILES_MIME_TYPES}
                    label="plugins.generic.latexConverter.settings.authorised_mime_types.label"
                }
            </p>
        {/fbvFormSection}

    {/fbvFormArea}

    {fbvFormButtons submitText="common.save"}
</form>
