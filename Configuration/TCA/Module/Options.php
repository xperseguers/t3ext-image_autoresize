<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TCA']['tx_imageautoresize'] = [
    'ctrl' => [
        'label' => 'title',
        'dividers2tabs' => true,
    ],
    'columns' => [
        'directories' => [
            'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xlf:tx_imageautoresize.directories',
            'config' => [
                'type' => 'input',
                'size' => '50',
                'max' => '255',
                'eval' => 'trim,required',
            ],
        ],
        'file_types' => [
            'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xlf:tx_imageautoresize.file_types',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'itemsProcFunc' => \Causal\ImageAutoresize\Tca\Graphics::class . '->getImageFileExtensions',
                'minitems' => '0',
                'maxitems' => '20',
                'size' => '6',
                'multiple' => '1',
            ],
        ],
        'threshold' => [
            'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xlf:tx_imageautoresize.threshold',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'trim,required',
            ],
        ],
        'max_width' => [
            'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xlf:tx_imageautoresize.max_width',
            'config' => [
                'type' => 'input',
                'size' => '5',
                'max' => '5',
                'eval' => 'int,required',
                'checkbox' => '0',
                'range' => [
                    'lower' => '100',
                ],
                'default' => 0,
            ],
        ],
        'max_height' => [
            'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xlf:tx_imageautoresize.max_height',
            'config' => [
                'type' => 'input',
                'size' => '5',
                'max' => '5',
                'eval' => 'int,required',
                'checkbox' => '0',
                'range' => [
                    'lower' => '100',
                ],
                'default' => 0,
            ],
        ],
        'auto_orient' => [
            'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xlf:tx_imageautoresize.auto_orient',
            'config' => [
                'type' => 'check',
            ],
        ],
        'keep_metadata' => [
            'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xlf:tx_imageautoresize.keep_metadata',
            'config' => [
                'type' => 'check',
            ],
        ],
        'resize_png_with_alpha' => [
            'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xlf:tx_imageautoresize.resize_png_with_alpha',
            'config' => [
                'type' => 'check',
            ],
        ],
        'conversion_mapping' => [
            'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xlf:tx_imageautoresize.conversion_mapping',
            'config' => [
                'type' => 'text',
                'cols' => '20',
                'rows' => '5',
                'eval' => 'trim',
            ],
        ],
        'rulesets' => [
            'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xlf:tx_imageautoresize.rulesets',
            'config' => [
                'type' => 'flex',
                'ds_pointerField' => 'list_type',
                'ds' => [
                    'default' => 'FILE:EXT:image_autoresize/Configuration/FlexForms/Rulesets.xml',
                ],
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' =>
            'directories,threshold,file_types,
			--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:ALT.imgDimensions;dimensions,
			--div--;LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xlf:tabs.options,
				auto_orient,keep_metadata,resize_png_with_alpha,conversion_mapping,
			--div--;LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xlf:tabs.rulesets,
				rulesets
		'],
    ],
    'palettes' => [
        'dimensions' => ['showitem' => 'max_width,max_height'],
    ],
];
