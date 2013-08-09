.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


What does it do?
----------------

This extension automatically resizes images to a given maximum height/width right after they have been uploaded to the TYPO3 website. The aspect ratio is of course kept.

The idea behind this extension is that TYPO3 should make both administrators and editors happy. Administrators want the website's footprint on server as small as possible to be able to handle backups efficiently and want the web server to deliver the pages as quick as possible. On the other hand, editors should be able to do their job and not bother with technical considerations such as the size of a picture on disk or that uploading their wonderful sunset taken during holidays with their 12 MP camera will slow down the time rendering of their great photo gallery where pictures are shown with a maximum definition of 800 × 600 pixels. Moreover, editors are either not aware of this or are simply unable to “prepare” their pictures as they are using a foreign computer (in a cyber café) or on the road with their laptop, neither of them running their beloved image editing software.

General configuration settings let you choose which directories should be somehow “monitored” for uploaded pictures and define the file types that should be handled (e.g., "jpg" and "tif" but not "png" nor "gif") and a file size threshold (e.g., "skip any picture smaller than 400 KB"). After all, if an editor managed to create a picture of many mega-pixels that weights only a few KB, why should we bother?
