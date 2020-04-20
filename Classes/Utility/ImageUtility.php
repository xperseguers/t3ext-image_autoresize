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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * This is a generic image utility.
 *
 * @category    Utility
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */
class ImageUtility
{

    /**
     * Returns the EXIF orientation of a given picture.
     *
     * @param string $fileName
     * @return int
     */
    public static function getOrientation(string $fileName): int
    {
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
     * @param bool $fullExtract
     * @return array
     */
    public static function getMetadata(string $fileName, bool $fullExtract = false): array
    {
        $metadata = static::getBasicMetadata($fileName);

        if ($fullExtract && !empty($metadata)) {
            $virtualFileObject = static::getVirtualFileObject($fileName, $metadata);
            $extractorRegistry = \TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance();
            $extractionServices = $extractorRegistry->getExtractorsWithDriverSupport('Local');

            $newMetadata = [
                0 => $metadata,
            ];

            $typo3Branch = class_exists(\TYPO3\CMS\Core\Information\Typo3Version::class)
                ? (new \TYPO3\CMS\Core\Information\Typo3Version())->getBranch()
                : TYPO3_branch;
            if (version_compare($typo3Branch, '10.0', '>=')) {
                foreach ($extractionServices as $priority => $services) {
                    foreach ($services as $service) {
                        if ($service->canProcess($virtualFileObject)) {
                            $newMetadata[$priority] = $service->extractMetaData($virtualFileObject, $newMetadata);
                        }
                    }
                }
            } else {
                foreach ($extractionServices as $service) {
                    if ($service->canProcess($virtualFileObject)) {
                        $newMetadata[$service->getPriority()] = $service->extractMetaData($virtualFileObject, $newMetadata);
                    }
                }
            }

            ksort($newMetadata);
            foreach ($newMetadata as $data) {
                $metadata = array_merge($metadata, $data);
            }
        }

        return $metadata;
    }

    /**
     * Creates a virtual File object to be used transparently by external
     * metadata extraction services as if it would come from standard FAL.
     *
     * @param string $fileName
     * @param array $metadata
     * @return File
     */
    protected static function getVirtualFileObject(string $fileName, array $metadata): File
    {
        /** @var ResourceFactory $resourceFactory */
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        $recordData = [
            'uid' => 0,
            'pid' => 0,
            'name' => 'Temporary Upload Storage',
            'description' => 'Internal storage, mounting the temporary PHP upload directory.',
            'driver' => 'Local',
            'processingfolder' => '',
            // legacy code
            'configuration' => '',
            'is_online' => true,
            'is_browsable' => false,
            'is_public' => false,
            'is_writable' => false,
            'is_default' => false,
        ];
        $storageConfiguration = [
            'basePath' => PathUtility::dirname($fileName),
            'pathType' => 'absolute'
        ];

        $virtualStorage = $resourceFactory->createStorageObject($recordData, $storageConfiguration);
        $name = PathUtility::basename($fileName);
        $extension = strtolower(substr($name, strrpos($name, '.') + 1));

        /** @var File $virtualFileObject */
        $virtualFileObject = GeneralUtility::makeInstance(
            File::class,
            [
                'identifier' => '/' . $name,
                'name' => $name,
                'extension' => $extension,
            ],
            $virtualStorage,
            $metadata
        );

        return $virtualFileObject;
    }

    /**
     * Returns metadata from a given file using basic, built-in, PHP-based extractor.
     *
     * @param string $fileName
     * @return array
     */
    protected static function getBasicMetadata(string $fileName): array
    {
        $extension = strtolower(substr($fileName, strrpos($fileName, '.') + 1));
        $metadata = [];
        if (GeneralUtility::inList('jpg,jpeg,tif,tiff', $extension) && function_exists('exif_read_data')) {
            $exif = @exif_read_data($fileName);
            if ($exif) {
                $metadata = $exif;
                // Fix description coming from EXIF
                $metadata['ImageDescription'] = static::safeUtf8Encode($metadata['ImageDescription']);

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
                    if (!empty($rationalParts[1])) {
                        $metadata['GPSAltitudeDecimal'] = $rationalParts[0] / $rationalParts[1];
                    } else {
                        $metadata['GPSAltitudeDecimal'] = 0;
                    }
                }
            }
            // Try to extract IPTC data
            $imageinfo = [];
            if (function_exists('iptcparse') && getimagesize($fileName, $imageinfo)) {
                if (isset($imageinfo['APP13'])) {
                    $data = iptcparse($imageinfo['APP13']);
                    $mapping = [
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
                    ];
                    foreach ($mapping as $iptcKey => $metadataKey) {
                        if (isset($data[$iptcKey])) {
                            $metadata['IPTC' . $metadataKey] = static::safeUtf8Encode($data[$iptcKey][0]);
                        }
                    }
                }
            }
        }
        return $metadata;
    }

