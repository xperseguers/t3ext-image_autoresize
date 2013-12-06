<?php
namespace Causal\ImageAutoresize\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Xavier Perseguers <xavier@causal.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
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

use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Core\Utility\PathUtility;

/**
 * This is a FAL-manipulation utility.
 *
 * @category    Utility
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class FAL {

	/** @var array */
	static protected $reflectedClasses = array();

	/**
	 * Creates/updates the index entry for a given file.
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 * @param string $origFilename
	 * @param string $newFilename
	 * @param integer $width
	 * @param integer $height
	 * @param array $metadata EXIF metadata
	 * @return void
	 */
	static public function indexFile(\TYPO3\CMS\Core\Resource\File $file = NULL, $origFilename, $newFilename, $width, $height, array $metadata = array()) {
		if ($file === NULL) {
			$file = static::findExistingFile($origFilename);
		}
		if ($file !== NULL) {
			if (version_compare(TYPO3_version, '6.1.99', '<=')) {
				// TYPO3 6.0 and 6.1: No new indexer
				static::manuallyUpdateIndex($file, $origFilename, $newFilename, $width, $height);
			} else {
				static::updateIndex($file, $origFilename, $newFilename, $width, $height, $metadata);
			}
		} else {
			static::createIndex($newFilename, $width, $height);
		}
	}

	/**
	 * Finds an existing file.
	 *
	 * @param string $filename
	 * @return \TYPO3\CMS\Core\Resource\AbstractFile|NULL
	 */
	static protected function findExistingFile($filename) {
		$file = NULL;
		$relativePath = substr(PathUtility::dirname($filename), strlen(PATH_site));
		$resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
		$targetFolder = $resourceFactory->retrieveFileOrFolderObject($relativePath);

		$storageConfiguration = $targetFolder->getStorage()->getConfiguration();
		if (isset($storageConfiguration['basePath'])) {
			$basePath = rtrim($storageConfiguration['basePath'], '/') . '/';
			$basePath = GeneralUtility::getFileAbsFileName($basePath);
			$identifier = substr($filename, strlen($basePath) - 1);

			$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'uid',
				'sys_file',
				'storage=' . intval($targetFolder->getStorage()->getUid()) .
					' AND identifier=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($identifier, 'sys_file') .
					\TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('sys_file')
			);

			if (!empty($row['uid'])) {
				/** @var \TYPO3\CMS\Core\Resource\FileRepository $fileRepository */
				$fileRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
				$file = $fileRepository->findByUid($row['uid']);
			}
		}

		return $file;
	}

	/**
	 * Updates the index entry for a given file in TYPO3 >= 6.2.
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 * @param string $origFilename
	 * @param string $newFilename
	 * @param integer $width
	 * @param integer $height
	 * @param array $metadata EXIF metadata
	 * @return void
	 */
	static protected function updateIndex(\TYPO3\CMS\Core\Resource\File $file = NULL, $origFilename, $newFilename, $width, $height, array $metadata = array()) {
		$storageConfiguration = $file->getStorage()->getConfiguration();
		$basePath = rtrim($storageConfiguration['basePath'], '/') . '/';
		$basePath = GeneralUtility::getFileAbsFileName($basePath);
		$identifier = substr($newFilename, strlen($basePath) - 1);

		$file->setIdentifier($identifier);

		/** @var \TYPO3\CMS\Core\Resource\Service\IndexerService $indexerService */
		$indexerService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Service\\IndexerService');
		$indexerService->indexFile($file);

		// Extension and name may not be correct if the file has been converted from one format to another
		if ($origFilename !== $newFilename) {
			$name = PathUtility::basename($newFilename);
			$newProperties = array(
				'name' => $name,
				'extension' => strtolower(substr($name, strrpos($name, '.') + 1)),
			);
			$file->updateProperties($newProperties);

			/** @var \TYPO3\CMS\Core\Resource\Index\FileIndexRepository $fileIndexRepository */
			$fileIndexRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository');
			$fileIndexRepository->update($file);
		}

		if (count($metadata) > 0) {
			/** @var \TYPO3\CMS\Core\Resource\Index\MetaDataRepository $metadataRepository */
			$metadataRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository');
			// Will take care of creating the record if it does not exist yet
			$currentMetadata = $metadataRepository->findByFile($file);
			$newMetadata = array(
				'unit' => 'px',
			);
			$mapping = array(
				//'caption' => '',
				'color_space' => 'ColorSpace',
				'content_creation_date' => 'DateTimeOriginal',
				//'content_modification_time' => '',
				'creator' => 'IPTCCreator',
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
				//'ranking' => '',
				'source' => 'IPTCSource',
				//'status' => '',
				'title' => 'IPTCTitle',
			);
			foreach ($mapping as $falKey => $metadataKeyMapping) {
				$metatadaKeys = explode('|', $metadataKeyMapping);
				foreach ($metatadaKeys as $metadataKey) {
					$value = NULL;
					if (isset($metadata[$metadataKey])) {
						$value = trim($metadata[$metadataKey]);
						if (ord($value) === 1) $value = NULL;
						switch ($metadataKey) {
							case 'ColorSpace':
								if ($value == 1) {
									$value = 'RGB';
								} else {
									// Unknown
									$value = NULL;
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
	 * Updates the index entry for a given file in TYPO3 6.0 and 6.1.
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 * @param string $origFilename
	 * @param string $newFilename
	 * @param integer $width
	 * @param integer $height
	 * @return void
	 */
	static protected function manuallyUpdateIndex(\TYPO3\CMS\Core\Resource\File $file = NULL, $origFilename, $newFilename, $width, $height) {
		$storageConfiguration = $file->getStorage()->getConfiguration();
		$basePath = rtrim($storageConfiguration['basePath'], '/') . '/';
		$basePath = GeneralUtility::getFileAbsFileName($basePath);
		$identifier = substr($newFilename, strlen($basePath) - 1);

		// $driver call below cannot be replaced by $file->getStorage()->getFileInfo($file)
		// when the file has been renamed (converted from one format to the other)
		/** @var \TYPO3\CMS\Core\Resource\Driver\AbstractDriver $driver */
		$driver = static::accessProtectedProperty($file->getStorage(), 'driver');

		// Business logic borrowed and adapted from \TYPO3\CMS\Core\Resource\ResourceStorage::updateFile()
		$fileInfo = $driver->getFileInfoByIdentifier($identifier);
		$newProperties = array(
			'storage' => $fileInfo['storage'],
			'identifier' => $fileInfo['identifier'],
			'tstamp' => $fileInfo['mtime'],
			'crdate' => $fileInfo['ctime'],
			'mime_type' => $fileInfo['mimetype'],
			'size' => $fileInfo['size'],
			'name' => $fileInfo['name'],
			// Not in original method
			'width' => $width,
			'height' => $height,
		);
		$file->updateProperties($newProperties);

		/** @var \TYPO3\CMS\Core\Resource\FileRepository $fileRepository */
		$fileRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
		$fileRepository->update($file);

		// CANNOT BE DONE above if file format changed
		$newProperties = array(
			'extension' => $file->getExtension(),
			'sha1' => $file->getSha1(),
		);
		$file->updateProperties($newProperties);
		$fileRepository->update($file);
	}

	/**
	 * Creates the index entry for a given file.
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 * @param string $origFilename
	 * @param string $newFilename
	 * @param integer $width
	 * @param integer $height
	 * @return void
	 */
	static protected function createIndex($filename, $width, $height) {
		$relativePath = substr(PathUtility::dirname($filename), strlen(PATH_site));
		$resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
		$targetFolder = $resourceFactory->retrieveFileOrFolderObject($relativePath);
		$targetFilename = PathUtility::basename($filename);

		$storageConfiguration = $targetFolder->getStorage()->getConfiguration();
		if (!isset($storageConfiguration['basePath'])) {
			// Probably a file found in uploads/ or similar
			return;
		}
		$basePath = rtrim($storageConfiguration['basePath'], '/') . '/';
		$basePath = GeneralUtility::getFileAbsFileName($basePath);
		$identifier = substr($filename, strlen($basePath) - 1);

		// TODO: possibly create file with nearly no info and populate them with
		// a call to $file->getStorage()->getFileInfo($file) instead of using $driver
		/** @var \TYPO3\CMS\Core\Resource\Driver\AbstractDriver $driver */
		$driver = static::accessProtectedProperty($targetFolder->getStorage(), 'driver');
		$fileInfo = $driver->getFileInfoByIdentifier($identifier);
		$file = $resourceFactory->createFileObject($fileInfo);

		/** @var \TYPO3\CMS\Core\Resource\FileRepository $fileRepository */
		$fileRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
		$fileRepository->addToIndex($file);
	}

	/**
	 * Returns the value of a protected property.
	 *
	 * @param object $object
	 * @param string $propertyName
	 * @return mixed
	 */
	static protected function accessProtectedProperty($object, $propertyName) {
		$className = get_class($object);
		if (!isset(static::$reflectedClasses[$className])) {
			static::$reflectedClasses[$className] = new \ReflectionClass($className);
		}
		$class = static::$reflectedClasses[$className];
		$property = $class->getProperty($propertyName);
		$property->setAccessible(TRUE);

		return $property->getValue($object);
	}

}
