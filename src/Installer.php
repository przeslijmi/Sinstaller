<?php declare(strict_types=1);

namespace Przeslijmi\Sinstaller;

use stdClass;

/**
 * Simple installing class.
 */
class Installer
{

    /**
     * Contents of composer.
     *
     * @var stdClass
     */
    private $composer;

    /**
     * Setter for composer contents.
     *
     * @param string $composerUri Uri of the composer.
     */
    public function setComposer(string $composerUri) : void
    {

        $this->composer = json_decode(file_get_contents($composerUri));
    }

    /**
     * Install file.
     *
     * @param string $vendorApp   From which app.
     * @param string $source      Which file.
     * @param string $destination Where to put.
     *
     * @return void
     */
    public function file(
        string $vendorApp,
        string $source,
        string $destination,
        ?callable $transform = null
    ) : void {

        // Lvd.
        $source      = str_replace('\\', '/', $source);
        $destination = str_replace('\\', '/', $destination);

        // Recalc source.
        $source = $this->getVendorAppUri($vendorApp) . $source;

        // Get contents.
        $contents = file_get_contents($source);

        if ($transform !== null) {
            $contents = $transform($contents);
        }

        file_put_contents($destination, $contents);
    }

    /**
     * Install director.
     *
     * @param string $vendorApp   From which app.
     * @param string $source      Which directory.
     * @param string $destination Where to put.
     *
     * @return void
     */
    public function dir(string $vendorApp, string $source, string $destination) : void
    {

        // Lvd.
        $source      = rtrim(str_replace('\\', '/', $source), '/') . '/';
        $destination = rtrim(str_replace('\\', '/', $destination), '/') . '/';

        // Recalc source.
        $source = $this->getVendorAppUri($vendorApp) . $source;

        // Empty destionation dir.
        if (file_exists($destination) === false) {
            $this->makeDir($destination);
        } else {
            $this->emptyDir($destination);
        }

        // Copy to destination fir.
        foreach ($this->getElementsRecursively($source) as $elSource) {

            // Calc real destination uri for this element.
            $elDestination = $destination . substr($elSource, ( strrpos($elSource, $source) + strlen($source)));

            // Copy file.
            if (is_file($elSource) === true) {
                copy($elSource, $elDestination);
            } else {
                mkdir($elDestination);
            }
        }
    }

    public function echo(string $what) : void
    {

        if ($what === 'cwd' || $what === 'currDir') {
            echo getcwd();
        }
    }

    /**
     * Getter for vendor-app uri.
     *
     * @param string $vendorApp Vendor-app for which uri has to be delivered.
     *
     * @return string
     */
    private function getVendorAppUri(string $vendorApp) : string
    {

        // Make sure vendor-app ends with backslash.
        $vendorApp = rtrim($vendorApp, '\\') . '\\';

        return $this->composer->autoload->{'psr-4'}->{$vendorApp};
    }

    /**
     * Reades dir recursively and return all found files and directories.
     *
     * @param string $dir Dir in which to search (will go recurisve).
     *
     * @return array
     */
    private function getElementsRecursively(string $dir) : array
    {

        // Lvd.
        $dir    = rtrim(str_replace('\\', '/', $dir), '/') . '/';
        $result = [];

        // Scan all.
        foreach (glob($dir . '*') as $element) {

            // Add element.
            $result[] = $element;

            // If this is dir - go deeper.
            if (is_dir($element) === true) {
                $result = array_merge($result, $this->getElementsRecursively($element));
            }
        }

        return $result;
    }

    public function setFileContentsIfe(string $uri, string $contents) : void
    {

        if (file_exists($uri) === false) {
            return;
        }

        file_put_contents($uri, $contents);
    }

    public function makeDir(string $dir) : void
    {

        $path = '';
        $dir  = rtrim(str_replace('\\', '/', $dir), '/');

        foreach (explode('/', $dir) as $subDir) {

            $path .= $subDir . '/';

            if (file_exists($path) === false) {
                mkdir($path);
            }
        }
    }

    public function emptyDir(string $dir) : void
    {

        // Lvd.
        $dir = rtrim(str_replace('\\', '/', $dir), '/');

        // Empty.
        foreach (array_reverse($this->getElementsRecursively($dir)) as $element) {
            if (is_file($element) === true) {
                unlink($element);
            } else {
                rmdir($element);
            }
        }
    }
}
