.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _signal-post-processing-configuration:

Post-processing Configuration
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. _processing-processConfiguration:

Slot: processConfiguration
""""""""""""""""""""""""""

This slot is used to post-process the configuration of the extension.

Your slot should implement a method of the form:

.. code-block:: php

   public function postProcessConfiguration(array &$configuration)
   {
       // Custom code
   }


Registering the slots
~~~~~~~~~~~~~~~~~~~~~

In your extension, open :file:`EXT:{extension-key}/ext_localconf.php` and add:

.. code-block:: php

   /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
   $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
       \TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class
   );

   $signalSlotDispatcher->connect(
       \Causal\ImageAutoresize\Controller\ConfigurationController::class,
       'processConfiguration',
       \Company\MyExt\Slots\ImageAutoresize::class,
       'postProcessConfiguration'
   );
