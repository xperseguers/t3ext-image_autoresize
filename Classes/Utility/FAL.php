<?php
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

namespace Causal\ImageAutoresize\Utility;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * This is a FAL-manipulation utility.
 *
 * @category    Utility
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class FAL
{

    /**
     * @var array
     */
    protected static $reflectedClasses = [];

    /**
     * Creates/updates the index entry for a given file.
     *
     * @param \TYPO3\CMS\Core\Resource\File $file
     * @param string $origFileName
     * @param string $newFileName
     * @param integer $width
     * @param integer $height
     * @param array $metadata EXIF metadata
     * @return void
     */
    public static function indexFile(\TYPO3\CMS\Core\Resource\File $file = null, $origFileName, $newFileName, $width, $height, array $metadata = [])
    {
        if ($file === null) {
            $file = static::findExistingFile($origFileName);
        }
        if ($file !== null) {
            static::updateIndex($file, $width, $height, $metadata);
        } else {
            static::createIndex($newFileName, $width, $height);
        }
    }

    /**
     * Finds an existing file.
     *
     * @param string $fileName
     * @return \TYPO3\CMS\Core\Resource\AbstractFile|null
     */
    protected static function findExistingFile($fileName)
    {
        $file = null;
        $relativePath = substr(PathUtility::dirname($fileName), strlen(PATH_site));
        $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
        $targetFolder = $resourceFactory->retrieveFileOrFolderObject($relativePath);

        $storageConfiguration = $targetFolder->getStorage()->getConfiguration();
        if (isset($storageConfiguration['basePath'])) {
            $basePath = rtrim($storageConfiguration['basePath'], '/') . '/';
            $basePath = GeneralUtility::getFileAbsFileName($basePath);
            $identifier = substr($fileName, strlen($basePath) - 1);

            $row = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('sys_file')
                ->select(
                    ['uid'],
                    'sys_file',
                    [
                        'storage' => $targetFolder->getStorage()->getUid(),
                        'identifier' => $identifier,
                    ]
                )
                ->fetch();

            if (!empty($row['uid'])) {
                /** @var \TYPO3\CMS\Core\Resource\FileRepository $fileRepository */
                $fileRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\FileRepository::class);
                $file = $fileRepository->findByUid($row['uid']);
            }
        }

        return $file;
    }

    /**
     * Updates the index entry for a given file.
     *
     * @param \TYPO3\CMS\Core\Resource\File $file
     * @param integer $width
     * @param integer $height
     * @param array $metadata EXIF metadata
     * @return void
     */
    protected static function updateIndex(\TYPO3\CMS\Core\Resource\File $file = null, $width, $height, array $metadata = [])
    {
        if (count($metadata) > 0) {
            /** @var \TYPO3\CMS\Core\Resource\Index\MetaDataRepository $metadataRepository */
            $metadataRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Index\MetaDataRepository::class);
            // Will take care of creating the record if it does not exist yet
            $currentMetadata = $metadataRepository->findByFile($file);

            $newMetadata = [];
            // Pre-populate with metadata coming from external extractors
            foreach ($currentMetadata as $key => $value) {
                if (!empty($metadata[$key])) {
                    // Known issue with "creator_tool" having a software version sometimes
                    if ($key === 'creator_tool' && MathUtility::canBeInterpretedAsFloat($metadata[$key])) {
                        continue;
                    }
                    $newMetadata[$key] = $metadata[$key];
                }
            }
            // Width and height are always wrong since we resized the image
            unset($newMetadata['width']);
            unset($newMetadata['height']);
            // We deal with resized images so unit is always pixels
            $newMetadata['unit'] = 'px';

            // Mapping for the built-in PHP-based metadata extractor
            $mapping = [
                'color_space' => 'ColorSpace',
                'content_creation_date' => 'DateTimeOriginal',
                'creator' => 'IPTCCreator|Company',
                'creator_tool' => 'Model|Make|Software',
                'description' => 'ImageDescription',
                'keywords' => 'IPTCKeywords',
                'latitude' => 'GPSLatitudeDecimal',
                'longitude' => 'GPSLongitudeDecimal',
                'location_city' => 'IPTCCity',
                'location_country' => 'IPTCCountry',
                'location_region' => 'IPTCRegion',
                'note' => 'IPTCLocation',
                'publisher' => 'IPTCCredit',
                'source' => 'IPTCSource',
                'title' => 'IPTCTitle',
            ];
            foreach ($mapping as $falKey => $metadataKeyMapping) {
                if (!empty($newMetadata[$falKey])) {
                    // We already have a known-to-be-valid metadata for this FAL property
                    continue;
                }
                $metatadaKeys = explode('|', $metadataKeyMapping);
                foreach ($metatadaKeys as $metadataKey) {
                    $value = null;
                    if (isset($metadata[$metadataKey])) {
                        $value = trim($metadata[$metadataKey]);
                        if (ord($value) === 1) $value = null;
                        switch ($metadataKey) {
                            case 'ColorSpace':
                                if ($value == 1) {
                                    $value = 'RGB';
                                } else {
                                    // Unknown
                                    $value = null;
                                }
                                break;
                            case 'DateTimeOriginal':
                                $value = strtotime($value);
                                break;
                        }
                    }
                    if (!empty($value)) {
                        $newMetadata[$falKey] = $value;
                        break;
                    }
                }
            }
            $metadataRepository->update($file->getUid(), $newMetadata);
        }
    }

    /**
     * Creates the index entry for a given file.
     *
     * @param string $fileName
     * @param integer $width
     * @param integer $height
     * @return void
     */
    protected static function createIndex($fileName, $width, $height)
    {
        $relativePath = substr(PathUtility::dirname($fileName), strlen(PATH_site));
        $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
        $targetFolder = $resourceFactory->retrieveFileOrFolderObject($relativePath);
        $targetFilename = PathUtility::basename($fileName);

        $storageConfiguration = $targetFolder->getStorage()->getConfiguration();
        if (!isset($storageConfiguration['basePath'])) {
            // Probably a file found in uploads/ or similar
            return;
        }
        $basePath = rtrim($storageConfiguration['basePath'], '/') . '/';
        $basePath = GeneralUtility::getFileAbsFileName($basePath);
        $identifier = substr($fileName, strlen($basePath) - 1);

        // TODO: possibly create file with nearly no info and populate them with
        // a call to $file->getStorage()->getFileInfo($file) instead of using $driver
        /** @var \TYPO3\CMS\Core\Resource\Driver\AbstractDriver $driver */
        $driver = static::accessProtectedProperty($targetFolder->getStorage(), 'driver');
        $fileInfo = $driver->getFileInfoByIdentifier($identifier);
        $file = $resourceFactory->createFileObject($fileInfo);

        /** @var \TYPO3\CMS\Core\Resource\FileRepository $fileRepository */
        $fileRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\FileRepository::class);
        $fileRepository->addToIndex($file);
    }

    /**
     * Returns the value of a protected property.
     *
     * @param object $object
     * @param string $propertyName
     * @return mixed
     */
    protected static function accessProtectedProperty($object, $propertyName)
    {
        $className = get_class($object);
        if (!isset(static::$reflectedClasses[$className])) {
            static::$reflectedClasses[$className] = new \ReflectionClass($className);
        }
        $class = static::$reflectedClasses[$className];
        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

}
