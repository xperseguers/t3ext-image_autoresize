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

/**
 * This is a generic image utility.
 *
 * @category    Service
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class ImageUtility {

	/**
	 * Returns the EXIF orientation of a given picture.
	 *
	 * @param string $filename
	 * @return integer
	 */
	static public function getOrientation($filename) {
		$extension = strtolower(substr($filename, strrpos($filename, '.') + 1));
		$orientation = 1; // Fallback to "straight"
		if (GeneralUtility::inList('jpg,jpeg,tif,tiff', $extension) && function_exists('exif_read_data')) {
			$exif = exif_read_data($filename);
			if ($exif) {
				$orientation = $exif['Orientation'];
			}
		}
		return $orientation;
	}

	/**
	 * Returns TRUE if the given picture is rotated.
	 *
	 * @param integer $orientation EXIF orientation
	 * @return integer
	 * @see http://www.impulseadventure.com/photo/exif-orientation.html
	 */
	static public function isRotated($orientation) {
		$ret = FALSE;
		switch ($orientation) {
			case 2: // horizontal flip
			case 3: // 180°
			case 4: // vertical flip
			case 5: // vertical flip + 90 rotate right
			case 6: // 90° rotate right
			case 7: // horizontal flip + 90 rotate right
			case 8: // 90° rotate left
				$ret = TRUE;
				break;
		}
		return $ret;
	}

	/**
	 * Returns a command line parameter to fix the orientation of a rotated picture.
	 *
	 * @param integer $orientation
	 * @return string
	 */
	static public function getTransformation($orientation) {
		$transformation = '';
		if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5'] !== 'gm') {
			// ImageMagick
			if ($orientation >= 2 && $orientation <= 8) {
				$transformation = '-auto-orient';
			}
		} else {
			// GraphicsMagick
			switch ($orientation) {
				case 2: // horizontal flip
					$transformation = '-flip horizontal';
					break;
				case 3: // 180°
					$transformation = '-rotate 180';
					break;
				case 4: // vertical flip
					$transformation = '-flip vertical';
					break;
				case 5: // vertical flip + 90 rotate right
					$transformation = '-transpose';
					break;
				case 6: // 90° rotate right
					$transformation = '-rotate 90';
					break;
				case 7: // horizontal flip + 90 rotate right
					$transformation = '-transverse';
					break;
				case 8: // 90° rotate left
					$transformation = '-rotate 270';
					break;
			}
		}
		return $transformation;
	}

	/**
	 * Resets the EXIF orientation flag of a picture.
	 *
	 * @param string $filename
	 * @return void
	 * @see http://sylvana.net/jpegcrop/exif_orientation.html
	 */
	static public function resetOrientation($filename) {
		\Causal\ImageAutoresize\Utility\JpegExifOrient::setOrientation($filename, 1);
	}

	/**
	 * Returns TRUE if the given PNG file contains transparency information.
	 *
	 * @param string $filename
	 * @return boolean
	 */
	static public function isTransparentPng($filename) {
		$bytes = file_get_contents($filename, FALSE, NULL, 24, 2);	// read 24th and 25th bytes
		$byte24 = ord($bytes{0});
		$byte25 = ord($bytes{1});
		if ($byte24 === 16 || $byte25 === 6 || $byte25 === 4) {
			return TRUE;
		} else {
			$content = file_get_contents($filename);
			return strpos($content, 'tRNS') !== FALSE;
		}
	}

}
