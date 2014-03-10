.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _signal-post-processing-images:

Post-processing Images
^^^^^^^^^^^^^^^^^^^^^^

.. _processing-afterImageResize:

Slot: afterImageResize
""""""""""""""""""""""

This slot is used to post-process the resized image.

Your slot should implement a method of the form:

.. code-block:: php

	public function postProcessImageResize($operation, $source, $destination,
	                                       &$newWidth, &$newHeight) {
	    // Custom code
	}

Parameter ``$operation`` is either ``RESIZE`` if ``$source`` was resized or ``RESIZE_CONVERT`` if ``$source`` was first
resized and then converted to another file format.


Registering the slots
~~~~~~~~~~~~~~~~~~~~~

In your extension, open :file:`EXT:{extension-key}/ext_localconf.php` and add:

.. code-block:: php

	/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
	$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
	    'TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher'
	);

	$signalSlotDispatcher->connect(
	    'Causal\\ImageAutoresize\\Service\\ImageResizer',
	    'afterImageResize',
	    'Company\\MyExt\\Slots\\ImageResizer',
	    'postProcessImageResize'
	);
