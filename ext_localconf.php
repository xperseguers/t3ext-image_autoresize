<?php
if (!defined ('TYPO3_MODE')) {
	die('Access denied.');
}

if (version_compare(TYPO3_version, '6.0.0', '>=')) {
	if (version_compare(TYPO3_branch, '6.2', '>=')) {

		/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
		$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');

		// Hook into \TYPO3\CMS\Core\Resource\ResourceStorage
		$signalSlotDispatcher->connect(
			'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
			'preFileAdd',
			'Causal\\ImageAutoresize\\Slots\\FileUpload',
			'autoResize'
		);
		$signalSlotDispatcher->connect(
			'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
			'postFileAdd',
			'Causal\\ImageAutoresize\\Slots\\FileUpload',
			'populateMetadata'
		);
		$signalSlotDispatcher->connect(
			'TYPO3\\CMS\\Extensionmanager\\ViewHelpers\\ProcessAvailableActionsViewHelper',
			'processActions',
			'Causal\\ImageAutoresize\\Slots\\ExtensionManager',
			'processActions'
		);

	} else { // TYPO3 6.0 and TYPO3 6.1

		// Uploads in fileadmin/
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'][] = 'Causal\\ImageAutoresize\\Hook\\FileUploadHook';
		// Uploads in uploads/
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processUpload'][] = 'Causal\\ImageAutoresize\\Hook\\FileUploadHook';

	}

	$extensionName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($_EXTKEY);
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Causal\\ImageAutoresize\\Task\\BatchResizeTask'] = array(
		'extension' => $_EXTKEY,
		'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf:batchResizeTask.name',
		'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf:batchResizeTask.description',
		'additionalFields' => 'Causal\\ImageAutoresize\\Task\\BatchResizeAdditionalFieldProvider',
	);

} else {

	// Uploads in fileadmin/
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'][] = 'EXT:' . $_EXTKEY . '/Classes/v4/class.user_fileupload_hooks.php:user_fileUpload_hooks';
	// Uploads in uploads/
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processUpload'][] = 'EXT:' . $_EXTKEY . '/Classes/v4/class.user_fileupload_hooks.php:user_fileUpload_hooks';
	// Uploads when using DAM
	$TYPO3_CONF_VARS['EXTCONF']['dam']['fileTriggerClasses'][] = 'EXT:' . $_EXTKEY . '/Classes/v4/class.user_fileupload_hooks.php:user_fileUpload_hooks';

}
