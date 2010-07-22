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
            'exclude' => 0,
            'label'   => 'LLL:EXT:image_autoresize/locallang_tca.xml:tx_imageautoresize_expert.directories',
            'config'  => array(
                'type' => 'input',
				'size' => '50',
				'max' => '255',
				'eval' => 'trim'
            )
        ),
        'filetypes' => array(
            'exclude' => 0,
            'label'   => 'LLL:EXT:image_autoresize/locallang_tca.xml:tx_imageautoresize_expert.filetypes',
            'config'  => array(
                'type' => 'input',
				'size' => '30',
				'max' => '255',
				'eval' => 'trim'
            )
        ),
        'threshold' => array(
            'exclude' => 0,
            'label'   => 'LLL:EXT:image_autoresize/locallang_tca.xml:tx_imageautoresize_expert.threshold',
            'config'  => array(
                'type' => 'input',
				'size' => '10',
				'max' => '10',
				'eval' => 'trim'
            )
        ),
        'maxwidth' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:image_autoresize/locallang_tca.xml:tx_imageautoresize_expert.maxwidth',
			'config' => array(
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => array(
					'upper' => '',
					'lower' => '100'
				),
				'default' => 0
			)
		),
		'maxheight' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:image_autoresize/locallang_tca.xml:tx_imageautoresize_expert.maxheight',
			'config' => array(
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => array(
					'upper' => '',
					'lower' => '100'
				),
				'default' => 0
			)
		),
		'feature' => array(
			'exclude' => 0,
			'label' => 'Feature',
			'config' => array(
				'type' => 'flex',
				'ds_pointerField' => 'list_type',
				'ds' => array(
					'default' => 'FILE:EXT:image_autoresize/flexform.xml',
				),
			),
		),
		'usergroup' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.usergroup',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'be_groups',
				'foreign_table_where' => 'ORDER BY be_groups.title',
				'size' => 5,
				'minitems' => 1,
				'maxitems' => 99,
				'autoSizeMax' => 10,
			),
		),
    ),
	'types' => array(
		'0' => array('showitem' =>
				'directories,threshold,filetypes,
			--palette--;LLL:EXT:cms/locallang_ttc.php:ALT.imgDimensions;1,
			--div--;LLL:EXT:image_autoresize/locallang_tca.xml:tabs.begroups,
				feature, usergroup
		'),
    ),
    'palettes' => array(
		'1' => array('showitem' => 'maxwidth,maxheight'),
    ),
);

?>