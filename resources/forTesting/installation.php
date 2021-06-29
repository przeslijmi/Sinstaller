<?php declare(strict_types=1);

require('vendor/autoload.php');

use Przeslijmi\Sinstaller\Installer;

$ins = new Installer();
$ins->setComposer('tests/testComposer.json');

$ins->makeDir('tests/probe/src/');
$ins->fileIfne('Vendor\\App', 'Exception.php', 'tests/probe/Exception.php');
$ins->dir('Vendor\\App', '.', 'tests/probe/src/aaa/');
$ins->setFileContentsIfe('tests/probe/src/aaa/Exception.php', 'aa');
$ins->setFileContentsIfne('tests/probe/src/aaa/Exception.php', 'aa');
$ins->emptyDirRecursively('tests/probe/src/aaa/');


$ins->getLog()->logLn('[NL][NL]Installation finished![NL]');
$ins->getLog()->logLn('[NL]Start console with commands:[NL]----------------------------[NL]cd [currDir][NL]php -S 127.0.0.1:8001[NL]----------------------------');
