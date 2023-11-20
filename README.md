# Resize images automatically

[![Latest Stable Version](https://poser.pugx.org/causal/image_autoresize/v/stable)](https://extensions.typo3.org/extension/image_autoresize/)
![GitHub license](https://img.shields.io/github/license/xperseguers/t3ext-image_autoresize.svg?style=flat-square&label=License)
[![Crowdin](https://badges.crowdin.net/typo3-extension-imageautoresiz/localized.svg)](https://crowdin.com/project/typo3-extension-imageautoresiz)
[![Total Downloads](https://poser.pugx.org/causal/image_autoresize/d/total)](https://packagist.org/packages/causal/image_autoresize)

This extension automatically resizes images to a given maximum height/width right after they have been uploaded to the
TYPO3 website. The aspect ratio is of course kept.

The idea behind this extension is that TYPO3 should make both administrators and editors happy. Administrators want the
website’s footprint on server as small as possible to be able to handle backups efficiently and want the web server to
deliver the pages as quick as possible. On the other hand, editors should be able to do their job and not bother with
technical considerations such as the size of a picture on disk or that uploading their wonderful sunset taken during
holidays with their 12 MP camera will slow down the time rendering of their great photo gallery where pictures are being
shown with a maximum definition of 800 × 600 pixels. Moreover, editors are either not aware of this or are simply unable
to “prepare” their pictures as they are using a foreign computer (in a cyber café) or on the road with their laptop,
neither of them running their beloved image editing software.

General configuration settings let you choose which directories should be somehow “monitored” for uploaded pictures and
define the file types that should be handled (e.g., “jpg” and “tif” but not “png” nor “gif”) and a file size threshold
(e.g., “skip any picture smaller than 400 KB”). After all, if an editor managed to create a picture of many mega-pixels
that weights only a few KB, why should we bother?


## Screencast

The team from [jweiland.net](https://jweiland.net/) prepared a screencast showing how to install and configure this
extension in your TYPO3 website:
https://jweiland.net/video-anleitungen/typo3/interessante-typo3-extensions/image-autoresize.html.


## Screenshot

The following two figures show how an administrator may easily configure rules to resize uploaded images:

![General Configuration][general-configuration]

![General Options][general-options]

[general-configuration]: https://raw.githubusercontent.com/xperseguers/t3ext-image_autoresize/master/Documentation/Images/general-configuration.png "General Configuration"

[general-options]: https://github.com/xperseguers/t3ext-image_autoresize/raw/master/Documentation/Images/general-options.png "General Options"


## Full Documentation

Please head to https://docs.typo3.org/p/causal/image_autoresize/main/en-us/ for the complete extension manual.


## Contribution

Please refer to https://docs.typo3.org/p/causal/image_autoresize/main/en-us/Links.html for instructions.
