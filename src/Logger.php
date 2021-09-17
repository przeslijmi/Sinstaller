<?php

declare(strict_types=1);

namespace Przeslijmi\Sinstaller;

use Przeslijmi\Sinstaller\Installer;
use Throwable;

/**
 * Logs information about installation process and send it to CLI.
 *
 * See README.md for more info.
 */
class Logger
{

    /**
     * Parrent installer object.
     *
     * @var Installer
     */
    private $installer;

    /**
     * Do echo all logs instantly.
     *
     * @var boolean
     */
    private $doEcho = true;

    /**
     * Log history,
     *
     * @var string
     */
    private $log = '';

    /**
     * Constructor.
     *
     * @param Installer $installer Parent Installer object.
     */
    public function __construct(Installer $installer)
    {

        $this->installer = $installer;
    }

    /**
     * Disable echo'ing logs to CLI.
     *
     * @return void
     */
    public function disableEcho(): void
    {

        $this->doEcho = false;
    }

    /**
     * Enable echo'ing logs to CLI.
     *
     * @return void
     */
    public function enableEcho(): void
    {

        $this->doEcho = true;
    }

    /**
     * Returns information if echo is enabled.
     *
     * @return boolean
     */
    public function isEchoEnabled(): bool
    {

        return $this->doEcho;
    }

    /**
     * Deliver contents (history) of what has been logged.
     *
     * @return string
     */
    public function getLog(): string
    {

        return $this->log;
    }

    /**
     * Shows welcome text and version number.
     *
     * @return void
     *
     * @phpcs:disable Squiz.PHP.DiscouragedFunctions
     */
    public function sayHello(): void
    {

        // Lvd.
        $version = json_decode(file_get_contents('composer.json'))->version;

        // Saying.
        $this->logLn('[NL][NL]Hello to Sinstaller by @przeslijmi [' . $version . ']');
        $this->logLn('See https://github.com/przeslijmi/Sinstaller for help[NL][NL]');
    }

    /**
     * Log line.
     *
     * @param string $text Text to be logged.
     *
     * @return void
     *
     * @phpcs:disable Squiz.PHP.DiscouragedFunctions
     */
    public function logLn(string $text): void
    {

        // Make text final.
        $text = $this->replace($text) . "\n";

        // Save log.
        $this->log .= $text;

        // Echo log.
        if ($this->doEcho === true) {
            echo $text;
        }
    }

    /**
     * Log text (without a new line at the end).
     *
     * @param string $text Text to be logged.
     *
     * @return void
     *
     * @phpcs:disable Squiz.PHP.DiscouragedFunctions
     */
    public function log(string $text): void
    {

        // Make text final.
        $text = $this->replace($text);

        // Save log.
        $this->log .= $text;

        // Echo log.
        if ($this->doEcho === true) {
            echo $text;
        }
    }

    /**
     * Log information about failure, throwable that happened.
     *
     * @param Throwable $thr Throwable that happened.
     *
     * @throws CriticalException As standard behaviour.
     * @return void
     *
     * @phpcs:disable Squiz.PHP.DiscouragedFunctions
     */
    public function logFailure(Throwable $thr): void
    {

        // Show.
        $this->logLn('failed, cause ' . $thr->getMessage() . ' !');

        // Throw critical.
        if ($this->installer->isCriticalStopEnabled() === true) {
            $this->logLn('[NL]CRITICAL STOP[NL][NL]');
            throw new CriticalException('Critical stop', 0, $thr);
        }
    }

    /**
     * Log information about successfull end.
     *
     * @return void
     *
     * @phpcs:disable Squiz.PHP.DiscouragedFunctions
     */
    public function logSuccess(): void
    {

        $this->logLn('succeeded');
    }

    /**
     * Replaces text for `->log` and `->logLn`.
     *
     * See README.md for more info.
     *
     * @param string $text Text to be replaced.
     *
     * @return string
     */
    private function replace(string $text): string
    {

        // Make replacements.
        $text = str_replace('[LN]', "\n", $text);
        $text = str_replace('[NL]', "\n", $text);
        $text = str_replace('[currDir]', getcwd(), $text);
        $text = str_replace('[cwd]', getcwd(), $text);

        return $text;
    }
}
