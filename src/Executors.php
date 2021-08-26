<?php

declare(strict_types=1);

namespace Przeslijmi\Sinstaller;

use Przeslijmi\Sinstaller\Exception;
use Throwable;

/**
 * Private methods warehouse abstract class for Installer.
 */
abstract class Executors
{

    /**
     * Make directory (serves recursiveness) as an executor protected method.
     *
     * @param string $dir Uri to new dir.
     *
     * @throws Exception When job can not be done.
     * @return void
     */
    protected function execMakeDir(string $dir): void
    {

        // Lvd.
        $path = '';
        $dir  = rtrim(str_replace('\\', '/', $dir), '/');

        // Go deeper every time.
        foreach (explode('/', $dir) as $subDir) {

            // Enlarge path.
            $path .= $subDir . '/';
            $test  = rtrim($path, '/');

            // Throw.
            if (file_exists($test) === true && is_dir($test) === false) {
                throw new Exception('path `' . $test . '` already exists and is not a dir');
            }

            // Create dir.
            if (file_exists($test) === false) {
                mkdir($path);
            }
        }
    }

    /**
     * Cleans directory (serves recursiveness) as an executor protected method.
     *
     * @param string $dir Uri to dir that is to be deleted.
     *
     * @return void
     */
    protected function execEmptyDirRecursively(string $dir): void
    {

        // Lvd.
        $dir       = rtrim(str_replace('\\', '/', $dir), '/');
        $filesDirs = array_reverse($this->getElementsRecursively($dir));

        // Empty.
        foreach ($filesDirs as $element) {

            if (is_file($element) === true) {
                unlink($element);
            } else {
                rmdir($element);
            }
        }
    }

    /**
     * Reades dir recursively and return all found files and directories.
     *
     * @param string $dir Dir in which to search (will go recurisve).
     *
     * @throws Exception When job can not be done.
     * @return array
     */
    protected function getElementsRecursively(string $dir): array
    {

        // Lvd.
        $dir    = rtrim(str_replace('\\', '/', $dir), '/') . '/';
        $result = [];

        // Throw.
        if (file_exists($dir) === false) {
            throw new Exception('dir `' . $dir . '` does not exists');
        }

        // Scan all.
        foreach (glob($dir . '*') as $element) {

            // If this is dir - go deeper, otherwise - only add.
            if (is_dir($element) === true) {

                // Add element.
                $result[] = $element . '/';

                // Go deeper.
                $result = array_merge($result, $this->getElementsRecursively($element));
            } else {

                // Add element.
                $result[] = $element;
            }
        }

        return $result;
    }
}
