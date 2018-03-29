<?php
declare(strict_types=1);
namespace Helhum\Typo3ComposerSetup\Composer\InstallerScript;

/*
 * This file is part of the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Composer\Script\Event;
use Composer\Util\Filesystem;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScript;

class LegacyCliEntryPoint implements InstallerScript
{
    /**
     * The target file relative to the web directory
     *
     * @var string
     */
    private $target;

    public function __construct(string $target)
    {
        $this->target = $target;
    }

    public function run(Event $event): bool
    {
        $filesystem = new Filesystem();

        $entryPointContent = <<<CONTENT
#!/usr/bin/env php
<?php
// This entry-point is deprecated since TYPO3 v8 and will be removed in TYPO3 v9
// Use the binary located typo3/sysext/core/bin/typo3 instead.
require __DIR__ . '/cli.php';
CONTENT;

        $filesystem->ensureDirectoryExists(dirname($this->target));
        file_put_contents($this->target, $entryPointContent);
        chmod($this->target, 0755);

        return true;
    }
}
