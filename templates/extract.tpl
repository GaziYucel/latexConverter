{**
 * templates/extract.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Select main file page
 *}
<script type="text/javascript">

    $(function() {ldelim}
        $('#latexConverter_extractForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
        {rdelim})

</script>

<form class="pkp_form"
      id="latexConverter_extractForm" method="post"
      action="{url op="extractAfterFileSelected"
            submissionId=$submissionId stageId=$stageId fileStage=$fileStage
            submissionFileId=$submissionFileId archiveType=$archiveType}">

    {csrf}

    {fbvFormArea id="latexConverter_extractFormArea"}

    {fbvFormSection}

    <select id="{$latexConverterSelectedFilenameKey}" name="{$latexConverterSelectedFilenameKey}" required>
        <option value="" selected disabled>{translate key="plugins.generic.latexConverter.modal.extract.label"}...</option>
        {foreach from=$filenames key=key item=value}
            <option value="{$value}">{$value}</option>
        {/foreach}
    </select>
    <label class="sub_label" for="{$latexConverterSelectedFilenameKey}">{translate key="plugins.generic.latexConverter.modal.extract.label"}</label>

    {/fbvFormSection}

    {/fbvFormArea}

    {fbvFormButtons submitText="plugins.generic.latexConverter.modal.extract.button"}

</form>
