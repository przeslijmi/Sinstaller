<?php

declare(strict_types=1);

namespace Przeslijmi\Sinstaller;

use Przeslijmi\Sinstaller\Exception;
use Przeslijmi\Sinstaller\Executors;
use Przeslijmi\Sinstaller\Logger;
use stdClass;
use Throwable;

/**
 * Simple installing class.
 *
 * See README.md for more info.
 */
class Installer extends Executors
{

    /**
     * Logger object initialised on creation.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Contents of composer.
     *
     * @var stdClass
     */
    protected $composer;

    /**
     * When set to true critical stop will be fired upon error - ending work of programme.
     *
     * @var boolean
     */
    protected $criticalStop = true;

    /**
     * Array of user inputs.
     *
     * @var array[]
     */
    protected $userInputs = [];

    /**
     * Constructor.
     *
     * @param boolean $doEchoLog Optional true. Set to false to disable echo'ing.
     */
    public function __construct(bool $doEchoLog = true)
    {

        // Instantitate.
        $this->logger = new Logger($this);

        // Disable echo if needed.
        if ($doEchoLog === false) {
            $this->logger->disableEcho();
        }

        // Say hello.
        $this->logger->sayHello();
    }

    /**
     * Disable shutting code on error.
     *
     * @return void
     */
    public function disableCriticalStop(): void
    {

        $this->criticalStop = false;
    }

    /**
     * Enable shutting code on error.
     *
     * @return void
     */
    public function enableCriticalStop(): void
    {

        $this->criticalStop = true;
    }

    /**
     * Checks if critical stop on error is enabled.
     *
     * @return boolean
     */
    public function isCriticalStopEnabled(): bool
    {

        return $this->criticalStop;
    }

    /**
     * Setter for composer contents - composer is needed to calc source app dirs.
     *
     * @param string $composerUri Uri of the composer.
     *
     * @throws Exception When job can not be done.
     * @return void
     */
    public function setComposer(string $composerUri): void
    {

        // Log.
        $this->logger->log(' => will use composer: ' . $composerUri . ' ... ');

        // Perform.
        try {

            // Check.
            if (file_exists($composerUri) === false || is_file($composerUri) === false) {
                throw new Exception('composer file not found or uri leads not to a file');
            }

            // Parse.
            $this->composer = json_decode(file_get_contents($composerUri));

            // Check.
            if (empty($this->composer) === true) {
                throw new Exception('file corrupted');
            }

            // End.
            $this->logger->logSuccess();
        } catch (Throwable $thr) {

            // End.
            $this->logger->logFailure($thr);
        }//end try
    }

    /**
     * Asks user to give some information. If validator is given asks in loop until proper value shows up.
     *
     * @param string        $key       Key of data - to be used in `->getUserInput()` method.
     * @param string        $for       Full contents of prompt ('Give user name:').
     * @param callable|null $validator Optional. Function that validates given input.
     * @param string|null   $onFailure Optional. Extra text that will show up on inproper value given.
     *
     * @return string
     */
    public function askFor(
        string $key,
        string $for,
        ?callable $validator = null,
        ?string $onFailure = null
    ): string {

        // Call question and serve response.
        $this->logger->logLn('');
        $input = (string) readline($for);

        // Validate - if validation is needed - and ask again if needed.
        if ($validator !== null && $validator($input) === false) {

            // Show comment if comment was defined.
            if (empty($onFailure) === false) {
                $this->logger->log($onFailure);
            }

            // Ask again.
            $input = $this->askFor(...func_get_args());
        }

        // Save response.
        $this->userInputs[$key] = $input;

        // Return response.
        return $this->userInputs[$key];
    }

    /**
     * Get user input created by `->askFor()` method.
     *
     * @param string $key Key of data used as a first param when asking for data.
     *
     * @return null|string
     */
    public function getUserInput(string $key): ?string
    {

        return ( $this->userInputs[$key] ?? null );
    }

