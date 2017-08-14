<?php
declare(strict_types=1);
namespace Helhum\Typo3ComposerSetup\Composer;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Helmut Hummel <info@helhum.io>
 *  All rights reserved
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Composer\Installer\InstallationManager;
use Composer\Repository\WritableRepositoryInterface;
use Composer\Semver\Constraint\EmptyConstraint;

class Typo3EntryPointFinder
{
    private static $defaultEntryPoints = [
        'frontend' => [
            'target' => 'index.php',
        ],
        'backend' => [
            'target' => 'typo3/index.php',
        ],
        'install' => [
            'target' => 'typo3/install.php',
        ],
    ];

    /**
     * @var WritableRepositoryInterface
     */
    private $repository;

    /**
     * @var InstallationManager
     */
    private $installationManager;

    public function __construct(WritableRepositoryInterface $repository, InstallationManager $installationManager)
    {
        $this->repository = $repository;
        $this->installationManager = $installationManager;
    }

    public function find(string $targetPath): array
    {
        $entryPoints = self::$defaultEntryPoints;
        $typo3CmsPackage = $this->repository->findPackage('typo3/cms', new EmptyConstraint());
        if ($typo3CmsPackage) {
            $cmsInstallPath = $this->installationManager->getInstallPath($typo3CmsPackage);
            $frontendPackagePath = $cmsInstallPath . '/typo3/sysext/frontend';
            $backendPackagePath = $cmsInstallPath . '/typo3/sysext/backend';
            $installPackagePath = $cmsInstallPath . '/typo3/sysext/install';
        } else {
            $frontendPackage = $this->repository->findPackage('typo3/cms-frontend', new EmptyConstraint());
            $frontendPackagePath = $this->installationManager->getInstallPath($frontendPackage);
            $backendPackage = $this->repository->findPackage('typo3/cms-backend', new EmptyConstraint());
            $backendPackagePath = $this->installationManager->getInstallPath($backendPackage);
            $installPackage = $this->repository->findPackage('typo3/cms-install', new EmptyConstraint());
            $installPackagePath = $this->installationManager->getInstallPath($installPackage);
        }

        if (file_exists($frontendSourceFile = $frontendPackagePath . '/Resources/Private/Php/frontend.php')) {
            $entryPoints['frontend']['source'] = $frontendSourceFile;
        } elseif (isset($cmsInstallPath)) {
            $entryPoints['frontend']['source'] = $cmsInstallPath . '/index.php';
        } else {
            throw new \UnexpectedValueException('Could not determine frontend entry point. typo3/cms is not installed?', 1502706253);
        }

        if (file_exists($backendSourceFile = $backendPackagePath . '/Resources/Private/Php/backend.php')) {
            $entryPoints['backend']['source'] = $backendSourceFile;
        } elseif (isset($cmsInstallPath)) {
            $entryPoints['backend']['source'] = $cmsInstallPath . '/typo3/index.php';
        } else {
            throw new \UnexpectedValueException('Could not determine backend entry point. typo3/cms is not installed?', 1502706254);
        }

        if (file_exists($installSourceFile = $installPackagePath . '/Resources/Private/Php/install.php')) {
            $entryPoints['install']['source'] = $installSourceFile;
        } elseif (isset($cmsInstallPath)) {
            $entryPoints['install']['source'] = $cmsInstallPath . '/typo3/sysext/install/Start/Install.php';
            $entryPoints['install']['target'] = 'typo3/sysext/install/Start/Install.php';
        } else {
            throw new \UnexpectedValueException('Could not determine install tool entry point. typo3/cms is not installed?', 1502706255);
        }

        $entryPoints['frontend']['target'] = $targetPath . '/' . $entryPoints['frontend']['target'];
        $entryPoints['backend']['target'] = $targetPath . '/' . $entryPoints['backend']['target'];
        $entryPoints['install']['target'] = $targetPath . '/' . $entryPoints['install']['target'];

        return $entryPoints;
    }
}
