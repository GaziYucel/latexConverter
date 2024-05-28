{**
 * templates/extract.tpl
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yücel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Select main file page
 *}
<script>
	$(function() {
		$('#{$smarty.const.LATEX_CONVERTER_PLUGIN_NAME}ExtractForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	});
</script>

<form class="pkp_form" method="post" id="{$smarty.const.LATEX_CONVERTER_PLUGIN_NAME}ExtractForm"
      action="{url op="extractExecute" submissionId=$submissionId stageId=$stageId fileStage=$fileStage submissionFileId=$submissionFileId archiveType=$archiveType}">

    {csrf}

    {fbvFormArea id="{$smarty.const.LATEX_CONVERTER_PLUGIN_NAME}ExtractFormArea"}

    {fbvFormSection}
        <select id="{$latexConverterSelectedFilenameKey}" name="{$latexConverterSelectedFilenameKey}" required>
            <option value="" selected disabled>{translate key="plugins.generic.latexConverter.modal.extract.label"}...
            </option>
            {foreach from=$filenames key=key item=value}
                <option value="{$value}">{$value}</option>
            {/foreach}
        </select>
        <label class="sub_label"
               for="{$latexConverterSelectedFilenameKey}">{translate key="plugins.generic.latexConverter.modal.extract.label"}</label>
    {/fbvFormSection}

    {/fbvFormArea}

    {fbvFormButtons submitText="plugins.generic.latexConverter.modal.extract.button"}

</form>