    /**
     * Install file.
     *
     * @param string   $vendorApp   From which app.
     * @param string   $source      Which file.
     * @param string   $destination Where to put.
     * @param callable $transform   Php function to run on every installed file.
     *
     * @throws Exception When job can not be done.
     * @return void
     */
    public function file(
        string $vendorApp,
        string $source,
        string $destination,
        ?callable $transform = null
    ): void {

        // Log.
        $this->logger->log(' => will install file: ' . $source . ' from app ' . $vendorApp . ' ... ');

        // Perform.
        try {

            // Check emptyness.
            if (empty($source) === true || empty($destination) === true) {
                throw new Exception('nor source, nor destination may be empty');
            }

            // Lvd.
            $source      = str_replace('\\', '/', $source);
            $destination = str_replace('\\', '/', $destination);

            // Recalc source.
            $source = $this->getVendorAppUri($vendorApp) . $source;

            // Check.
            if (file_exists($source) === false) {
                throw new Exception('source file not found');
            }

            // Get contents.
            $contents = file_get_contents($source);

            // Call transformations.
            if ($transform !== null) {
                $contents = $transform($contents, $this);
            }

            // Check dir.
            if (strpos($destination, '/') !== false && file_exists(dirname($destination)) === false) {
                throw new Exception('destination dir does not exists');
            }

            // Save file.
            file_put_contents($destination, $contents);

            // End.
            $this->logger->logSuccess();

        } catch (Throwable $thr) {

            // End.
            $this->logger->logFailure($thr);
        }//end try
    }

    /**
     * Install file - only if destination file does not exists.
     *
     * @param string   $vendorApp   From which app.
     * @param string   $source      Which file.
     * @param string   $destination Where to put.
     * @param callable $transform   Php function to run on every installed file.
     *
     * @return void
     */
    public function fileIfne(
        string $vendorApp,
        string $source,
        string $destination,
        ?callable $transform = null
    ): void {

        // Lvd.
        $destination = str_replace('\\', '/', $destination);

        // Continue only if not exists.
        if (file_exists($destination) === false) {

            // Perform.
            $this->file($vendorApp, $source, $destination, $transform);
            return;
        }

        // Log to inform why nothing was done.
        $this->logger->logLn(
            ' => will NOT install file: ' . $source . ' from app ' . $vendorApp . ' cause file already exists'
        );
    }

    /**
     * Install directory.
     *
     * @param string $vendorApp   From which app.
     * @param string $source      Which directory.
     * @param string $destination Where to put.
     *
     * @throws Exception When job can not be done.
     * @return void
     */
    public function dir(string $vendorApp, string $source, string $destination): void
    {

        // Log.
        $this->logger->log(' => will install dir: ' . $source . ' from app ' . $vendorApp . ' ... ');

        // Perform.
        try {

            // Check emptyness.
            if (empty($source) === true || empty($destination) === true) {
                throw new Exception('nor source, nor destination may be empty - use `.` (dot) instead');
            }

            // Define source and destination.
            $source      = rtrim(str_replace('\\', '/', $source), '/') . '/';
            $destination = rtrim(str_replace('\\', '/', $destination), '/') . '/';

            // Recalc source.
            $source = $this->getVendorAppUri($vendorApp) . $source;

            // Check.
            if (file_exists($source) === false || is_dir($source) === false) {
                throw new Exception('source dir not found or is not a dir');
            }

            // Empty destionation dir.
            if (file_exists($destination) === false) {
                $this->execMakeDir($destination);
            } else {
                $this->execEmptyDirRecursively($destination);
            }

            // Copy to destination fir.
            foreach ($this->getElementsRecursively($source) as $elSource) {

                // Calc real destination uri for this element.
                $elDestination = $destination . substr($elSource, ( strrpos($elSource, $source) + strlen($source) ));

                // Copy file.
                if (is_file($elSource) === true) {
                    copy($elSource, $elDestination);
                } else {
                    mkdir($elDestination);
                }
            }

            // End.
            $this->logger->logSuccess();

        } catch (Throwable $thr) {

            // End.
            $this->logger->logFailure($thr);
        }//end try
    }

    /**
     * Getter for Logger object.
     *
     * @return Logger
     */
    public function getLogger(): Logger
    {

        return $this->logger;
    }

