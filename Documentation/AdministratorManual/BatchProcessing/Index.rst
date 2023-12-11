.. include:: ../../Includes.rst.txt
.. _batch-processing:

Batch processing images
-----------------------

This extension provides a command to batch process uploaded images in the
directories you normally watch for image upload (see :ref:`general-settings`).

It is particularly useful if you let users upload images outside of TYPO3 (e.g.,
using FTP), thus bypassing upload post-processing to automatically resize them
according to your rule sets. The command may be invoked from the command line
using:

.. code:: bash

   ./vendor/bin/typo3 imageautoresize:batchresize

As such, when you create a new scheduler task, you need to choose "Execute
console commands (scheduler)" and then "imageautoresize:batchresize".

This command will process all images found in the directories you are monitoring
but you may optionally override this with two options:

- ``--defaultDirectories``: a comma-separated list of directories to process
  instead of the one(s) you are monitoring.
- ``--excludeDirectories``: a comma-separated list of directories to exclude
  from processing.

.. important::

   As the scheduler task will process each directory found in your rule sets,
   make sure to exclude directories you normally do not show to your editors
   (e.g., :file:`1:/templates`) which may contain large image assets that should
   never be processed by this extension.
