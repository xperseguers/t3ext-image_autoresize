<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Xavier Perseguers (typo3@perseguers.ch)
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

if (!version_compare(TYPO3_version, '4.4.99', '>')) {
	include_once(t3lib_extMgm::extPath('image_autoresize') . 'interfaces/interface.t3lib_extfilefunctions_processdatahook.php');
}

/**
 * This class extends t3lib_extFileFunctions to automatically resize
 * huge pictures upon upload.
 *
 * @category    Hook
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <typo3@perseguers.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class user_t3lib_extFileFunctions_hook implements t3lib_extFileFunctions_processDataHook {

	/**
	 * @var array
	 */
	protected $config;

	/**
	 * Default constructor.
	 */
	public function __construct() {
		$this->config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['image_autoresize']);

		$this->config['directories'] = t3lib_div::trimExplode(',', $this->config['directories']);
		$this->config['filetypes'] = t3lib_div::trimExplode(',', $this->config['filetypes']);
		$this->config['threshold'] = $this->convertHumanSizeToBytes(trim($this->config['threshold']));

			// Sanitize name of the directories
		foreach ($this->config['directories'] as &$directory) {
			$directory = rtrim($directory, '/') . '/';
		}
	}

	/**
	 * Post process upload of a picture and make sure it is not too big.
	 *
	 * @param string The action
	 * @param array The parameter sent to the action handler
	 * @param array The results of all calls to the action handler 
	 * @param t3lib_extFileFunctions parent object
	 * @return void
	 */
	public function processData_postProcessAction($action, array $cmdArr, array $result, t3lib_extFileFunctions $pObj) {
		if ($action !== 'upload') {
				// Early return
			return;
		}

			// Get the latest uploaded file name
		$filename = array_pop($result);
		$relFilename = substr($filename, strlen(PATH_site));
		$imgExt = strtolower(substr($filename, strrpos($filename, '.') + 1));

		$processFile = FALSE;
		foreach ($this->config['directories'] as $directory) {
			$processFile |= t3lib_div::isFirstPartOfStr($relFilename, $directory);
		}
		$processFile &= t3lib_div::inArray($this->config['filetypes'], $imgExt);

		if (!$processFile) {
				// Early return: directory and/or file type not configured
			return;
		}

		if (filesize($filename) > $this->config['threshold']) {
				// Image is bigger than allowed, will now resize it to (hopefully) make it lighter
			$gifCreator = t3lib_div::makeInstance('tslib_gifbuilder');
			$gifCreator->init();
			$gifCreator->absPrefix = PATH_site;
	
			$hash = t3lib_div::shortMD5($filename);
			$dest = $gifCreator->tempPath . $hash . '.' . $imgExt;
			$options = array(
				'maxW' => $this->config['maxWidth'],
				'maxH' => $this->config['maxHeight'],
			);

			$tempFileInfo = $gifCreator->imageMagickConvert($filename, '', '', '', '', '', $options);
			if ($tempFileInfo) {
					// Replace original file
				@unlink($filename);
				@rename($tempFileInfo[3], $filename);

				$this->notify(
					sprintf('Image %s has been automatically resized to %sx%s pixels', $relFilename, $tempFileInfo[0], $tempFileInfo[1]),
					t3lib_FlashMessage::INFO
				);
			}
		}
	}

	/**
	 * Converts a human file size into bytes.
	 *
	 * @param string $size
	 * @return integer
	 */
	protected function convertHumanSizeToBytes($size) {
		if (!is_numeric($size)) {
			$unit = strtoupper(substr($size, -1));
			$factor = 1 * ($unit === 'K' ? 1024 : ($unit === 'M' ? 1024 * 1024 : 0));
			$size = intval(trim(substr($size, 0, strlen($size) - 1))) * $factor;
		}
		return $size;
	}

	/**
	 * Notifies the user using a Flash message.
	 *
	 * @param string $message The message
	 * @param integer $severity Optional severity, must be either of t3lib_FlashMessage::INFO, t3lib_FlashMessage::OK,
	 *                          t3lib_FlashMessage::WARNING or t3lib_FlashMessage::ERROR. Default is t3lib_FlashMessage::OK.
	 * @return void
	 */
	protected function notify($message, $severity = t3lib_FlashMessage::OK) {
		$flashMessage = t3lib_div::makeInstance(
			't3lib_FlashMessage',
			$message,
			'',
			$severity,
			TRUE
		);
		t3lib_FlashMessageQueue::addMessage($flashMessage);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/image_autoresize/classes/class.user_t3lib_extfilefunctions_hook.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/image_autoresize/classes/class.user_t3lib_extfilefunctions_hook.php']);
}
?>