<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Xavier Perseguers (typo3@perseguers.ch)
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
 * This class extends t3lib_TCEmain to provide the hook needed
 * to automatically resize huge picture upon upload. It is only needed
 * for TYPO3 4.3 and TYPO3 4.4.
 *
 * @category    XClass
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <typo3@perseguers.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class ux_t3lib_TCEmain extends t3lib_TCEmain {

	/**
	 * Handling files for group/select function
	 *
	 * @param	array		Array of incoming file references. Keys are numeric, values are files (basically, this is the exploded list of incoming files)
	 * @param	array		Configuration array from TCA of the field
	 * @param	string		Current value of the field
	 * @param	array		Array of uploaded files, if any
	 * @param	string		Status ("update" or ?)
	 * @param	string		tablename of record
	 * @param	integer		UID of record
	 * @param	string		Field identifier ([table:uid:field:....more for flexforms?]
	 * @return	array		Modified value array
	 * @see checkValue_group_select()
	 */
	function checkValue_group_select_file($valueArray,$tcaFieldConf,$curValue,$uploadedFileArray,$status,$table,$id,$recFID)	{

		if (!$this->bypassFileHandling)	{	// If filehandling should NOT be bypassed, do processing:

				// If any files are uploaded, add them to value array
			if (is_array($uploadedFileArray) &&
				$uploadedFileArray['name'] &&
				strcmp($uploadedFileArray['tmp_name'],'none'))	{
					$valueArray[]=$uploadedFileArray['tmp_name'];
					$this->alternativeFileName[$uploadedFileArray['tmp_name']] = $uploadedFileArray['name'];
			}

				// Creating fileFunc object.
			if (!$this->fileFunc)	{
				$this->fileFunc = t3lib_div::makeInstance('t3lib_basicFileFunctions');
				$this->include_filefunctions=1;
			}
				// Setting permitted extensions.
			$all_files = Array();
			$all_files['webspace']['allow'] = $tcaFieldConf['allowed'];
			$all_files['webspace']['deny'] = $tcaFieldConf['disallowed'] ? $tcaFieldConf['disallowed'] : '*';
			$all_files['ftpspace'] = $all_files['webspace'];
			$this->fileFunc->init('', $all_files);
		}

			// If there is an upload folder defined:
		if ($tcaFieldConf['uploadfolder'] && $tcaFieldConf['internal_type'] == 'file') {
			if (!$this->bypassFileHandling)	{	// If filehandling should NOT be bypassed, do processing:
					// For logging..
				$propArr = $this->getRecordProperties($table,$id);

					// Get destrination path:
				$dest = $this->destPathFromUploadFolder($tcaFieldConf['uploadfolder']);

					// If we are updating:
				if ($status=='update')	{

						// Traverse the input values and convert to absolute filenames in case the update happens to an autoVersionized record.
						// Background: This is a horrible workaround! The problem is that when a record is auto-versionized the files of the record get copied and therefore get new names which is overridden with the names from the original record in the incoming data meaning both lost files and double-references!
						// The only solution I could come up with (except removing support for managing files when autoversioning) was to convert all relative files to absolute names so they are copied again (and existing files deleted). This should keep references intact but means that some files are copied, then deleted after being copied _again_.
						// Actually, the same problem applies to database references in case auto-versioning would include sub-records since in such a case references are remapped - and they would be overridden due to the same principle then.
						// Illustration of the problem comes here:
						// We have a record 123 with a file logo.gif. We open and edit the files header in a workspace. So a new version is automatically made.
						// The versions uid is 456 and the file is copied to "logo_01.gif". But the form data that we sent was based on uid 123 and hence contains the filename "logo.gif" from the original.
						// The file management code below will do two things: First it will blindly accept "logo.gif" as a file attached to the record (thus creating a double reference) and secondly it will find that "logo_01.gif" was not in the incoming filelist and therefore should be deleted.
						// If we prefix the incoming file "logo.gif" with its absolute path it will be seen as a new file added. Thus it will be copied to "logo_02.gif". "logo_01.gif" will still be deleted but since the files are the same the difference is zero - only more processing and file copying for no reason. But it will work.
					if ($this->autoVersioningUpdate===TRUE)	{
						foreach($valueArray as $key => $theFile)	{
							if ($theFile===basename($theFile))	{	// If it is an already attached file...
								$valueArray[$key] = PATH_site.$tcaFieldConf['uploadfolder'].'/'.$theFile;
							}
						}
					}

						// Finding the CURRENT files listed, either from MM or from the current record.
					$theFileValues=array();
					if ($tcaFieldConf['MM'])	{	// If MM relations for the files also!
						$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
						/* @var $dbAnalysis t3lib_loadDBGroup */
						$dbAnalysis->start('','files',$tcaFieldConf['MM'],$id);
						foreach ($dbAnalysis->itemArray as $item) {
							if ($item['id']) {
								$theFileValues[] = $item['id'];
							}
						}
					} else {
						$theFileValues=t3lib_div::trimExplode(',',$curValue,1);
					}
					$currentFilesForHistory = implode(',', $theFileValues);

						// DELETE files: If existing files were found, traverse those and register files for deletion which has been removed:
					if (count($theFileValues))	{
							// Traverse the input values and for all input values which match an EXISTING value, remove the existing from $theFileValues array (this will result in an array of all the existing files which should be deleted!)
						foreach($valueArray as $key => $theFile)	{
							if ($theFile && !strstr(t3lib_div::fixWindowsFilePath($theFile),'/'))	{
								$theFileValues = t3lib_div::removeArrayEntryByValue($theFileValues,$theFile);
							}
						}

							// This array contains the filenames in the uploadfolder that should be deleted:
						foreach($theFileValues as $key => $theFile)	{
							$theFile = trim($theFile);
							if (@is_file($dest.'/'.$theFile))	{
								$this->removeFilesStore[]=$dest.'/'.$theFile;
							} elseif ($theFile) {
								$this->log($table,$id,5,0,1,"Could not delete file '%s' (does not exist). (%s)",10,array($dest.'/'.$theFile, $recFID),$propArr['event_pid']);
							}
						}
					}
				}

					// Traverse the submitted values:
				foreach($valueArray as $key => $theFile)	{
						// NEW FILES? If the value contains '/' it indicates, that the file is new and should be added to the uploadsdir (whether its absolute or relative does not matter here)
					if (strstr(t3lib_div::fixWindowsFilePath($theFile),'/'))	{
							// Init:
						$maxSize = intval($tcaFieldConf['max_size']);
						$cmd='';
						$theDestFile='';		// Must be cleared. Else a faulty fileref may be inserted if the below code returns an error!

							// Check various things before copying file:
						if (@is_dir($dest) && (@is_file($theFile) || @is_uploaded_file($theFile)))	{		// File and destination must exist

								// Finding size. For safe_mode we have to rely on the size in the upload array if the file is uploaded.
							if (is_uploaded_file($theFile) && $theFile==$uploadedFileArray['tmp_name'])	{
								$fileSize = $uploadedFileArray['size'];
							} else {
								$fileSize = filesize($theFile);
							}

							if (!$maxSize || $fileSize<=($maxSize*1024))	{	// Check file size:
									// Prepare filename:
								$theEndFileName = isset($this->alternativeFileName[$theFile]) ? $this->alternativeFileName[$theFile] : $theFile;
								$fI = t3lib_div::split_fileref($theEndFileName);

									// Check for allowed extension:
								if ($this->fileFunc->checkIfAllowed($fI['fileext'], $dest, $theEndFileName)) {
									$theDestFile = $this->fileFunc->getUniqueName($this->fileFunc->cleanFileName($fI['file']), $dest);

										// If we have a unique destination filename, then write the file:
									if ($theDestFile)	{
										t3lib_div::upload_copy_move($theFile,$theDestFile);

											// Hook for post-processing the upload action
										if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processUpload'])) {
											foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processUpload'] as $classRef) {
												$hookObject = t3lib_div::getUserObj($classRef);

												if (!($hookObject instanceof t3lib_TCEmain_processUploadHook)) {
													throw new UnexpectedValueException('$hookObject must implement interface t3lib_TCEmain_processUploadHook', 1279962349);
												}

												$hookObject->processUpload_postProcessAction($theDestFile, $this);
											}
										}

										$this->copiedFileMap[$theFile] = $theDestFile;
										clearstatcache();
										if (!@is_file($theDestFile))	$this->log($table,$id,5,0,1,"Copying file '%s' failed!: The destination path (%s) may be write protected. Please make it write enabled!. (%s)",16,array($theFile, dirname($theDestFile), $recFID),$propArr['event_pid']);
									} else $this->log($table,$id,5,0,1,"Copying file '%s' failed!: No destination file (%s) possible!. (%s)",11,array($theFile, $theDestFile, $recFID),$propArr['event_pid']);
								} else $this->log($table,$id,5,0,1,"File extension '%s' not allowed. (%s)",12,array($fI['fileext'], $recFID),$propArr['event_pid']);
							} else $this->log($table,$id,5,0,1,"Filesize (%s) of file '%s' exceeds limit (%s). (%s)",13,array(t3lib_div::formatSize($fileSize),$theFile,t3lib_div::formatSize($maxSize*1024),$recFID),$propArr['event_pid']);
						} else $this->log($table,$id,5,0,1,'The destination (%s) or the source file (%s) does not exist. (%s)',14,array($dest, $theFile, $recFID),$propArr['event_pid']);

							// If the destination file was created, we will set the new filename in the value array, otherwise unset the entry in the value array!
						if (@is_file($theDestFile))	{
							$info = t3lib_div::split_fileref($theDestFile);
							$valueArray[$key]=$info['file']; // The value is set to the new filename
						} else {
							unset($valueArray[$key]);	// The value is set to the new filename
						}
					}
				}
			}

				// If MM relations for the files, we will set the relations as MM records and change the valuearray to contain a single entry with a count of the number of files!
			if ($tcaFieldConf['MM'])	{
				$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
				/* @var $dbAnalysis t3lib_loadDBGroup */
				$dbAnalysis->tableArray['files']=array();	// dummy

				foreach ($valueArray as $key => $theFile) {
						// explode files
						$dbAnalysis->itemArray[]['id']=$theFile;
				}
				if ($status=='update')	{
					$dbAnalysis->writeMM($tcaFieldConf['MM'],$id,0);
					$newFiles = implode(',', $dbAnalysis->getValueArray());
					list(,,$recFieldName) = explode(':', $recFID);
					if ($currentFilesForHistory != $newFiles) {
						$this->mmHistoryRecords[$currentTable . ':' . $id]['oldRecord'][$recFieldName] = $currentFilesForHistory;
						$this->mmHistoryRecords[$currentTable . ':' . $id]['newRecord'][$recFieldName] = $newFiles;
					} else {
						$this->mmHistoryRecords[$currentTable . ':' . $id]['oldRecord'][$currentField] = '';
						$this->mmHistoryRecords[$currentTable . ':' . $id]['newRecord'][$currentField] = '';
					}
				} else {
					$this->dbAnalysisStore[] = array($dbAnalysis, $tcaFieldConf['MM'], $id, 0);	// This will be traversed later to execute the actions
				}
				$valueArray = $dbAnalysis->countItems();
			}
			//store path relative to site root (if uploadfolder is not set or internal_type is file_reference)
		} else {
			if (count($valueArray)){
				if (!$this->bypassFileHandling) {	// If filehandling should NOT be bypassed, do processing:
					$propArr = $this->getRecordProperties($table, $id); // For logging..
					foreach($valueArray as &$theFile){

							// if alernative File Path is set for the file, then it was an import
						if ($this->alternativeFilePath[$theFile]){

								// don't import the file if it already exists
							if (@is_file(PATH_site . $this->alternativeFilePath[$theFile])) {
								$theFile = PATH_site . $this->alternativeFilePath[$theFile];

								// import the file
							} elseif (@is_file($theFile)){
								$dest = dirname(PATH_site . $this->alternativeFilePath[$theFile]);
								if (!@is_dir($dest)) {
									t3lib_div::mkdir_deep(PATH_site, dirname($this->alternativeFilePath[$theFile]) . '/');
								}

									// Init:
								$maxSize = intval($tcaFieldConf['max_size']);
								$cmd = '';
								$theDestFile = '';		// Must be cleared. Else a faulty fileref may be inserted if the below code returns an error!
								$fileSize = filesize($theFile);

								if (!$maxSize || $fileSize <= ($maxSize * 1024))	{	// Check file size:
										// Prepare filename:
									$theEndFileName = isset($this->alternativeFileName[$theFile]) ? $this->alternativeFileName[$theFile] : $theFile;
									$fI = t3lib_div::split_fileref($theEndFileName);

										// Check for allowed extension:
									if ($this->fileFunc->checkIfAllowed($fI['fileext'], $dest, $theEndFileName)) {
										$theDestFile = PATH_site . $this->alternativeFilePath[$theFile];

											// Write the file:
										if ($theDestFile)	{
											t3lib_div::upload_copy_move($theFile, $theDestFile);
											$this->copiedFileMap[$theFile] = $theDestFile;
											clearstatcache();
											if (!@is_file($theDestFile)) $this->log($table, $id, 5, 0, 1, "Copying file '%s' failed!: The destination path (%s) may be write protected. Please make it write enabled!. (%s)", 16, array($theFile, dirname($theDestFile), $recFID), $propArr['event_pid']);
										} else $this->log($table, $id, 5, 0, 1, "Copying file '%s' failed!: No destination file (%s) possible!. (%s)", 11, array($theFile, $theDestFile, $recFID), $propArr['event_pid']);
									} else $this->log($table, $id, 5, 0, 1, "File extension '%s' not allowed. (%s)", 12, array($fI['fileext'], $recFID), $propArr['event_pid']);
								} else $this->log($table, $id, 5, 0, 1, "Filesize (%s) of file '%s' exceeds limit (%s). (%s)", 13, array(t3lib_div::formatSize($fileSize), $theFile,t3lib_div::formatSize($maxSize * 1024),$recFID), $propArr['event_pid']);

									// If the destination file was created, we will set the new filename in the value array, otherwise unset the entry in the value array!
								if (@is_file($theDestFile))	{
									$theFile = $theDestFile; // The value is set to the new filename
								} else {
									unset($theFile); // The value is set to the new filename
								}
							}
						}
						$theFile = t3lib_div::fixWindowsFilePath($theFile);
						if (t3lib_div::isFirstPartOfStr($theFile, PATH_site)) {
							$theFile = substr($theFile, strlen(PATH_site));
						}
					}
				}
			}
		}

		return $valueArray;
	}

}

?>