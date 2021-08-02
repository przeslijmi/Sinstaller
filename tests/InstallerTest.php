<?php

declare(strict_types=1);

namespace Przeslijmi\Installer;

use PHPUnit\Framework\TestCase;
use Przeslijmi\Sinstaller\CriticalException;
use Przeslijmi\Sinstaller\Exception;
use Przeslijmi\Sinstaller\Installer;
use Przeslijmi\Sinstaller\Logger;

/**
 * Tests Installer class.
 */
final class InstallerTest extends TestCase
{

    /**
     * Tests composer usage.
     *
     * In order to test properly - you have to answer the questions in order:
     * - `5`
     * - `10`
     * - `15`
     *
     * @return void
     */
    public function testProperFullJob(): void
    {

        // Prestart installer to make sure that working directory does not exists.
        $preIns = new Installer(false);
        $preIns->makeDir('resources/testingProbe/');
        $preIns->emptyDirRecursively('resources/testingProbe/');
        rmdir('resources/testingProbe/');

        // Start capturing real output.
        ob_start();

        // Define installer.
        $ins = new Installer();
        $ins->setComposer('resources/forTesting/testComposer.json');

        // Ask simple question.
        $response = $ins->askFor('test', 'Test question - write `5` and hit Enter: ');
        $this->assertEquals('5', $response);
        $this->assertEquals('5', $ins->getUserInput('test'));
        $this->assertEquals(null, $ins->getUserInput('sthNonexisting'));

        // Ask question with validation.
        $response = $ins->askFor(
            'number',
            'Test question - write `10` and hit Enter: ',
            function (string $input): bool {
                return ( (int) $input === 15 );
            },
            'Just joking - write `15`!'
        );
        $this->assertEquals('15', $response);
        $this->assertEquals('15', $ins->getUserInput('number'));

        // Make HDD operations.
        $ins->makeDir('resources/testingProbe/src/');
        $ins->makeDir('resources/testingProbe/src/');
        $ins->fileIfne('Vendor\\App', 'Exception.php', 'resources/testingProbe/Exception.php');
        $ins->fileIfne('Vendor\\App', 'Exception.php', 'resources/testingProbe/Exception.php');
        $ins->dir('Vendor\\App', '../', 'resources/testingProbe/src/aaa/');
        $ins->dir('Vendor\\App', '../', 'resources/testingProbe/src/aaa/');
        $ins->setFileContentsIfe('resources/testingProbe/src/aaa/src/NonexistingFile.php', 'aa');
        $ins->setFileContentsIfe('resources/testingProbe/src/aaa/src/Exception.php', 'aa');
        $ins->setFileContentsIfne('resources/testingProbe/src/aaa/src/Exception.php', 'aa');
        $ins->setFileContentsIfne('resources/testingProbe/src/aaa/src/NonexistingFile.php', 'aa');
        $ins->emptyDirRecursively('resources/testingProbe/');

        // Get values.
        $actual    = ob_get_clean();
        $expected  = $this->getHelloText(true) . 'resources/forTesting/testComposer.json ... succeeded' . "\n";
        $expected .= "\n\n" . 'Just joking - write `15`!' . "\n";
        $expected .= ' => will create empty dir: resources/testingProbe/src/ ... succeeded' . "\n";
        $expected .= ' => will create empty dir: resources/testingProbe/src/ ... already exists' . "\n";
        $expected .= ' => will install file: Exception.php from app Vendor\App ... succeeded' . "\n";
        $expected .= ' => will NOT install file: Exception.php from app Vendor\App cause file already exists' . "\n";
        $expected .= ' => will install dir: ../ from app Vendor\App ... succeeded' . "\n";
        $expected .= ' => will install dir: ../ from app Vendor\App ... succeeded' . "\n";
        $expected .= ' => will NOT set contents of a file: resources/testingProbe/src/aaa/src/NonexistingFile.php';
        $expected .= ' cause file does not exists' . "\n";
        $expected .= ' => will set contents of a file: resources/testingProbe/src/aaa/src/Exception.php';
        $expected .= ' ... succeeded' . "\n";
        $expected .= ' => will NOT set contents of a file: resources/testingProbe/src/aaa/src/Exception.php';
        $expected .= ' cause file already exists' . "\n";
        $expected .= ' => will set contents of a file: resources/testingProbe/src/aaa/src/NonexistingFile.php';
        $expected .= ' ... succeeded' . "\n";
        $expected .= ' => will recursively empty a dir: resources/testingProbe/ ... succeeded' . "\n";

        // Test.
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests if disabling and enabling critical stop works.
     *
     * @return void
     */
    public function testCriticalStopToggle(): void
    {

        // Perform tests.
        $ins = new Installer(false);
        $this->assertEquals(true, $ins->isCriticalStopEnabled());
        $ins->disableCriticalStop();
        $this->assertEquals(false, $ins->isCriticalStopEnabled());
        $ins->enableCriticalStop();
        $this->assertEquals(true, $ins->isCriticalStopEnabled());
        $this->assertInstanceOf(Logger::class, $ins->getLogger());
    }

    /**
     * Test if CriticalException will be thrown on error.
     *
     * @return void
     */
    public function testIfCriticalStopWillWork(): void
    {

        // Prepare.
        $this->expectException(CriticalException::class);

        // Call installer.
        $ins = new Installer(false);
        $ins->setComposer('wrong.json');
    }

    /**
     * Tests if disabling and enabling echo works.
     *
     * @return void
     */
    public function testEchoingToggle(): void
    {

        // Perform tests.
        $ins = new Installer(false);
        $this->assertEquals(false, $ins->getLogger()->isEchoEnabled());

        // Perform tests.
        $ins = new Installer();
        $this->assertEquals(true, $ins->getLogger()->isEchoEnabled());
        $ins->getLogger()->disableEcho();
        $this->assertEquals(false, $ins->getLogger()->isEchoEnabled());
        $ins->getLogger()->enableEcho();
        $this->assertEquals(true, $ins->getLogger()->isEchoEnabled());
    }

    /**
     * Tests composer usage.
     *
     * @return void
     */
    public function testComposerFails(): void
    {

        // Lvd.
        $exp = '';

        // Perform test.
        $ins = new Installer(false);
        $ins->disableCriticalStop();

        // Add composer wrong uri.
        $ins->setComposer('wrong_uri');
        $exp .= $this->getFailText(
            'will use composer: wrong_uri',
            'composer file not found or uri leads not to a file',
        );

        // Add composer that is not JSON.
        $ins->setComposer('resources/forTesting/installation.php');
        $exp .= $this->getFailText(
            'will use composer: resources/forTesting/installation.php',
            'file corrupted',
        );

        // Install file form App - but composer is not installed.
        $ins->file('Vendor\App', 'a', 'b');
        $exp .= $this->getFailText(
            'will install file: a from app Vendor\App',
            'composer not defined, use `->setComposer(...)`',
        );

        // Get values.
        $actual   = $ins->getLog();
        $expected = $this->getHelloText() . $exp;

        // Test.
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests composer usage.
     *
     * @return void
     */
    public function testOtherFails(): void
    {

        // Lvd.
        $exp = '';

        // Perform test.
        $ins = new Installer(false);
        $ins->disableCriticalStop();
        $ins->setComposer('resources/forTesting/testComposer.json');

        // Instal empty files.
        $ins->file('Vendor\App', '', '');
        $exp .= $this->getFailText(
            'will install file:  from app Vendor\App',
            'nor source, nor destination may be empty',
        );

        // Instal file from nonexisting app.
        $ins->file('Vendor\NonestingApp', 'a', 'b');
        $exp .= $this->getFailText(
            'will install file: a from app Vendor\NonestingApp',
            'app not found in composer',
        );

        // Instal nonexisting file.
        $ins->file('Vendor\App', 'a', 'b');
        $exp .= $this->getFailText(
            'will install file: a from app Vendor\App',
            'source file not found',
        );

        // Instal with failing Exception in tranformation method.
        $ins->file('Vendor\App', 'CriticalException.php', 'resources/testingProbe/Somewhere.php', function ($contents) {
            if ($contents === $contents) {
                throw new Exception('something in function went wrong');
            }
        });
        $exp .= $this->getFailText(
            'will install file: CriticalException.php from app Vendor\App',
            'something in function went wrong',
        );

        // Instal nonexisting file.
        $ins->file('Vendor\App', 'CriticalException.php', 'nonExistingDir/Somewhere.php');
        $exp .= $this->getFailText(
            'will install file: CriticalException.php from app Vendor\App',
            'destination dir does not exists',
        );

        // Instal empty dir.
        $ins->dir('Vendor\App', '', '');
        $exp .= $this->getFailText(
            'will install dir:  from app Vendor\App',
            'nor source, nor destination may be empty - use `.` (dot) instead',
        );

        // Instal dir from nonexisting app.
        $ins->dir('Vendor\NonestingApp', 'a', 'b');
        $exp .= $this->getFailText(
            'will install dir: a from app Vendor\NonestingApp',
            'app not found in composer',
        );

        // Instal nonexisting dir.
        $ins->dir('Vendor\App', 'a', 'b');
        $exp .= $this->getFailText(
            'will install dir: a from app Vendor\App',
            'source dir not found or is not a dir',
        );

        // Set file contents to a dictionary.
        $ins->setFileContentsIfe('resources', 'contents');
        $exp .= $this->getFailText(
            'will set contents of a file: resources',
            'it is not a file under given uri',
        );

        // Set file contents to a dictionary.
        $ins->setFileContentsIfne('resources', 'contents');
        $exp .= $this->getFailText(
            'will set contents of a file: resources',
            'uri is already taken by something other than file',
        );

        // Create dir that already exists as a file.
        $ins->makeDir('resources/forTesting/installation.php');
        $exp .= $this->getFailText(
            'will create empty dir: resources/forTesting/installation.php',
            'path `resources/forTesting/installation.php` already exists and is not a dir',
        );

        // Empty dir that does not exists.
        $ins->emptyDirRecursively('resources/testingProbe/nonexistingDir/');
        $exp .= $this->getFailText(
            'will recursively empty a dir: resources/testingProbe/nonexistingDir/',
            'dir `resources/testingProbe/nonexistingDir/` does not exists',
        );

        // Get values.
        $actual    = $ins->getLog();
        $expected  = $this->getHelloText(true) . 'resources/forTesting/testComposer.json ... succeeded' . "\n";
        $expected .= $exp;

        // Test.
        $this->assertEquals($expected, $actual);
    }

    /**
     * Deliver standard hello text - while it is used for assertions every time.
     *
     * @param boolean $inclComposer Optional, false. Should part about composer should be included.
     *
     * @return string
     */
    private function getHelloText(bool $inclComposer = false): string
    {

        // Lvd.
        $result  = "\n\n";
        $result .= 'Hello to Sinstaller by @przeslijmi [v1.1.0]' . "\n";
        $result .= 'See https://github.com/przeslijmi/Sinstaller for help' . "\n\n\n";

        // Include composer.
        if ($inclComposer === true) {
            $result .= ' => will use composer: ';
        }

        return $result;
    }

    /**
     * Return standard fail text for given operation and failure cause.
     *
     * @param string $operation Operation that was performed.
     * @param string $cause     Case of failure.
     *
     * @return string
     */
    private function getFailText(string $operation, string $cause): string
    {

        return ' => ' . $operation . ' ... failed, cause ' . $cause . ' !' . "\n";
    }
}