    /**
     * Safely converts some text to UTF-8.
     *
     * @param string|null $text
     * @return string|null
     */
    protected static function safeUtf8Encode(?string $text): ?string
    {
        if (function_exists('mb_detect_encoding')) {
            if (mb_detect_encoding($text, 'UTF-8', true) !== 'UTF-8') {
                $text = utf8_encode($text);
            }
        } else {
            // Fall back to hack
            $encodedText = utf8_encode($text);
            if (strpos($encodedText, 'Ã') === false) {
                $text = $encodedText;
            }
        }
        return $text;
    }

    /**
     * Converts an EXIF rational into its decimal representation.
     *
     * @param array $components
     * @return float
     */
    protected static function rationalToDecimal(array $components): float
    {
        foreach ($components as $key => $value) {
            $rationalParts = explode('/', $value);
            if (!empty($rationalParts[1])) {
                $components[$key] = $rationalParts[0] / $rationalParts[1];
            } else {
                $components[$key] = 0;
            }
        }
        list($hours, $minutes, $seconds) = $components;

        return $hours + ($minutes / 60) + ($seconds / 3600);
    }

    /**
     * Returns true if the given picture is rotated.
     *
     * @param int $orientation EXIF orientation
     * @return bool
     * @see http://www.impulseadventure.com/photo/exif-orientation.html
     */
    public static function isRotated(int $orientation): bool
    {
        $ret = false;
        switch ($orientation) {
            case 2: // horizontal flip
            case 3: // 180°
            case 4: // vertical flip
            case 5: // vertical flip + 90 rotate right
            case 6: // 90° rotate right
            case 7: // horizontal flip + 90 rotate right
            case 8: // 90° rotate left
                $ret = true;
                break;
        }
        return $ret;
    }

    /**
     * Returns a command line parameter to fix the orientation of a rotated picture.
     *
     * @param int $orientation
     * @return string
     */
    public static function getTransformation(int $orientation): string
    {
        $typo3Branch = class_exists(\TYPO3\CMS\Core\Information\Typo3Version::class)
            ? (new \TYPO3\CMS\Core\Information\Typo3Version())->getBranch()
            : TYPO3_branch;
        if (version_compare($typo3Branch, '9.0', '>=')) {
            return '-auto-orient';
        }

        $transformation = '';
        if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['processor'] !== 'GraphicsMagick') {
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
     * @see http://sylvana.net/jpegcrop/exif_orientation.html
     */
    public static function resetOrientation(string $fileName): void
    {
        JpegExifOrient::setOrientation($fileName, 1);
    }

    /**
     * Returns true if the given PNG file contains transparency information.
     *
     * @param string $fileName
     * @return bool
     */
    public static function isTransparentPng(string $fileName): bool
    {
        $bytes = file_get_contents($fileName, false, null, 24, 2);    // read 24th and 25th bytes
        $byte24 = ord(substr($bytes, 0, 1));
        $byte25 = ord(substr($bytes, 1, 1));
        if ($byte24 === 16 || $byte25 === 6 || $byte25 === 4) {
            return true;
        } else {
            $content = file_get_contents($fileName);
            return strpos($content, 'tRNS') !== false;
        }
    }

    /**
     * Returns true if the given GIF file is animated.
     *
     * @param string $fileName
     * @return bool
     * @throws \RuntimeException
     */
    public static function isAnimatedGif(string $fileName): bool
    {
        if (($fh = fopen($fileName, 'rb')) === false) {
            throw new \RuntimeException('Can\'t open ' . $fileName, 1454678600);
        }
        $count = 0;
        // An animated gif contains multiple "frames", with each frame having a
        // header made up of:
        // - a static 4-byte sequence (\x00\x21\xF9\x04)
        // - 4 variable bytes
        // - a static 2-byte sequence (\x00\x2C)

        // We read through the file til we reach the end of the file, or we've found
        // at least 2 frame headers
        while (!feof($fh) && $count < 2) {
            $chunk = fread($fh, 1024 * 100); // read 100kb at a time
            $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
        }

        fclose($fh);
        return $count > 1;
    }

}
