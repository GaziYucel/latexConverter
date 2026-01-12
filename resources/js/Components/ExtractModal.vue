<template>
	<PkpSideModalBody>
		<template #title>
			{{
				t("plugins.generic.latexConverter.modal.extract.header",
					{archiveName: localize(currentSubmissionFile.name)})
			}}
		</template>
		<PkpSideModalLayoutBasic>
			<div>
				{{ t("plugins.generic.latexConverter.modal.extract.title") }}
			</div>
			<div> &nbsp;</div>
			<div>
				<select id="select-file" v-model="selectedFile" class="select-file">
					<option value="" disabled>...</option>
					<option v-for="(item, index) in fileList" :key="index" :value="item">
						{{ item }}
					</option>
				</select>
			</div>
			<div> &nbsp;</div>
			<div>
				<PkpButton @click="handleSubmit" :isDisabled="!selectedFile">
					{{ t('plugins.generic.latexConverter.modal.extract.button') }}
				</PkpButton> &nbsp;
				<PkpButton @click="handleCancel">
					{{ t('common.cancel') }}
				</PkpButton>
			</div>
		</PkpSideModalLayoutBasic>
	</PkpSideModalBody>
</template>

<script setup>
import {inject, onMounted, ref} from 'vue';

const {useLocalize} = pkp.modules.useLocalize;
const {useFetch} = pkp.modules.useFetch;
const {t, localize} = useLocalize();
const closeModal = inject("closeModal");

const props = defineProps({
	currentSubmissionFile: {type: Object, required: true},
	apiUrl: {type: String, required: true}
});
const {currentSubmissionFile, apiUrl} = props;
const emit = defineEmits(['close']);

const selectedFile = ref('');
const fileList = ref('');

/** Fetch file list */
const handleFetch = async () => {
	const {fetch, data} = useFetch(
		`${apiUrl}/listFiles`,
		{
			method: 'GET',
			headers: {
				'Content-Type': 'application/json',
				'X-Csrf-Token': pkp.currentUser.csrfToken,
			}
		});
	await fetch().then(() => {
		fileList.value = data.value;
	});
}
onMounted(() => {
	handleFetch();
});

/** Submit selected file */
const handleSubmit = async () => {
	const {fetch, data} = useFetch(
		`${apiUrl}/extractFiles`,
		{
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-Csrf-Token': pkp.currentUser.csrfToken,
			},
			body: {
				selectedFile: selectedFile.value,
			}
		});
	await fetch().then(() => {
		fileList.value = data.value;
	});
	emit('close');
	closeModal();
}

/** Cancel and close sideModal */
function handleCancel() {
	emit('close');
	closeModal();
}

/* remove before production */
const {PkpButton, PkpSideModalBody, PkpSideModalLayoutBasic} = pkp.registry.getAllComponents();
</script>

<style scoped>
.select-file {
	width: 20em;
	display: block;
	padding: 0 1em;
	height: 2.5rem;
	background-color: #fff;
	font-size: 0.875rem;
	line-height: 2.5rem;
	border: 1px solid #bbb;
	border-radius: 2px;
}
</style>
