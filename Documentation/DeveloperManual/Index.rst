.. include:: ../Includes.rst.txt
.. _developer-manual:

Developer Manual
================

This chapter describes some internals of the Resize Images Automatically
extension to let you extend it easily.


PSR-14 Events
-------------

The concept of PSR-14 events allows for easy implementation of the
`Observer pattern <https://en.wikipedia.org/wiki/Observer_pattern>`_ in
software, something similar to *signal and slots* or *hooks* in former versions
of TYPO3. Please read chapter
`Event dispatcher (PSR-14 events) <https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Events/EventDispatcher/Index.html>`__
from TYPO3 Core documentation.

**Available events:**

ImageResizedEvent
   Event raised after an image has been resized.

ProcessDefaultConfigurationEvent
   Event raised whenever the default configuration file
   :file:`config/image_autoresize.config.php` does not exist, thus loading the
   default configuration for this extension. This event is typically useful if
   you want to provide other (better) default values for the configuration.

ProcessConfigurationEvent
   Event raised after the configuration has been processed, allowing to alter
   it based on your own business logic.
