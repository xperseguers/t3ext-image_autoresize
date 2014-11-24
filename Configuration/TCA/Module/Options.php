<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TCA']['tx_imageautoresize'] = array(
	'ctrl' => array(
		'label' => 'title',
		'dividers2tabs' => TRUE,
	),
	'columns' => array(
		'directories' => array(
			'label'   => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xml:tx_imageautoresize.directories',
			'config'  => array(
				'type' => 'input',
				'size' => '50',
				'max' => '255',
				'eval' => 'trim,required',
			),
		),
		'file_types' => array(
			'label'   => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xml:tx_imageautoresize.file_types',
			'config'  => array(
				'type' => 'select',
				'itemsProcFunc' => 'tx_imageautoresize_module1->getImageFileExtensions',
				'minitems' => '0',
				'maxitems' => '20',
				'size' => '6',
				'multiple' => '1',
			),
		),
		'threshold' => array(
			'label'   => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xml:tx_imageautoresize.threshold',
			'config'  => array(
				'type' => 'input',
				'size' => '10',
				'max' => '10',
				'eval' => 'trim,required',
			),
		),
		'max_width' => array(
			'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xml:tx_imageautoresize.max_width',
			'config' => array(
				'type' => 'input',
				'size' => '5',
				'max' => '5',
				'eval' => 'int,required',
				'checkbox' => '0',
				'range' => array(
					'upper' => '',
					'lower' => '100',
				),
				'default' => 0,
			),
		),
		'max_height' => array(
			'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xml:tx_imageautoresize.max_height',
			'config' => array(
				'type' => 'input',
				'size' => '5',
				'max' => '5',
				'eval' => 'int,required',
				'checkbox' => '0',
				'range' => array(
					'upper' => '',
					'lower' => '100',
				),
				'default' => 0,
			),
		),
		'auto_orient' => array(
			'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xml:tx_imageautoresize.auto_orient',
			'config' => array(
				'type' => 'check',
			),
		),
		'keep_metadata' => array(
			'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xml:tx_imageautoresize.keep_metadata',
			'config' => array(
				'type' => 'check',
			),
		),
		'resize_png_with_alpha' => array(
			'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xml:tx_imageautoresize.resize_png_with_alpha',
			'config' => array(
				'type' => 'check',
			),
		),
		'conversion_mapping' => array(
			'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xml:tx_imageautoresize.conversion_mapping',
			'config' => array(
				'type' => 'text',
				'cols' => '20',
				'rows' => '5',
				'eval' => 'trim',
			),
		),
		'rulesets' => array(
			'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xml:tx_imageautoresize.rulesets',
			'config' => array(
				'type' => 'flex',
				'ds_pointerField' => 'list_type',
				'ds' => array(
					'default' => 'FILE:EXT:image_autoresize/Configuration/FlexForms/Rulesets.xml',
				),
			),
		),
    ),
	'types' => array(
		'0' => array('showitem' =>
				'directories,threshold,file_types,
			--palette--;LLL:EXT:cms/locallang_ttc.php:ALT.imgDimensions;1,
			--div--;LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xml:tabs.options,
				auto_orient,keep_metadata,resize_png_with_alpha,conversion_mapping,
			--div--;LLL:EXT:image_autoresize/Resources/Private/Language/locallang_tca.xml:tabs.rulesets,
				rulesets
		'),
	),
	'palettes' => array(
		'1' => array('showitem' => 'max_width,max_height'),
	),
);
