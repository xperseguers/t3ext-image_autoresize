.. include:: ../Includes.rst.txt
.. _changelog:

ChangeLog
=========

The following is a very high level overview of the changes in this extension.
For more details,
`read the online log <https://github.com/xperseguers/t3ext-image_autoresize/commits/master>`_.


=======  ======================================================
Version  Changes
=======  ======================================================
2.5.x    * Early compatibility with TYPO3 v14
         * **Beware:** Dropped support for TYPO3 v10 and v11
2.4.x    * Early compatibility with TYPO3 v13
         * Better respect of privacy by respecting whether or not to
           automatically extracting metadata prior to the resizing process
2.3.x    * Configuration stored along with the site definitions
         * Scheduler task rewritten as a Symfony command
2.2.x    * Compatibility with TYPO3 10 LTS - 12 LTS
         * **Beware:** Dropped compatibility with PHP 7.2 and 7.3
2.1.x    Compatibility with TYPO3 8 LTS - 11 LTS
2.0.x    * Compatibility with TYPO3 8 LTS - 10 LTS
         * **Beware:** Dropped compatibility with PHP 7.0 and 7.1
1.9.x    Support for bounding the maximum dimensions in pixels
1.8.x    Compatibility with TYPO3 7 LTS - 8 LTS
1.7.x    Support for 3rd-party metadata extraction services
1.6.x    Compatibility with TYPO3 6.2 - 7.x
1.5.x    Automatic handling of Frontend-based uploads, if `properly done <https://gist.github.com/xperseguers/9076406>`_.
1.4.x    Batch processing of images
1.3.x    * Compatibility with TYPO3 6.0. Support for auto-rotate when using GraphicsMagick.
         * **Beware:** Dropped compatibility with PHP 5.2, dropped compatibility code for TYPO3 < 4.5 LTS
1.2.x    Added compatibility with DAM 1.3. Thanks to Eventex Nord (http://eventex.fr) for sponsoring this bugfix.
1.1.x    Move configuration wizard from Extension Manager to a dedicated Backend module to be compatible with TYPO3 4.6.
1.0.x    Minor update. Extension is now considered stable.
0.5.0    First release to the TER
=======  ======================================================