    /**
     * Getter for log history (contents).
     *
     * @return string
     */
    public function getLog(): string
    {

        return $this->logger->getLog();
    }

    /**
     * Sets file contents - but only if that file already exists.
     *
     * @param string $uri      File uri in which contents has to be set.
     * @param string $contents Contents of contents to be set :D .
     *
     * @throws Exception When job can not be done.
     * @return void
     */
    public function setFileContentsIfe(string $uri, string $contents): void
    {

        // Log to inform why nothing was done.
        if (file_exists($uri) === false) {
            $this->logger->logLn(' => will NOT set contents of a file: ' . $uri . ' cause file does not exists');
            return;
        }

        // Log.
        $this->logger->log(' => will set contents of a file: ' . $uri . ' ... ');

        // Perform.
        try {

            // Continue only if exists.
            if (is_file($uri) === false) {
                throw new Exception('it is not a file under given uri');
            }

            // Perform.
            file_put_contents($uri, $contents);

            // End.
            $this->logger->logSuccess();

        } catch (Throwable $thr) {

            // End.
            $this->logger->logFailure($thr);
        }
    }

    /**
     * Sets file contents - but only if that file does not exists.
     *
     * @param string $uri      File uri in which contents has to be set.
     * @param string $contents Contents of contents to be set :D .
     *
     * @throws Exception When job can not be done.
     * @return void
     */
    public function setFileContentsIfne(string $uri, string $contents): void
    {

        // Log to inform why nothing was done.
        if (file_exists($uri) === true && is_file($uri) === true) {
            $this->logger->logLn(' => will NOT set contents of a file: ' . $uri . ' cause file already exists');
            return;
        }

        // Log.
        $this->logger->log(' => will set contents of a file: ' . $uri . ' ... ');

        // Perform.
        try {

            // Continue only if exists.
            if (file_exists($uri) === true) {
                throw new Exception('uri is already taken by something other than file');
            }

            // Perform.
            file_put_contents($uri, $contents);

            // End.
            $this->logger->logSuccess();

        } catch (Throwable $thr) {

            // End.
            $this->logger->logFailure($thr);
        }
    }

    /**
     * Make directory (serves recursiveness).
     *
     * @param string $dir Uri to new dir.
     *
     * @return void
     */
    public function makeDir(string $dir): void
    {

        // Log.
        $this->logger->log(' => will create empty dir: ' . $dir . ' ... ');

        // Fast end.
        if (file_exists($dir) === true && is_dir($dir) === true) {
            $this->logger->logLn('already exists');
            return;
        }

        // Try.
        try {

            // Perform.
            $this->execMakeDir($dir);

            // End.
            $this->logger->logSuccess();
        } catch (Throwable $thr) {

            // End.
            $this->logger->logFailure($thr);
        }
    }

    /**
     * Cleans directory (serves recursiveness).
     *
     * @param string $dir Uri to dir that is to be deleted..
     *
     * @return void
     */
    public function emptyDirRecursively(string $dir): void
    {

        // Log.
        $this->logger->log(' => will recursively empty a dir: ' . $dir . ' ... ');

        // Try.
        try {

            // Perform.
            $this->execEmptyDirRecursively(...func_get_args());

            // End.
            $this->logger->logSuccess();
        } catch (Throwable $thr) {

            // End.
            $this->logger->logFailure($thr);
        }
    }

    /**
     * Getter for vendor-app uri.
     *
     * @param string $vendorApp Vendor-app for which uri has to be delivered.
     *
     * @throws Exception When job can not be done.
     * @return string
     */
    public function getVendorAppUri(string $vendorApp): string
    {

        // Throw.
        if (empty($this->composer) === true) {
            throw new Exception('composer not defined, use `->setComposer(...)`');
        }

        // Make sure vendor-app ends with backslash.
        $vendorApp = rtrim($vendorApp, '\\') . '\\';

        // Find.
        $result = ( $this->composer->autoload->{'psr-4'}->{$vendorApp} ?? '' );

        // Throw.
        if (empty($result) === true) {
            throw new Exception('app not found in composer');
        }

        return $result;
    }
}
