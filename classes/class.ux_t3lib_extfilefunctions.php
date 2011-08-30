<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Xavier Perseguers <xavier@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * This class extends t3lib_extFileFunctions to provide the hook needed
 * to automatically resize huge picture upon upload. It is only needed
 * for TYPO3 4.3 and TYPO3 4.4.
 *
 * @category    XClass
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@typo3.org>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class ux_t3lib_extFileFunctions extends t3lib_extFileFunctions {

	/**
	 * Processing the command array in $this->fileCmdMap
	 *
	 * @return	mixed	false, if the file functions were not initialized
	 *					otherwise returns an array of all the results that are returned
	 *					from each command, separated in each action.
	 */
	function processData() {
		$result = array();
		if (!$this->isInit) {
			return false;
		}

		if (is_array($this->fileCmdMap)) {

				// Check if there were uploads expected, but no one made
			if ($this->fileCmdMap['upload']) {
				$uploads = $this->fileCmdMap['upload'];
				foreach ($uploads as $upload) {
					if (!$_FILES['upload_' . $upload['data']]['name']) {
						unset($this->fileCmdMap['upload'][$upload['data']]);
					}
				}
				if (count($this->fileCmdMap['upload']) == 0) {
					$this->writelog(1,1,108,'No file was uploaded!','');
				}
			}

				// Traverse each set of actions
			foreach ($this->fileCmdMap as $action => $actionData) {

					// Traverse all action data. More than one file might be affected at the same time.
				if (is_array($actionData)) {
					$result[$action] = array();
					foreach ($actionData as $cmdArr) {

							// Clear file stats
						clearstatcache();

							// Branch out based on command:
						switch ($action) {
							case 'delete':
								$result[$action][] = $this->func_delete($cmdArr);
							break;
							case 'copy':
								$result[$action][] = $this->func_copy($cmdArr);
							break;
							case 'move':
								$result[$action][] = $this->func_move($cmdArr);
							break;
							case 'rename':
								$result[$action][] = $this->func_rename($cmdArr);
							break;
							case 'newfolder':
								$result[$action][] = $this->func_newfolder($cmdArr);
							break;
							case 'newfile':
								$result[$action][] = $this->func_newfile($cmdArr);
							break;
							case 'editfile':
								$result[$action][] = $this->func_edit($cmdArr);
							break;
							case 'upload':
								$result[$action][] = $this->func_upload($cmdArr);
							break;
							case 'unzip':
								$result[$action][] = $this->func_unzip($cmdArr);
							break;
						}

							// Hook for post-processing the action
						if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'])) {
							foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'] as $classRef) {
								$hookObject = t3lib_div::getUserObj($classRef);

								if (!($hookObject instanceof t3lib_extFileFunctions_processDataHook)) {
									throw new UnexpectedValueException('$hookObject must implement interface t3lib_extFileFunctions_processDataHook', 1279719168);
								}

								/** @var $hookObject t3lib_extFileFunctions_processDataHook */
								$hookObject->processData_postProcessAction($action, $cmdArr, $result[$action], $this);
							}
						}
					}
				}
			}
		}
		return $result;
	}

}

?>