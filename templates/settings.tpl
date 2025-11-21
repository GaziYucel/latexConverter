{**
 * @file plugins/generic/latexConverter/templates/settings.tpl
 *
 * @copyright (c) 2021-2025 TIB Hannover
 * @copyright (c) 2021-2025 Gazi Yücel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Settings form for the latexConverterSettings plugin.
 *}
<script>
    $(function () {
        $('#LatexConverterPluginSettings').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    });
</script>

<form class="pkp_form" method="POST"
      id="LatexConverterPluginSettings"
      action="{url router=$smarty.const.ROUTE_COMPONENT
      op="manage"
      category="generic"
      plugin=$pluginName
      verb="settings"
      save=true}">

    {csrf}

    {fbvFormArea id="LatexConverterPluginSettingsArea"}

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
