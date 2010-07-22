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
    ),
	'types' => array(
		'0' => array('showitem' =>
				'directories;;3;;2-2-2, filetypes,
			--div--;LLL:EXT:image_autoresize/locallang_tca.xml:tabs.begroups,
				subtitle,
		'),
    ),
);

?>