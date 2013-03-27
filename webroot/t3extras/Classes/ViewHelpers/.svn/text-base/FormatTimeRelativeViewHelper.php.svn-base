<?php

/*
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
 * format a relative time string
 * 
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @author Christian Zenker <christian.zenker@599media.de>
 */
class Tx_T3orgTemplate_ViewHelpers_FormatTimeRelativeViewHelper  extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * @param mixed $time time
	 * @return string a rendered string, like "5 minutes ago"
	 * @author Christian Zenker <christian.zenker@599media.de>
	 */
	public function render($timestamp) {
		if($timestamp instanceof DateTimeObject) {
			$timestamp = $timestamp->format('U');
		} else {
			if(is_object($timestamp)) {
				$timestamp = (string) $timestamp;
			}
			
			if(is_numeric($timestamp)) {
				$timestamp = intval($timestamp);
			} elseif(is_string($timestamp)) {
				$timestamp = strtotime($timestamp);
				if($timestamp === false) {
					return '';
	//				throw new InvalidArgumentException('given string could not be parsed as time string in '.get_class($this));
				}
			} else {
				return '';
	//			throw new InvalidArgumentException('Can\'t convert '.gettype($timestamp).' to time.');
			}
		}
		
		
		$diff = time() - $timestamp;
		
		if($diff < 5) {
			return 'a few seconds ago';
		} elseif($diff < 45) {
			return sprintf('%d seconds ago', $diff);
		} elseif($diff < 91) {
			return 'about one minute ago';
		} elseif($diff < 2700){
			// if: less than 45 minutes
			return sprintf('%d minutes ago', round($diff / 60));
		} elseif($diff < 5400) {
			// if: less than 90 minutes
			return 'one hour ago';
		} elseif($diff < 43200) {
			// if: less than 12 hours
			return sprintf('%d hours ago', round($diff / 3600));
		} elseif($diff < 129600) {
			//if: Less than 1 day
			return '1 day ago';
		} elseif ($diff < 604800) {
			// if: less than 7 days
			return sprintf('%d days ago', round($diff / 86400));
		} elseif($diff < 1209600){
			return 'last week';
		} else {
			return 'a long time ago';
		}
	}
	
}
?>