<?php
/**
 * @file plugins/generic/latexConverter/classes/Models/Log.inc.php
 *
 * Copyright (c) 2023+ TIB Hannover
 * Copyright (c) 2023+ Gazi Yucel
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Log
 * @ingroup plugins_generic_latexconverter
 *
 * @brief Logging helper class
 */

namespace TIBHannover\LatexConverter\Models;

use Config;

class Log
{
    /**
     * Log file path
     * @var string
     */
    protected string $logFilePath = '';

    function __construct()
    {
        $this->logFilePath = Config::getVar('files', 'files_dir') . '/' . LATEX_CONVERTER_PLUGIN_NAME . '.log';
    }

    /**
     * Write a message with specified level to log
     * @param  $message string Message to write
     * @param  $level   string Error level to add to message
     * @return void
     */
    protected function writeLog(string $message, string $level): void
    {
        $fineStamp = date('Y-m-d H:i:s') . substr(microtime(), 1, 4);
        error_log("$fineStamp $level $message\n", 3, $this->logFilePath);
    }

    /**
     * Log error message to log file
     * @param $message
     * @return void
     */
    public function logError($message): void
    {
        $this->writeLog($message, 'ERROR');
    }
}
