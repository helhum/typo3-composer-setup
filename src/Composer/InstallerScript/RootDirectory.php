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

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Script\Event;
use Composer\Semver\Constraint\EmptyConstraint;
use Composer\Util\Platform;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScript;
use TYPO3\CMS\Composer\Plugin\Util\Filesystem;

/**
 * Setting up TYPO3 root directory
 */
class RootDirectory implements InstallerScript
{
    const PUBLISH_STRATEGY_MIRROR = 'mirror';
    const PUBLISH_STRATEGY_LINK = 'link';

    /**
     * @var string
     */
    private static $typo3Dir = '/typo3';

    /**
     * @var string
     */
    private static $systemExtensionsDir = '/sysext';

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var bool
     */
    private $isDevMode = false;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var string
     */
    private $publishStrategy;

    public function __construct(string $rootDir, string $publishStrategy = self::PUBLISH_STRATEGY_LINK)
    {
        $this->rootDir = $rootDir;
        $this->publishStrategy = $publishStrategy;
    }

    /**
     * Prepare the web directory with symlinks
     *
     * @param Event $event
     * @return bool
     */
    public function run(Event $event): bool
    {
        $this->io = $event->getIO();
        $this->composer = $event->getComposer();
        $this->filesystem = new Filesystem();
        $this->isDevMode = $event->isDevMode();
        $backendDir = $this->rootDir . self::$typo3Dir;

        $this->io->writeError('<info>Setting up TYPO3 Core Extension directories</info>');

        $localRepository = $this->composer->getRepositoryManager()->getLocalRepository();
        $typo3Package = $localRepository->findPackage('typo3/cms', new EmptyConstraint());
        $sourcesDir = $this->composer->getInstallationManager()->getInstallPath($typo3Package);

        $source = $sourcesDir . self::$typo3Dir . self::$systemExtensionsDir;
        $target = $backendDir . self::$systemExtensionsDir;

        $this->ensureOldLinksRemoved($target);
        $this->filesystem->ensureDirectoryExists($target);

        $coreExtKeys = $this->getCoreExtensionKeysFromTypo3Package($typo3Package);
        $fileSystem = new \Symfony\Component\Filesystem\Filesystem();
        $installedSystemExtensions = glob($target . '/*');
        foreach ($installedSystemExtensions as $installedSystemExtension) {
            if (!in_array(basename($installedSystemExtension), $coreExtKeys, true)) {
                if ($this->filesystem->isJunction($installedSystemExtension)) {
                    $this->filesystem->removeJunction($installedSystemExtension);
                } else {
                    $fileSystem->remove($installedSystemExtension);
                }
            }
        }

        foreach ($coreExtKeys as $coreExtKey) {
            $extensionSource = $source . '/' . $coreExtKey;
            $extensionTarget = $target . '/' . $coreExtKey;
            if ($this->publishStrategy === self::PUBLISH_STRATEGY_LINK) {
                if (file_exists($extensionTarget)) {
                    continue;
                }
                if (Platform::isWindows()) {
                    // Implement symlinks as NTFS junctions on Windows
                    $this->filesystem->junction($extensionSource, $extensionTarget);
                } else {
                    $shortestPath = $this->filesystem->findShortestPath($extensionTarget, $extensionSource);
                    $extensionTarget = rtrim($extensionTarget, '/');
                    $fileSystem->symlink($shortestPath, $extensionTarget);
                }
            } elseif ($this->publishStrategy === self::PUBLISH_STRATEGY_MIRROR) {
                $fileSystem->mirror($extensionSource, $extensionTarget, null, ['delete' => true]);
            } else {
                throw new \UnexpectedValueException('Publish strategy can only be one of "mirror" or "link"');
            }
        }

        return true;
    }

    private function ensureOldLinksRemoved(string $systemDir)
    {
        $typo3Dir = dirname($systemDir);
        $indexPhp = dirname($typo3Dir) . '/index.php';
        foreach ([$systemDir, $typo3Dir, $indexPhp] as $possibleLink) {
            if (is_link($possibleLink)) {
                unlink($possibleLink);
            }
        }
    }

    /**
     * @param PackageInterface $typo3Package
     * @return array
     */
    private function getCoreExtensionKeysFromTypo3Package(PackageInterface $typo3Package): array
    {
        $coreExtensionKeys = [];
        $frameworkPackages = [];
        foreach ($typo3Package->getReplaces() as $name => $_) {
            if (is_string($name) && strpos($name, 'typo3/cms-') === 0) {
                $frameworkPackages[] = $name;
            }
        }
        $installedPackages = $this->composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
        $rootPackage = $this->composer->getPackage();
        $installedPackages[$rootPackage->getName()] = $rootPackage;
        foreach ($installedPackages as $package) {
            $requires = $package->getRequires();
            if ($package === $rootPackage && $this->isDevMode) {
                $requires = array_merge($requires, $package->getDevRequires());
            }
            foreach ($requires as $name => $_) {
                if (is_string($name) && in_array($name, $frameworkPackages, true)) {
                    $extensionKey = $this->determineExtKeyFromPackageName($name);
                    $this->io->writeError(sprintf('The package "%s" requires: "%s"', $package->getName(), $name), true, IOInterface::DEBUG);
                    $this->io->writeError(sprintf('The extension key for package "%s" is: "%s"', $name, $extensionKey), true, IOInterface::DEBUG);
                    $coreExtensionKeys[$name] = $extensionKey;
                }
            }
        }
        return $coreExtensionKeys;
    }

    /**
     * @param string $packageName
     * @return string
     */
    private function determineExtKeyFromPackageName(string $packageName): string
    {
        return str_replace(['typo3/cms-', '-'], ['', '_'], $packageName);
    }
}
