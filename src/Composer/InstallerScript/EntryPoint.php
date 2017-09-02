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

class EntryPoint implements InstallerScript
{
    /**
     * Absolute path to entry script source
     *
     * @var string
     */
    private $source;

    /**
     * The target file relative to the web directory
     *
     * @var string
     */
    private $target;

    public function __construct(string $source, string $target)
    {
        $this->source = $source;
        $this->target = $target;
    }

    public function run(Event $event): bool
    {
        $composer = $event->getComposer();
        $filesystem = new Filesystem();

        $entryPointContent = file_get_contents($this->source);
        $autoloadFile = $composer->getConfig()->get('vendor-dir') . '/autoload.php';

        $entryPointContent = preg_replace(
            '/(\$classLoader = require )[^;]*;$/m',
            '\1' . $filesystem->findShortestPathCode($this->target, $autoloadFile) . ';',
            $entryPointContent
        );

        $filesystem->ensureDirectoryExists(dirname($this->target));
        file_put_contents($this->target, $entryPointContent);

        return true;
    }
}
