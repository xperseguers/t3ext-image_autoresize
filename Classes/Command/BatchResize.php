<?php
declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Causal\ImageAutoresize\Command;

use Causal\ImageAutoresize\Controller\ConfigurationController;
use Causal\ImageAutoresize\Service\ImageResizer;
use Causal\ImageAutoresize\Utility\FAL;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BatchResize extends Command
{
    /**
     * @var ImageResizer
     */
    protected $imageResizer;

    /**
     * @var SymfonyStyle
     */
    protected $io;

    protected function configure()
    {
        $this->addArgument(
            'defaultDirectories',
            InputArgument::OPTIONAL,
            'Comma-separated list of directories to process (overrides watched directories)'
        );
        $this->addArgument(
            'excludeDirectories',
            InputArgument::OPTIONAL,
            'Comma-separated list of directories to exclude from processing',
            ''
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title('Batch resize images');

        $configuration = ConfigurationController::readConfiguration();
        $pathSite = Environment::getPublicPath() . '/';

        $this->imageResizer = GeneralUtility::makeInstance(ImageResizer::class);
        $this->imageResizer->initializeRulesets($configuration);

        $defaultDirectories = $input->getArgument('defaultDirectories');
        if (empty($defaultDirectories)) {
            // Process watched directories
            $directories = $this->imageResizer->getAllDirectories();
        } else {
            $dirs = GeneralUtility::trimExplode(LF, $defaultDirectories, true);
            $directories = [];
            foreach ($dirs as $directory) {
                $directoryConfig = FAL::getDirectoryConfig($directory);
                if ($directoryConfig !== null) {
                    $directories[] = $directoryConfig;
                }
            }
        }
        $processedDirectories = [];

        // Expand watched directories if they contain wildcard characters
        $expandedDirectories = [];
        foreach ($directories as $directoryConfig) {
            if (($pos = strpos($directoryConfig['directory'], '/*')) !== false) {
                $pattern = $directoryConfig['pattern'];
                $basePath = $directoryConfig['basePath'] . substr($directoryConfig['directory'], 0, $pos + 1);

                $objects = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($basePath),
                    \RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($objects as $name => $object) {
                    $relativePath = substr($name, strlen($pathSite));
                    if (substr($relativePath, -2) === DIRECTORY_SEPARATOR . '.') {
                        if (preg_match($pattern, $relativePath)) {
                            $expandedDirectories[] = substr($relativePath, 0, -1);
                        }
                    }
                }
            } else {
                $expandedDirectories[] = $directoryConfig['basePath'] . $directoryConfig['directory'];
            }
        }
        $directories = $expandedDirectories;
        sort($directories);

        $success = true;
        foreach ($directories as $directory) {
            foreach ($processedDirectories as $processedDirectory) {
                $isInProcessedDirectory = PHP_VERSION_ID >= 80000
                    ? str_starts_with($directory, $processedDirectory)
                    : GeneralUtility::isFirstPartOfStr($directory, $processedDirectory);
                if ($isInProcessedDirectory) {
                    continue 2;
                }
            }

            // Execute bach resize
            if (is_dir($directory)) {
                $success |= $this->batchResizePictures($input, $directory);
            }
            $processedDirectories[] = $directory;
        }

        return $success ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Batch resizes pictures in a given parent directory (including all subdirectories
     * recursively).
     *
     * @oaram InputInterface $input
     * @param string $absolutePath
     * @return bool true if run was successful
     * @throws \RuntimeException
     */
    protected function batchResizePictures(InputInterface $input, string $absolutePath): bool
    {
        // Check if given directory exists
        if (!@is_dir($absolutePath)) {
            throw new \RuntimeException('Given directory "' . $absolutePath . '" does not exist', 1384102984);
        }

        $allFileTypes = $this->imageResizer->getAllFileTypes();

        // We do not want to pass any backend user, even if manually running the task as administrator from
        // the Backend as images may be resized based on usergroup rule sets and this should only happen when
        // actually resizing the image while uploading, not during a batch processing (it's simply "too late").
        $backendUser = null;

        /*
        if ((($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof \Psr\Http\Message\ServerRequestInterface
                && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
            ) || Environment::isCli()) {
            $callbackNotification = [$this, 'syslog'];
        } else {
            $callbackNotification = [$this, 'notify'];
        }
        */
        $callbackNotification = [$this, 'notify'];

        $dirs = GeneralUtility::trimExplode(LF, $input->getArgument('excludeDirectories'), true);
        $excludeDirectories = [];
        foreach ($dirs as $directory) {
            $directoryConfig = FAL::getDirectoryConfig($directory);
            if ($directoryConfig !== null) {
                $excludeDirectories[] = $directoryConfig['basePath'] . $directoryConfig['directory'];
            }
        }

        $directoryContent = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($absolutePath));
        foreach ($directoryContent as $fileName => $file) {
            $filePath = $file->getPath();
            $name = substr($fileName, strlen($filePath) + 1);

            // Skip files in recycler directory or whose type should not be processed
            $skip = $name[0] === '.' || substr($filePath, -10) === '_recycler_';
            if (!$skip) {
                // Check if we should skip since in one of the exclude directories
                foreach ($excludeDirectories as $excludeDirectory) {
                    $isInExcludeDirectory = PHP_VERSION_ID >= 80000
                        ? str_starts_with($filePath, $excludeDirectory)
                        : GeneralUtility::isFirstPartOfStr($filePath, $excludeDirectory);
                    if ($isInExcludeDirectory || rtrim($excludeDirectory, '/') === $filePath
                    ) {
                        $skip = true;
                        break;
                    }
                }
            }

            if (!$skip) {
                if (($dotPosition = strrpos($name, '.')) !== false) {
                    $fileExtension = strtolower(substr($name, $dotPosition + 1));
                    if (in_array($fileExtension, $allFileTypes, true)) {
                        $this->imageResizer->processFile(
                            $fileName,
                            '',    // target file name
                            '',    // target directory
                            null,
                            $backendUser,
                            $callbackNotification
                        );
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param string $message
     * @param int|\TYPO3\CMS\Core\Type\ContextualFeedbackSeverity $severity
     * @return void
     */
    public function notify(string $message, $severity)
    {
        if (version_compare((new Typo3Version())->getBranch(), '12.0', '>=')) {
            $severity = $severity->value;
        }

        switch ($severity) {
            case -2:
                $this->io->note($message);
                break;
            case -1:
                $this->io->info($message);
                break;
            case 0:
                $this->io->success($message);
                break;
            case 1:
                $this->io->warning($message);
                break;
            case 2:
                $this->io->error($message);
                break;
        }
    }
}
