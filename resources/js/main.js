/**
 * @file plugins/generic/latexConverter/resources/js/main.js
 *
 * Copyright (c) 2021-2025 TIB Hannover
 * Copyright (c) 2021-2025 Gazi Yücel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Vite main file
 */

import ExtractModal from './Components/ExtractModal.vue';

pkp.registry.registerComponent('ExtractModal', ExtractModal);

pkp.registry.storeExtend(
	'fileManager_PRODUCTION_READY_FILES',
	(piniaContext) => {
		const dashboardStore = pkp.registry.getPiniaStore('dashboard');
		const fileStore = piniaContext.store;

		// Editorial dashboard and production stage only
		if (dashboardStore.dashboardPage !== 'editorialDashboard'
			|| fileStore.props.submissionStageId !== pkp.const.WORKFLOW_STAGE_ID_PRODUCTION) {
			return;
		}

		const {useModal} = pkp.modules.useModal;
		const {useLocalize} = pkp.modules.useLocalize;
		const {useUrl} = pkp.modules.useUrl;
		const {useFetch} = pkp.modules.useFetch;
		const {t, localize} = useLocalize();
		const {useDataChanged} = pkp.modules.useDataChanged;
		const {triggerDataChange} = useDataChanged();
		const {openSideModal, openDialog} = useModal();

		function dataUpdateCallback() {
			triggerDataChange();
		}

		fileStore.extender.extendFn('getItemActions', (originalResult, args) => {
			let newResult = originalResult;
			const {apiUrl} = useUrl(`submissions/latexConverter/${args.file.id}`);

			// zip archive
			if (localize(args.file.name).endsWith('.zip')) {
				newResult.push({
					label: t('plugins.generic.latexConverter.button.extract'),
					name: 'extractAction',
					icon: 'FileZip',
					actionFn: ({file}) => {
						openSideModal(ExtractModal, {
							currentSubmissionFile: file,
							apiUrl: apiUrl.value,
							onClose: dataUpdateCallback,
						});
					},
				});
			}

			// tex file
			if (localize(args.file.name).endsWith('.tex')) {
				newResult.push({
					label: t('plugins.generic.latexConverter.button.convert'),
					name: 'convertAction',
					icon: 'FileText',
					actionFn: ({file}) => {
						openDialog({
							title: t('plugins.generic.latexConverter.button.convert'),
							message: t('plugins.generic.latexConverter.button.message', {
								fileName: localize(file.name),
							}),
							actions: [
								{
									label: 'Yes',
									isPrimary: true,
									callback: async (close) => {
										close();
										const {fetch} = useFetch(`${apiUrl.value}/convertTex`, {
											method: 'GET',
											headers: {
												'Content-Type': 'application/json',
												'X-Csrf-Token': pkp.currentUser.csrfToken,
											},
										});
										await fetch().then(() => {
											dataUpdateCallback();
										});
									},
								},
								{
									label: 'No',
									isWarnable: true,
									callback: (close) => {
										close();
									},
								},
							],
						});
					},
				});
			}

			return [...newResult];
		});
	}
);
