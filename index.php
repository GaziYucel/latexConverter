<?php
/**
 * @file plugins/generic/latexConverter/index.php
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class latexConverter
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Wrapper for the latexConverter plugin.
 */

require_once('LatexConverterPlugin.php');

return new LatexConverterPlugin();
