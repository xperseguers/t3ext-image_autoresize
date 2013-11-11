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
	 * @return void
	 */
	static public function indexFile(\TYPO3\CMS\Core\Resource\File $file = NULL, $origFilename, $newFilename, $width, $height) {
		if (version_compare(TYPO3_version, '6.1.99', '<=')) {
			// TYPO3 6.0 and 6.1: No new indexer
			if ($file !== NULL) {
				static::manuallyUpdateIndex($file, $origFilename, $newFilename, $width, $height);
			} else {
				static::manuallyCreateIndex($newFilename, $width, $height);
			}
			return;
		}

		if ($file === NULL) {
			// TODO: check if existing entry exists for $origFilename
			return;
		}

		/** @var \TYPO3\CMS\Core\Resource\Index\FileIndexRepository $fileIndexRepository */
		$fileIndexRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository');

		// TODO: Update (does not yet support a converted file type)
		//$filename = PathUtility::basename($newFilename);
		//$newProperties['identifier'] = preg_replace('/' . preg_quote($file->getProperty('name')) . '$/', $filename, $file->getProperty('identifier'));

		$newProperties = $localDriver = $file->getStorage()->getFileInfo($file);
		$newProperties['sha1'] = $file->getSha1();
		$file->updateProperties($newProperties);
		$fileIndexRepository->update($file);

		/** @var \TYPO3\CMS\Core\Resource\Index\MetaDataRepository $metaDataRepository */
		//$metaDataRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository');
		//$metaDataRepository->findByFile($file);
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
		static::getFileRepository()->update($file);

		// CANNOT BE DONE above if file format changed
		$newProperties = array(
			'extension' => $file->getExtension(),
			'sha1' => $driver->hash($file, 'sha1'),
		);
		$file->updateProperties($newProperties);
		static::getFileRepository()->update($file);
	}

	/**
	 * Creates the index entry for a given file in TYPO3 6.0 and 6.1.
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 * @param string $origFilename
	 * @param string $newFilename
	 * @param integer $width
	 * @param integer $height
	 * @return void
	 */
	static protected function manuallyCreateIndex($filename, $width, $height) {
		// Create a fresh file object
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

		/** @var \TYPO3\CMS\Core\Resource\Driver\AbstractDriver $driver */
		$driver = static::accessProtectedProperty($targetFolder->getStorage(), 'driver');

		/** @var $fileObject \TYPO3\CMS\Core\Resource\File */
		$fileInfo = $driver->getFileInfoByIdentifier($identifier);
		$file = $resourceFactory->createFileObject($fileInfo);
		static::getFileRepository()->addToIndex($file);
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

	/**
	 * Returns a file repository.
	 *
	 * @return \TYPO3\CMS\Core\Resource\FileRepository
	 */
	static protected function getFileRepository() {
		static $fileRepository;
		if ($fileRepository === NULL) {
			$fileRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
		}
		return $fileRepository;
	}

	/**
	 * Returns the database connection.
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	static protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
