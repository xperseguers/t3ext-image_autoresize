.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _batch-processing:

Batch processing images
-----------------------

When installed in TYPO3 6.0 or above, this extension provides a scheduler task to batch process uploaded images in
directory :file:`fileadmin/`.

It is particularly useful if you let users upload images outside of TYPO3 (e.g., using FTP), thus bypassing upload
post-processing to automatically resize them according to your rule sets.
