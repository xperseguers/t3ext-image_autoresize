<?php
namespace Causal\ImageAutoresize\Service;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use Causal\ImageAutoresize\Utility\ImageUtility;

/**
 * This is a utility class to resize pictures based on rules.
 *
 * @category    Service
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class ImageResizer {

	/**
	 * @var array
	 */
	protected $rulesets = array();

	/**
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 */
	protected $signalSlotDispatcher;

	/**
	 * @var array|NULL
	 */
	protected $lastMetadata = NULL;

	/**
	 * Default constructor
	 */
	public function __construct() {
		$this->signalSlotDispatcher = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
	}

	/**
	 * Initializes the hook configuration as a meaningful ordered list
	 * of rule sets.
	 *
	 * @param array $configuration
	 * @return void
	 */
	public function initializeRulesets(array $configuration) {
		$general = $configuration;
		$general['usergroup'] = '';
		unset($general['rulesets']);
		$general = $this->expandValuesInRuleset($general);
		if ($general['conversion_mapping'] === '') {
			$general['conversion_mapping'] = array();
		}

		if (isset($configuration['rulesets'])) {
			$rulesets = $this->compileRuleSets($configuration['rulesets']);
		} else {
			$rulesets = array();
		}

		// Inherit values from general configuration in rule sets if needed
		foreach ($rulesets as $k => &$ruleset) {
			foreach ($general as $key => $value) {
				if (!isset($ruleset[$key])) {
					$ruleset[$key] = $value;
				} elseif ($ruleset[$key] === '') {
					$ruleset[$key] = $value;
				}
			}
			if (count($ruleset['usergroup']) == 0) {
				// Make sure not to try to override general configuration
				// => only keep directories not present in general configuration
				$ruleset['directories'] = array_diff($ruleset['directories'], $general['directories']);
				if (count($ruleset['directories']) == 0) {
					unset($rulesets[$k]);
				}
			}
		}

		// Use general configuration as very last rule set
		$rulesets[] = $general;
		$this->rulesets = $rulesets;
	}

	/**
	 * Processes upload of a file.
	 *
	 * @param string $fileName
	 * @param string $targetFileName Expected target file name, if not converted
	 * @param string $targetDirectory
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 * @param \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $backendUser
	 * @param callback $callbackNotification Callback to send notification
	 * @return string File name that was finally written
	 */
	public function processFile($fileName, $targetFileName = '', $targetDirectory = '', \TYPO3\CMS\Core\Resource\File $file = NULL, \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $backendUser = NULL, $callbackNotification = NULL) {
		$this->lastMetadata = NULL;

		if (!(empty($targetFileName) && empty($targetDirectory))) {
			$targetDirectory = rtrim($targetDirectory, '/') . '/';
			$ruleset = $this->getRuleset($fileName, $targetDirectory . $targetFileName, $backendUser);
		} else {
			$ruleset = $this->getRuleset($fileName, $fileName, $backendUser);
		}

		if (count($ruleset) == 0)  {
			// File does not match any rule set
			return $fileName;
		}

		if ($backendUser === NULL && count($ruleset['usergroup']) > 0) {
			// Rule set is targeting some user group but we have no backend user (scheduler task)
			// so we should skip this file altogether
			return $fileName;
		}

		// Make file name relative, store as $targetFileName
		if (empty($targetFileName)) {
			$targetFileName = substr($fileName, strlen(PATH_site));
		}

		// Extract the extension
		if (($dotPosition = strrpos($fileName, '.')) === FALSE) {
			// File has no extension
			return $fileName;
		}
		$fileExtension = strtolower(substr($fileName, $dotPosition + 1));

		if ($fileExtension === 'png' && !$ruleset['resize_png_with_alpha']) {
			if (ImageUtility::isTransparentPng($fileName)) {
				$message = sprintf(
					$GLOBALS['LANG']->sL('LLL:EXT:image_autoresize/Resources/Private/Language/locallang.xml:message.imageTransparent'),
					$targetFileName
				);
				$this->notify($callbackNotification, $message, \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING);
				return $fileName;
			}
		}
		if (!is_writable($fileName)) {
			$message = sprintf(
				$GLOBALS['LANG']->sL('LLL:EXT:image_autoresize/Resources/Private/Language/locallang.xml:message.imageNotWritable'),
				$targetFileName
			);
			$this->notify($callbackNotification, $message, \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
			return $fileName;
		}

		if (isset($ruleset['conversion_mapping'][$fileExtension])) {
			// File format will be converted
			$destExtension = $ruleset['conversion_mapping'][$fileExtension];
			$destDirectory = PathUtility::dirname($fileName);
			$destFileName = PathUtility::basename(substr($fileName, 0, strlen($fileName) - strlen($fileExtension)) . $destExtension);

			if (empty($targetDirectory)) {
				// Ensures $destFileName does not yet exist, otherwise make it unique!
				/* @var $fileFunc \TYPO3\CMS\Core\Utility\File\BasicFileUtility */
				$fileFunc = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Utility\\File\\BasicFileUtility');
				$destFileName = $fileFunc->getUniqueName($destFileName, $destDirectory);
				$targetDestFileName = $destFileName;
			} else {
				$destFileName = $destDirectory . '/' . $destFileName;
				$targetDestFileName = $targetDirectory . PathUtility::basename(substr($targetFileName, 0, strlen($targetFileName) - strlen($fileExtension)) . $destExtension);
			}
		} else {
			// File format stays the same
			$destExtension = $fileExtension;
			$destFileName = $fileName;
		}

		// Image is bigger than allowed, will now resize it to (hopefully) make it lighter
		/** @var $gifCreator \TYPO3\CMS\Frontend\Imaging\GifBuilder */
		$gifCreator = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder');
		$gifCreator->init();
		$gifCreator->absPrefix = PATH_site;

		$imParams = $ruleset['keep_metadata'] === '1' ? '###SkipStripProfile###' : '';
		$metadata = ImageUtility::getMetadata($fileName);
		$this->lastMetadata = $metadata;
		$isRotated = FALSE;

		if ($ruleset['auto_orient'] === '1') {
			$orientation = ImageUtility::getOrientation($fileName);
			$isRotated = ImageUtility::isRotated($orientation);
			$transformation = ImageUtility::getTransformation($orientation);
			if ($transformation !== '') {
				$imParams .= ' ' . $transformation;
			}
		}

		if ($isRotated) {
			// Invert max_width and max_height as the picture
			// will be automatically rotated
			$options = array(
				'maxW' => $ruleset['max_height'],
				'maxH' => $ruleset['max_width'],
			);
		} else {
			$options = array(
				'maxW' => $ruleset['max_width'],
				'maxH' => $ruleset['max_height'],
			);
		}

		$originalFileSize = filesize($fileName);
		$tempFileInfo = NULL;
		$tempFileInfo = $gifCreator->imageMagickConvert($fileName, $destExtension, '', '', $imParams, '', $options, TRUE);
		if (filesize($tempFileInfo[3]) >= $originalFileSize - 10240 && $destExtension === $fileExtension) {
			// Conversion leads to same or bigger file (rounded to 10KB to accomodate tiny variations in compression) => skip!
			$tempFileInfo = NULL;
		}
		if ($tempFileInfo) {
			// Signal to post-process the image
			$this->signalSlotDispatcher->dispatch(
				__CLASS__,
				'afterImageResize',
				array(
					'operation' => ($fileName === $destFileName) ? 'RESIZE' : 'RESIZE_CONVERT',
					'source' => $fileName,
					'destination' => $tempFileInfo[3],
					'newWidth' => &$tempFileInfo[0],
					'newHeight' => &$tempFileInfo[1],
				)
			);

			$newFileSize = filesize($tempFileInfo[3]);
			$this->reportAdditionalStorageClaimed($originalFileSize - $newFileSize);

			// Replace original file
			@unlink($fileName);
			@rename($tempFileInfo[3], $destFileName);

			if ($fileName === $destFileName) {
				$message = sprintf(
					$this->localize('LLL:EXT:image_autoresize/Resources/Private/Language/locallang.xml:message.imageResized'),
					$targetFileName, $tempFileInfo[0], $tempFileInfo[1]
				);
			} else {
				$message = sprintf(
					$this->localize('LLL:EXT:image_autoresize/Resources/Private/Language/locallang.xml:message.imageResizedAndRenamed'),
					$targetFileName, $tempFileInfo[0], $tempFileInfo[1], PathUtility::basename($targetDestFileName)
				);
			}

			// Indexation in TYPO3 6.2 is using another signal, after the file
			// has been actually uploaded
			$this->lastMetadata['COMPUTED']['Width'] = $tempFileInfo[0];
			$this->lastMetadata['COMPUTED']['Height'] = $tempFileInfo[1];

			if ($isRotated && $ruleset['keep_metadata'] === '1' && $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5'] === 'gm') {
				ImageUtility::resetOrientation($destFileName);
			}

			$this->notify($callbackNotification, $message, \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);
		} else {
			// Destination file was not written
			$destFileName = $fileName;
		}
		return $destFileName;
	}

	/**
	 * Returns the last extracted metadata.
	 *
	 * @return array|NULL
	 */
	public function getLastMetadata() {
		return $this->lastMetadata;
	}

	/**
	 * Localizes a label.
	 *
	 * @param string $input
	 * @return string
	 */
	protected function localize($input) {
		if (TYPO3_MODE === 'FE') {
			$output = is_object($GLOBALS['TSFE']) ? $GLOBALS['TSFE']->sL($input) : $input;
		} else {
			$output = $GLOBALS['LANG']->sL($input);
		}
		return $output;
	}

	/**
	 * Sends a notification.
	 *
	 * @param callback $callbackNotification Callback to send notification
	 * @param string $message
	 * @param integer $severity
	 */
	protected function notify($callbackNotification, $message, $severity) {
		$callableName = '';
		if (is_callable($callbackNotification, FALSE, $callableName)) {
			call_user_func($callbackNotification, $message, $severity);
		}
	}

	/**
	 * Returns the rule set that applies to a given file for a given backend user (or NULL
	 * if using scheduler task).
	 *
	 * @param string $sourceFileName
	 * @param string $targetFileName
	 * @param \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $backendUser
	 * @return array
	 */
	protected function getRuleset($sourceFileName, $targetFileName, \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $backendUser = NULL) {
		$ret = array();
		if (!is_file($sourceFileName)) {
			// Early return
			return $ret;
		}

		// Make file name relative and extract the extension
		$relTargetFileName = substr($targetFileName, strlen(PATH_site));
		$fileExtension = strtolower(substr($targetFileName, strrpos($targetFileName, '.') + 1));

		$beGroups = $backendUser !== NULL ? array_keys($GLOBALS['BE_USER']->userGroups) : array();

		// Try to find a matching ruleset
		foreach ($this->rulesets as $ruleset) {
			if (!is_array($ruleset['file_types'])) {
				// Default general settings do not include any watched image types
				continue;
			}
			if (count($ruleset['usergroup']) > 0 && (
					$backendUser === NULL ||
					count(array_intersect($ruleset['usergroup'], $beGroups)) == 0)
			) {
				// Backend user is not member of a group configured for the current rule set
				continue;
			}
			$processFile = FALSE;
			foreach ($ruleset['directories'] as $directoryPattern) {
				$processFile |= preg_match($directoryPattern, $relTargetFileName);
			}
			$processFile &= GeneralUtility::inArray($ruleset['file_types'], $fileExtension);
			$processFile &= (filesize($sourceFileName) > $ruleset['threshold']);
			if ($processFile) {
				$ret = $ruleset;
				break;
			}
		}
		return $ret;
	}

	/**
	 * Returns all directories found in the various rulesets.
	 *
	 * @return array
	 */
	public function getAllDirectories() {
		$directories = array();
		foreach ($this->rulesets as $ruleset) {
			$dirs = GeneralUtility::trimExplode(',', $ruleset['directories_config'], TRUE);
			$directories = array_merge($directories, $dirs);
		}
		$directories = array_unique($directories);
		asort($directories);
		return $directories;
	}

	/**
	 * Returns all file types found in the various rulesets.
	 *
	 * @return array
	 */
	public function getAllFileTypes() {
		$fileTypes = array();
		foreach ($this->rulesets as $ruleset) {
			if (is_array($ruleset['file_types'])) {
				$fileTypes = array_merge($fileTypes, $ruleset['file_types']);
			}
		}
		$fileTypes = array_unique($fileTypes);
		return $fileTypes;
	}

	/**
	 * Compiles all FlexForm rule sets.
	 *
	 * @param array $rulesets
	 * @return array
	 */
	protected function compileRulesets(array $rulesets) {
		$sheets = GeneralUtility::resolveAllSheetsInDS($rulesets);
		$rulesets = array();

		foreach ($sheets['sheets'] as $sheet) {
			$elements = $sheet['data']['sDEF']['lDEF']['ruleset']['el'];
			foreach ($elements as $container) {
				if (isset($container['container']['el'])) {
					$values = array();
					foreach ($container['container']['el'] as $key => $value) {
						if ($key === 'title') {
							continue;
						}
						$values[$key] = $value['vDEF'];
					}
					$rulesets[] = $this->expandValuesInRuleset($values);
				}
			}
		}

		return $rulesets;
	}

	/**
	 * Expands values of a rule set.
	 *
	 * @param array $ruleset
	 * @return array
	 */
	protected function expandValuesInRuleset(array $ruleset) {
		$values = array();
		foreach ($ruleset as $key => $value) {
			switch ($key) {
				case 'usergroup':
					$value = GeneralUtility::trimExplode(',', $value, TRUE);
					break;
				case 'directories':
					$values['directories_config'] = '';
					$value = GeneralUtility::trimExplode(',', $value, TRUE);
					// Sanitize name of the directories
					foreach ($value as &$directory) {
						$directory = rtrim($directory, '/') . '/';
						if (!empty($values['directories_config'])) {
							$values['directories_config'] .= ',';
						}
						$values['directories_config'] .= $directory;
						$directory = $this->getDirectoryPattern($directory);
					}
					if (count($value) == 0) {
						// Inherit configuration
						$value = '';
					}
					break;
				case 'file_types':
					$value = GeneralUtility::trimExplode(',', $value, TRUE);
					if (count($value) == 0) {
						// Inherit configuration
						$value = '';
					}
					break;
				case 'threshold':
					if (!is_numeric($value)) {
						$unit = strtoupper(substr($value, -1));
						$factor = 1 * ($unit === 'K' ? 1024 : ($unit === 'M' ? 1024 * 1024 : 0));
						$value = intval(trim(substr($value, 0, strlen($value) - 1))) * $factor;
					}
				// Beware: fall-back to next value processing
				case 'max_width':
				case 'max_height':
					if ($value <= 0) {
						// Inherit configuration
						$value = '';
					}
					break;
				case 'conversion_mapping':
					$mapping = GeneralUtility::trimExplode(',', $value, TRUE);
					if (count($mapping) > 0) {
						$value = $this->expandConversionMapping($mapping);
					} else {
						// Inherit configuration
						$value = '';
					}
					break;
			}
			$values[$key] = $value;
		}

		return $values;
	}

	/**
	 * Expands the image type conversion mapping.
	 *
	 * @param array $mapping Array of lines similar to "bmp => jpg", "tif => jpg"
	 * @return array Key/Value pairs of mapping: array('bmp' => 'jpg', 'tif' => 'jpg')
	 */
	protected function expandConversionMapping(array $mapping) {
		$ret = array();
		$matches = array();
		foreach ($mapping as $m) {
			if (preg_match('/^(.*)\s*=>\s*(.*)/', $m, $matches)) {
				$ret[trim($matches[1])] = trim($matches[2]);
			}
		}
		return $ret;
	}

	/**
	 * Returns a regular expression pattern to match directories.
	 *
	 * @param string $directory
	 * @return string
	 */
	protected function getDirectoryPattern($directory) {
		$pattern = '/^' . str_replace('/', '\\/', $directory) . '/';
		$pattern = str_replace('\\/**\\/', '\\/([^\/]+\\/)*', $pattern);
		$pattern = str_replace('\\/*\\/', '\\/[^\/]+\\/', $pattern);

		return $pattern;
	}

	/**
	 * Stores how many extra bytes have been freed.
	 *
	 * @param integer $bytes
	 * @return void
	 */
	protected function reportAdditionalStorageClaimed($bytes) {
		$fileName = PATH_site . 'typo3conf/.tx_imageautoresize';

		$data = array();
		if (file_exists($fileName)) {
			$data = json_decode(file_get_contents($fileName), TRUE);
			if (!is_array($data)) {
				$data = array();
			}
		}

		$data['bytes'] = $bytes + (isset($data['bytes']) ? (int)$data['bytes'] : 0);
		$data['images'] = 1 + (isset($data['images']) ? (int)$data['images'] : 0);

		GeneralUtility::writeFile($fileName, json_encode($data));
	}

}