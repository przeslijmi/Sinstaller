
![badge](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/przeslijmi/ddfc64b8aa29f4d841ab52f394e43482/raw/test.json)
![badge](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/przeslijmi/3b3f9a428e84a26e8cdf64bd5b83e5d9/raw/test.json)

# Simple installation solution

App used to perform file and dir movements after `composer` finishes job.

Used for Agile Data software.

## Table of contents

__TOC__

## Usage example

```php
<?php

declare(strict_types=1);

require('bootstrap.php');

use Przeslijmi\Sinstaller\Installer;
use Przeslijmi\Sinstaller\CriticalException;

try {

    // Create installer.
    $ins = new Installer();
    $ins->setComposer('resources/forTesting/testComposer.json');

    // Additional settings - enabled by default.
    $ins->disableCriticalStop();
    $ins->enableCriticalStop();

    // Ask question - you'll have response given by the user in $response variable.
    $ins->askFor('test', 'Give something: ');
    $response = $ins->getUserInput('test'));

    // Ask question that will be reasked until validation returns `true`.
    $ins->askFor(
        'test',
        'Write `YES`: ',
        function (string $response): bool
        {
            return ( $response === 'YES' );
        },
        'I said - write `YES`!'
    );
    $response = $ins->getUserInput('test'));

    // Echoing - enabled by default.
    $ins->getLogger()->disableEcho();
    $ins->getLogger()->enableEcho();

    // Main installation part.
    $ins->makeDir('tests/probe/src/');
    $ins->fileIfne('Vendor\\App', 'Exception.php', 'tests/probe/Exception.php');
    $ins->dir('Vendor\\App', '.', 'tests/probe/src/aaa/');
    $ins->setFileContentsIfe('tests/probe/src/aaa/Exception.php', 'aa');
    $ins->setFileContentsIfne('tests/probe/src/aaa/Exception.php', 'aa');
    $ins->emptyDirRecursively('tests/probe/src/aaa/');

    // Show some info for the user.
    $ins->getLogger()->logLn('[NL][NL]Installation finished![NL]');
    $ins->getLogger()->logLn('[NL]Start console with commands:[NL]----------------------------[NL]cd [currDir][NL]php -S 127.0.0.1:8001[NL]----------------------------');

} catch (CriticalException $exc) {
    die;
}
```

### `composer.json` usage

Use of composer (by `->setComposer()`) is needed for proper perfoming those operations:

  - `file`
  - `fileIfne`
  - `dir`

while this operation have source defined not as uri but as app vendor and name `Vendor\App` namespace with uri taken from composer.

## Methods

### Config

| Name | Description |
| --- | --- |
| disableCriticalStop | If `CriticalStop` is off error in on operation will not shut the program. |
| enableCriticalStop | If `CriticalStop` is on any error in on operation will shut the program. |
| isCriticalStopEnabled | Returns `true` if `CriticalStop` is enabled, `false` otherwise. |
| setComposer | Defines composer file that should be used in other operations. |

### Files and directories operations

| Name | Composer.json needed? | Description |
| --- | --- | --- |
| `ask` | no | Asks question and can also validate answer. |
| `file` | **yes** | Copies file from source app into destination. |
| `fileIfne` | **yes** | Copies file from source app into destination **only if that file does not exists**. |
| `setFileContentsIfe` | no | Sets given file given contetns **only if that file exists**. |
| `setFileContentsIfne` | no | Sets given file given contetns **only if that file does not exists**. |
| `dir` | **yes** | Copies dir from source app into destination. |
| `makeDir` | no | Creates empty dir - inlcuding path if it is deep. |
| `emptyDirRecursively` | no | Cleans contents of a dir including all subdirs and all files. |
| `getVendorAppUri` | yes | Delivers URI to `src` directory of given vendor/app. |
| `getUserInput` | no | Deliver user input under given key. |
| `getLogger` | no | Delivers `Logger` object. |
| `getLog` | no | Delivers string contents of log. |

### Logger

Call `$ins->getLogger()` to gain access to methods below.

| Name | Description |
| --- | --- |
| `disableEcho` | Logger will only keep log - without echoing it. |
| `enableEcho` | Logger will both keep log and echo it. |
| `isEchoEnabled` | Returns `true` if echo is enabled, `false` otherwise. |
| `getLog` | Delivers string contents of log. |
| `sayHello` | Shows welcome text with version number of Sinstaller. |
| `logLn` | Log whole line (will add `\n` at the end). |
| `log` | Log text without adding `\n` at the end). |
| `logFailure` | Log information about failure, ie. thrown exception. |
| `logSucess` | Log information about success. |

## Exceptions

### `Przeslijmi\Sinstaller\Exception`

Always catched inside program - something (defined in `$message`) went wrong during installation process.

### `Przeslijmi\Sinstaller\CriticalException`

Is thrown by the program if any `Throwable` occures during operation and if `CriticalStop` action is enabled - also ends operation of the program (`die`).
