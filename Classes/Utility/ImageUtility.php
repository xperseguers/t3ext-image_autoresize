<?php
namespace Causal\ImageAutoresize\Utility;

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

/**
 * This is a generic image utility.
 *
 * @category    Utility
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class ImageUtility {

	/**
	 * Returns the EXIF orientation of a given picture.
	 *
	 * @param string $fileName
	 * @return integer
	 */
	static public function getOrientation($fileName) {
		$orientation = 1; // Fallback to "straight"
		$metadata = static::getMetadata($fileName);
		if (isset($metadata['Orientation'])) {
			$orientation = $metadata['Orientation'];
		}
		return $orientation;
	}

	/**
	 * Returns metadata from a given file.
	 *
	 * @param string $fileName
	 * @return array
	 */
	static public function getMetadata($fileName) {
		$extension = strtolower(substr($fileName, strrpos($fileName, '.') + 1));
		$metadata = array();
		if (GeneralUtility::inList('jpg,jpeg,tif,tiff', $extension) && function_exists('exif_read_data')) {
			$exif = @exif_read_data($fileName);
			if ($exif) {
				$metadata = $exif;

				// Process the longitude/latitude/altitude
				if (isset($metadata['GPSLatitude']) && is_array($metadata['GPSLatitude'])) {
					$reference = isset($metadata['GPSLatitudeRef']) ? $metadata['GPSLatitudeRef'] : 'N';
					$decimal = static::rationalToDecimal($metadata['GPSLatitude']);
					$decimal *= $reference === 'N' ? 1 : -1;
					$metadata['GPSLatitudeDecimal'] = $decimal;
				}
				if (isset($metadata['GPSLongitude']) && is_array($metadata['GPSLongitude'])) {
					$reference = isset($metadata['GPSLongitudeRef']) ? $metadata['GPSLongitudeRef'] : 'E';
					$decimal = static::rationalToDecimal($metadata['GPSLongitude']);
					$decimal *= $reference === 'E' ? 1 : -1;
					$metadata['GPSLongitudeDecimal'] = $decimal;
				}
				if (isset($metadata['GPSAltitude'])) {
					$rationalParts = explode('/', $metadata['GPSAltitude']);
					$metadata['GPSAltitudeDecimal'] = $rationalParts[0] / $rationalParts[1];
				}
			}
			// Try to extract IPTC data
			$imageinfo = array();
			if (function_exists('iptcparse') && getimagesize($fileName, $imageinfo)) {
				if (isset($imageinfo['APP13'])) {
					$data = iptcparse($imageinfo['APP13']);
					$mapping = array(
						'2#005' => 'Title',
						'2#025' => 'Keywords',
						'2#040' => 'Instructions',
						'2#080' => 'Creator',
						'2#085' => 'CreatorFunction',
						'2#090' => 'City',
						'2#092' => 'Location',
						'2#095' => 'Region',
						'2#100' => 'CountryCode',
						'2#101' => 'Country',
						'2#103' => 'IdentifierWork',
						'2#105' => 'CreatorTitle',
						'2#110' => 'Credit',
						'2#115' => 'Source',
						'2#116' => 'Copyright',
						'2#120' => 'Description',
						'2#122' => 'DescriptionAuthor',
					);
					foreach ($mapping as $iptcKey => $metadataKey) {
						if (isset($data[$iptcKey])) {
							$metadata['IPTC' . $metadataKey] = $data[$iptcKey][0];
						}
					}
				}
			}
		}
		return $metadata;
	}

	/**
	 * Converts an EXIF rational into its decimal representation.
	 *
	 * @param array $components
	 * @return float
	 */
	static protected function rationalToDecimal(array $components) {
		foreach ($components as $key => $value) {
			$rationalParts = explode('/', $value);
			$components[$key] = $rationalParts[0] / $rationalParts[1];
		}
		list($hours, $minutes, $seconds) = $components;

		return $hours + ($minutes / 60) + ($seconds / 3600);
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
	 * @param string $fileName
	 * @return void
	 * @see http://sylvana.net/jpegcrop/exif_orientation.html
	 */
	static public function resetOrientation($fileName) {
		\Causal\ImageAutoresize\Utility\JpegExifOrient::setOrientation($fileName, 1);
	}

	/**
	 * Returns TRUE if the given PNG file contains transparency information.
	 *
	 * @param string $fileName
	 * @return boolean
	 */
	static public function isTransparentPng($fileName) {
		$bytes = file_get_contents($fileName, FALSE, NULL, 24, 2);	// read 24th and 25th bytes
		$byte24 = ord($bytes{0});
		$byte25 = ord($bytes{1});
		if ($byte24 === 16 || $byte25 === 6 || $byte25 === 4) {
			return TRUE;
		} else {
			$content = file_get_contents($fileName);
			return strpos($content, 'tRNS') !== FALSE;
		}
	}

}
