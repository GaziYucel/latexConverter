<?php

/**
 * @file plugins/generic/latexConverter/classes/PluginApiHandler.php
 *
 * Copyright (c) 2021-2025 TIB Hannover
 * Copyright (c) 2021-2025 Gazi Yücel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PluginApiHandler
 *
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Api Handler for the plugin LatexConverter
 */

namespace APP\plugins\generic\latexConverter\classes;

use APP\facades\Repo;
use APP\plugins\generic\latexConverter\classes\Helpers\ZipHelper;
use APP\plugins\generic\latexConverter\classes\Models\Convert;
use APP\plugins\generic\latexConverter\classes\Models\Extract;
use APP\plugins\generic\latexConverter\LatexConverterPlugin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\Response;
use PKP\core\PKPBaseController;
use PKP\file\PrivateFileManager;
use PKP\handler\APIHandler;
use PKP\plugins\Hook;

class PluginApiHandler
{
    public LatexConverterPlugin $plugin;

    public function __construct(LatexConverterPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * This allows to add a route on the fly without defining an api controller.
     * Hook: APIHandler::endpoints::submissions
     * e.g. api/v1/submissions/latexConverter/{submission_file_id}/__action__
     */
    public function addRoute(string $hookName, PKPBaseController $apiController, APIHandler $apiHandler): bool
    {
        $apiHandler->addRoute(
            'GET',
            "latexConverter/{submission_file_id}/listFiles",
            fn(IlluminateRequest $request): JsonResponse => $this->listFiles($request),
            'latexConverter.listFiles',
            Constants::AUTHORISED_ROLES
        );

        $apiHandler->addRoute(
            'POST',
            "latexConverter/{submission_file_id}/extractFiles",
            fn(IlluminateRequest $request): JsonResponse => $this->extractFiles($request),
            'latexConverter.extractFiles',
            Constants::AUTHORISED_ROLES
        );

        $apiHandler->addRoute(
            'GET',
            "latexConverter/{submission_file_id}/convertTex",
            fn(IlluminateRequest $request): JsonResponse => $this->convertTex($request),
            'latexConverter.convertTex',
            Constants::AUTHORISED_ROLES
        );

        return Hook::CONTINUE;
    }

    /**
     * Extracts file and adds to files list.
     */
    private function listFiles(IlluminateRequest $illuminateRequest): JsonResponse
    {
        $submissionFileId = (int)$illuminateRequest->route('submission_file_id');
        $submissionFile = Repo::submissionFile()->get($submissionFileId);
        if (!$submissionFile) {
            return response()->json(
                ['error' => __('api.404.resourceNotFound')],
                Response::HTTP_NOT_FOUND
            );
        }

        $fileManager = new PrivateFileManager();
        if (!ZipHelper::isZipArchive($fileManager->getBasePath() . DIRECTORY_SEPARATOR . $submissionFile->getData('path'))) {
            return response()->json(
                ['error' => __('plugins.generic.latexConverter.notification.noValidZipFile')],
                Response::HTTP_NOT_FOUND
            );
        }

        $extract = new Extract($this->plugin);

        return response()->json(
            $extract->listFiles($submissionFile),
            Response::HTTP_OK
        );
    }

    /**
     * Create article from selected main file.
     */
    private function extractFiles(IlluminateRequest $illuminateRequest): JsonResponse
    {
        $selectedFile = (string)$illuminateRequest->input('selectedFile');
        if (empty($selectedFile)) {
            return response()->json(
                ['error' => __('plugins.generic.latexConverter.notification.noFileSelected')],
                Response::HTTP_NOT_FOUND
            );
        }

        $submissionFileId = (int)$illuminateRequest->route('submission_file_id');
        $submissionFile = Repo::submissionFile()->get($submissionFileId);
        if (!$submissionFile) {
            return response()->json(
                ['error' => __('api.404.resourceNotFound')],
                Response::HTTP_NOT_FOUND
            );
        }

        $extract = new Extract($this->plugin);

        return response()->json(
            $extract->extractFiles($submissionFile, $selectedFile),
            Response::HTTP_OK
        );
    }

    /**
     * Converts LaTex file to pdf.
     */
    private function convertTex(IlluminateRequest $illuminateRequest): JsonResponse
    {
        $submissionFile = Repo::submissionFile()->get((int)$illuminateRequest->route('submission_file_id'));
        if (!$submissionFile) {
            return response()->json(
                ['error' => __('api.404.resourceNotFound')],
                Response::HTTP_NOT_FOUND
            );
        }

        $latexExec = $this->plugin->getSetting(
            $this->plugin->getRequest()->getContext()->getId(),
            Constants::SETTING_LATEX_PATH_EXECUTABLE
        );
        if (empty($latexExec) || !file_exists($latexExec)) {
            return response()->json(
                ['error' => __('plugins.generic.latexConverter.executable.notConfigured')],
                Response::HTTP_NOT_FOUND
            );
        }

        $convert = new Convert($this->plugin, $submissionFile);

        $submission = Repo::submission()->get($submissionFile->getData('submissionId'));

        return response()->json(
//            [
//                "submissionFile" => $submissionFile->_data,
//                "name" => $submissionFile->getData('name')[$submission->getData('locale')]
//            ],
             $convert->process(),
            Response::HTTP_OK
        );
    }
}
