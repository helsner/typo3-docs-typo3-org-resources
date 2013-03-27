<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Extension of Fluids ViewHelper to be able to find
 * whether a file really exists or not
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Tx_T3orgTemplate_ViewHelpers_FileExistsViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {


	/**
	 * Initialize arguments
	 * @return void
	 */
	public function initializeArguments() {
		$this->registerArgument('file', 'mixed', 'Filename(s) to check', TRUE);
	}

	/**
	 * @param $file
	 * @return int
	 */
	public function render() {
		$file = $this->arguments['file'];

		$fileExists = FALSE;
		if ($file && substr($file, -1) != '/') {
			if (file_exists(t3lib_div::getFileAbsFileName($file)) && filesize(t3lib_div::getFileAbsFileName($file)) > 0) {
				$fileExists=TRUE;
			}
		}
		return $fileExists ? $this->renderChildren() : '';
	}
}
?>