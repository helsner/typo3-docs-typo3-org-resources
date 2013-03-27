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
 * distribute the values of a given array into multiple columns
 * 
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @author Christian Zenker <christian.zenker@599media.de>
 */
class Tx_T3orgTemplate_ViewHelpers_ColumnViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * distribute the values of a given array into multiple columns
	 *
	 * @param array $items the items to split accross the columns
	 * @param string $as the name of the array with the items for this column
	 * @param integer $columns the number of columns
	 * @param string $iteration the variable name to store the iteration data
	 * @return string the rendered content
	 * @author Christian Zenker <christian.zenker@599media.de>
	 */
	public function render($items, $as, $columns = 2, $iteration = NULL) {
		
		$iterationData = array(
			'total' => $columns
		);
		
		/* @TODO: could be distributed a little nicer, but works ok for our 2-col layout
		 * 
		 * distributing 7 items in a 3-col layout it would be better to distribute them
		 * "3-2-2" instead of "3-3-1".
		 */
		$itemsPerColumn = ceil(count($items) / $columns);

		if(is_object($items) && method_exists($items, 'toArray')) {
			$items = $items->toArray();
		}
		
		$output = '';
		
		for($i = 0; $i < $columns; $i++) {
			if($iteration !== NULL) {
				$iterationData['index'] = $i;
				$iterationData['cycle'] = $i + 1;
				$iterationData['isFirst'] = $i === 0;
				$iterationData['isLast'] = $i === $columns - 1;
				$iterationData['isEven'] = $i % 2 === 0;
				$iterationData['isOdd'] = !$iterationData['isEven'];
				
				$this->templateVariableContainer->add($iteration, $iterationData);
			}
			
			$this->templateVariableContainer->add($as, array_slice($items, $i * $itemsPerColumn, $itemsPerColumn));
			
			$output .= $this->renderChildren();
			
			$this->templateVariableContainer->remove($as);
			
			if ($iteration !== NULL) {
				$this->templateVariableContainer->remove($iteration);
			}
			
		}
		
		return $output;
	}
	
}
?>