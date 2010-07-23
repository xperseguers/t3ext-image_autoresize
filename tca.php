<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$TCA['tx_imageautoresize_expert'] = array(
    'ctrl' => array(
		'label' => 'title',
		'dividers2tabs' => TRUE,
	),
    'columns' => array(
        'directories' => array(
            'label'   => 'LLL:EXT:image_autoresize/locallang_tca.xml:tx_imageautoresize_expert.directories',
            'config'  => array(
                'type' => 'input',
				'size' => '50',
				'max' => '255',
				'eval' => 'trim',
            ),
        ),
        'file_types' => array(
            'label'   => 'LLL:EXT:image_autoresize/locallang_tca.xml:tx_imageautoresize_expert.file_types',
            'config'  => array(
                'type' => 'input',
				'size' => '30',
				'max' => '255',
				'eval' => 'trim',
            ),
        ),
        'threshold' => array(
            'label'   => 'LLL:EXT:image_autoresize/locallang_tca.xml:tx_imageautoresize_expert.threshold',
            'config'  => array(
                'type' => 'input',
				'size' => '10',
				'max' => '10',
				'eval' => 'trim',
            ),
        ),
        'max_width' => array(
			'label' => 'LLL:EXT:image_autoresize/locallang_tca.xml:tx_imageautoresize_expert.max_width',
			'config' => array(
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => array(
					'upper' => '',
					'lower' => '100',
				),
				'default' => 0,
			),
		),
		'max_height' => array(
			'label' => 'LLL:EXT:image_autoresize/locallang_tca.xml:tx_imageautoresize_expert.max_height',
			'config' => array(
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => array(
					'upper' => '',
					'lower' => '100',
				),
				'default' => 0,
			),
		),
		'auto_orient' => array(
			'label' => 'LLL:EXT:image_autoresize/locallang_tca.xml:tx_imageautoresize_expert.auto_orient',
			'config' => array(
				'type' => 'check',
			),
		),
		'keep_metadata' => array(
			'label' => 'LLL:EXT:image_autoresize/locallang_tca.xml:tx_imageautoresize_expert.keep_metadata',
			'config' => array(
				'type' => 'check',
			),
		),
		'rulesets' => array(
			'label' => 'LLL:EXT:image_autoresize/locallang_tca.xml:tx_imageautoresize_expert.rulesets',
			'config' => array(
				'type' => 'flex',
				'ds_pointerField' => 'list_type',
				'ds' => array(
					'default' => 'FILE:EXT:image_autoresize/flexform.xml',
				),
			),
		),
    ),
	'types' => array(
		'0' => array('showitem' =>
				'directories,threshold,file_types,
			--palette--;LLL:EXT:cms/locallang_ttc.php:ALT.imgDimensions;1,
			--div--;LLL:EXT:image_autoresize/locallang_tca.xml:tabs.options,
				auto_orient,keep_metadata,
			--div--;LLL:EXT:image_autoresize/locallang_tca.xml:tabs.rulesets,
				rulesets
		'),
    ),
    'palettes' => array(
		'1' => array('showitem' => 'max_width,max_height'),
    ),
);

?>