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
 * get number of events
 * 
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @author Christian Zenker <christian.zenker@599media.de>
 */
class Tx_T3orgTemplate_ViewHelpers_CountAdditionalEventsViewHelper  extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * @param array $settings
	 * @param integer $subtract
	 * @return string a rendered string, like "5 minutes ago"
	 * @author Christian Zenker <christian.zenker@599media.de>
	 */
	public function render($settings, $subtract = 0) {
		$objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		$this->eventRepository = $objectManager->get('Tx_CzSimpleCal_Domain_Repository_EventIndexRepository');
		
		unset($settings['maxEvents']);
		$settings['startDate'] = $settings['startDate'] ? $objectManager->get('Tx_CzSimpleCal_Utility_DateTime', $settings['startDate'])->getTimestamp() : null;
		$settings['endDate'] = $settings['endDate'] ? $objectManager->get('Tx_CzSimpleCal_Utility_DateTime', $settings['endDate'])->getTimestamp() : null;
		
		
		$number = $this->eventRepository->countAllWithSettings($settings) - $subtract;
		$number = max($number, 0);
		return $number;
	}
	
}
?>