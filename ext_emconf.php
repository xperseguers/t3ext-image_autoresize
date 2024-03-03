<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "image_autoresize".
 *
 * Auto generated 29-11-2014 17:27
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'Resize images automatically',
    'description' => 'Simplify the way your editors may upload their images: no complex local procedure needed, let TYPO3 automatically resize down their huge images/pictures on-the-fly during upload (or using a command for batch processing) and according to your own business rules (directory/groups). This will highly reduce the footprint on your server and speed-up response time if lots of images are rendered (e.g., in a gallery). Features an EXIF/IPTC extractor to ensure metadata may be used by the FAL indexer even if not preserved upon resizing.',
    'category' => 'be',
    'author' => 'Xavier Perseguers',
    'author_email' => 'xavier@causal.ch',
    'state' => 'stable',
    'author_company' => 'Causal Sàrl',
    'version' => '2.4.1',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.3.99',
            'typo3' => '10.4.0-13.0.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => ['Causal\\ImageAutoresize\\' => 'Classes']
    ],
];
