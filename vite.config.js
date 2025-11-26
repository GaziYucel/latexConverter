/**
 * @file plugins/generic/latexConverter/vite.config.js
 *
 * @copyright (c) 2021-2025 TIB Hannover
 * @copyright (c) 2021-2025 Gazi Yücel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Vite configuration
 */

import {resolve} from "path";
import {defineConfig} from "vite";
import vue from "@vitejs/plugin-vue";
import i18nExtractKeys from "./lib/i18nExtractKeys.vite.js";

export default defineConfig({
    target: "es2016",
    plugins: [i18nExtractKeys(), vue()],
    build: {
        lib: {
            entry: resolve(__dirname, "resources/js/main.js"),
            name: "LatexConverterPlugin",
            fileName: "build",
            formats: ["iife"],
        },
        outDir: resolve(__dirname, "public/build"),
        rollupOptions: {
            external: ["vue"],
            output: {
                globals: {
                    vue: "pkp.modules.vue",
                },
            },
        },
    }
});
