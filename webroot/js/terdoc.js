//	
//	/ Add JS Loading library
//	unction LoadScript(url) {
//		document.write( '<scr' + 'ipt type="text/javascript" src="' + url + '"><\/scr' + 'ipt>' ) ;
//	
//	
//	/ Load jQuery if not the case
//	ar jQueryIsLoaded = typeof jQuery != "undefined";
//	f (!jQueryIsLoaded) {
//		LoadScript("https://code.jquery.com/jquery.js");
//		LoadScript("https://docs.typo3.org/js/jquery.condense.js");
//	
//	
//	/ Add listener
//	indow.onload = function () {
//		
//		// Add collapse / expand event
//		$('dl.docutils dd:nth-child(n-4)').click(function(e) {
//			var container = $(this).parents('dl');
//			$('dd:nth-child(n+6)', container).toggle();
//			
//			var row = $(container).parent('div.table-row');
//			$(row).toggleClass('table-row-expand');
//		});
//		
//		// Condense mode for collapsing / expanding description box
//		$('dl.docutils dd:nth-child(6)').condense({
//			condensedLength: 40,
//			ellipsis: "&nbsp;...",
//			moreText: "",
//			lessText: "",
//			moreSpeed: "normal",  
//			lessSpeed: "normal",
//			easing: "swing"
//		});
//	;