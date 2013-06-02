.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt
.. include:: Images.txt


Workflow
^^^^^^^^

Simply upload your unmodified pictures either from the File > Filelist module (fileadmin) or from the edit form of a content element (text with image, image, ...) and enjoy the automatic resizing and orientation in portrait of your pictures (here with an original picture in 4080 × 2720, 3.6 MB with EXIF orientation set to "portrait"):

|example_autoresize|

As you see, the constraints of 1024 × 768 pixels have been taken into account even with the correct orientation of the picture. If the reorientation did not have been taken into account, the picture would have been resized to 682 × 1024 instead (maximum ratio using the other dimension).
Here is another example, from a content element "text w/image" where a BMP (``screenshot.bmp``, 3.2 MB, 1184 × 884) has been added to the list of associated images:

|example_converted|

The uploaded file screenshot.bmp has automatically been both resized and converted to a new file ``screenshot.jpg`` (according to the image type conversion mapping option described in chapter :ref:`Administration`).

Details of this image show that its footprint is now quite small (151 KB):

|footprint|
