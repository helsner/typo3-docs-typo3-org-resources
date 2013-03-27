jQuery(document).ready(function() {

	jQuery('#ter-filter-toggle').click(function() {
		jQuery('#tx-solr-faceting').slideToggle(300);
		jQuery('#ter-filter-toggle').toggleClass('ter-toggle-showLess');
	});
	
	jQuery('#ter-ext-list-search-sorting').change(function() {
		window.location.href = jQuery('#ter-ext-list-search-sorting').val();
	})

});