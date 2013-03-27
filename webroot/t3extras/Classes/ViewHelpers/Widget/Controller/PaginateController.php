<?php 

/**
 * overriding the PaginateController is necessary to override the template 
 * and implement the custom logic
 * This ViewHelper makes it possible to treat other objects that are traversable and not just queries.
 * 
 * @author Christian Zenker <christian.zenker@599media.de>
 */
class Tx_T3orgTemplate_ViewHelpers_Widget_Controller_PaginateController extends Tx_Fluid_ViewHelpers_Widget_Controller_PaginateController {

	/**
	 * @var array
	 */
	protected $configuration = array('itemsPerPage' => 10, 'insertAbove' => FALSE, 'insertBelow' => TRUE, 'showArrows' => TRUE, 'maxPagesToShow' => 10);
	
	/**
	 * add logic to handle other traversable objects that are no query result
	 * 
	 * @param integer $currentPage
	 * @return void
	 */
	public function indexAction($currentPage = 1) {
			// set current page
		$this->currentPage = (integer)$currentPage;
		if ($this->currentPage < 1) {
			$this->currentPage = 1;
		} elseif ($this->currentPage > $this->numberOfPages) {
			$this->currentPage = $this->numberOfPages;
		}

			// modify query
		$itemsPerPage = (integer)$this->configuration['itemsPerPage'];
		
// modifications start here
		if($this->objects instanceof Tx_Extbase_Persistence_QueryResultInterface) {
			//if: queryResult -> default widget behaviour
			$query = $this->objects->getQuery();
			$query->setLimit($itemsPerPage);
			if ($this->currentPage > 1) {
				$query->setOffset((integer)($itemsPerPage * ($this->currentPage - 1)));
			}
			$modifiedObjects = $query->execute();
		} elseif(is_array($this->objects) || (is_object($this->objects) && ($this->objects instanceof Traversable || method_exists($this->objects, 'toArray')))) {
			//if: we got something arrayish
			if(is_object($this->objects) && method_exists($this->objects, 'toArray')) {
				// if: object implements "toArray" method
				$this->objects = $this->objects->toArray();
			} elseif(is_object($this->objects)) {
				$this->objects = iterator_to_array($this->objects);
			}
			
			$modifiedObjects = array_slice($this->objects, ($this->currentPage - 1) * $itemsPerPage, $itemsPerPage);
			
		} else {
			//else: no idea how to handle this...
			throw new InvalidArgumentException(get_class($this).' only handles arrayish objects.');
		}
// modifiactions end here
		$this->view->assign('contentArguments', array(
			$this->widgetConfiguration['as'] => $modifiedObjects
		));
		$this->view->assign('configuration', $this->configuration);
		$this->view->assign('pagination', $this->buildPagination());
	}
	
	/**
	 * Returns an array with the keys "pages", "current", "numberOfPages", "nextPage" & "previousPage"
	 *
	 * @return array
	 */
	protected function buildPagination() {
		$maxPagesToShow = $this->configuration['maxPagesToShow'] ? $this->configuration['maxPagesToShow'] : 10;
		
		/*
		 * determine which number range should be shown
		 */
		
		/**
		 * @var integer the first page to show in selection
		 */
		$sliceMin = null;
		/**
		 * @var integer the last page to show in the selection
		 */
		$sliceMax = null;
		
		if($this->numberOfPages <= $maxPagesToShow) {
			// if: less than 10 pages -> just show them all
			$sliceMin = 1;
			$sliceMax = $this->numberOfPages;
		} elseif($this->currentPage <= ceil($maxPagesToShow / 2)) {
			//if: current page is in the first 5 pages -> show from the beginning and strip at the end
			$sliceMin = 1;
			$sliceMax = $maxPagesToShow;
		} elseif($this->currentPage >= floor($this->numberOfPages - $maxPagesToShow / 2)) {
			//if: current page is in the last 5 pages -> show from the end and strip at the beginning
			$sliceMin = $this->numberOfPages - $maxPagesToShow;
			$sliceMax = $this->numberOfPages;
		} else {
			//if: current page is somewhere in the middle -> strip at beginning and end
			$sliceMin = $this->currentPage - floor(($maxPagesToShow - 1) / 2);
			$sliceMax = $this->currentPage + ceil(($maxPagesToShow - 1) / 2);
		}
		
		$pages = array();
		for ($i = $sliceMin; $i <= $sliceMax; $i++) {
			$pages[] = array('number' => $i, 'isCurrent' => ($i == $this->currentPage));
		}
		
		
		$pagination = array(
			'pages' => $pages,
			'current' => $this->currentPage,
			'numberOfPages' => $this->numberOfPages,
		);
		if ($this->currentPage < $this->numberOfPages) {
			$pagination['nextPage'] = $this->currentPage + 1;
		}
		if ($this->currentPage > 1) {
			$pagination['previousPage'] = $this->currentPage - 1;
		}
		
		if($this->configuration['showArrows']) {
			$pagination['hellipBefore'] = $sliceMin != 1;
			$pagination['hellipAfter'] = $sliceMax != $this->numberOfPages;
			$pagination['showFirst'] = $this->currentPage != 1;
			$pagination['showLast'] = $this->currentPage != $this->numberOfPages;
		} else {
			$pagination['hellipBefore'] = $sliceMin > 2;
			$pagination['hellipAfter'] = $sliceMax < $this->numberOfPages - 1;
			$pagination['showFirst'] = $sliceMin > 1;
			$pagination['showLast'] = $sliceMax < $this->numberOfPages;
		}
		
		return $pagination;
	}
}

?>