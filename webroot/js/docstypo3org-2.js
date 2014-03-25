/*
 * mb, 2012-12-26, 2013-08-18, 2014-03-25
 * docstypo3org-2.js
 * utf-8, äöü
 */

$(document).ready(function () {
    var naviwest = $('#naviwest');
	var flyOutTocHtml = $('#flyOutToc').html();
	if (flyOutTocHtml === null) {
		flyOutTocHtml = '<p>Navigate within this page:</p>';
	}
	$('#naviwestpayload')
		.append(flyOutTocHtml)
		.addClass('flyOutToc');
	naviwest.css('display', 'block');
	$('.doc #nav-aside a.cur').hover(
		function() { naviwest.css('background-color', '#ff8700'); },
		function() { naviwest.css('background-color', '#ffffff'); }
	);
	$('.hnav-related-2').prepend(''
		+	'<div id="hnav-versions">'
		+	'	<div id="vchoice-trigger">'
		+	'		Versions'
		+	'		<div id="vchoice-choices">'
		+	'			<img id="ajax-preloader-img" src="http://docs.typo3.org/t3extras/i/ajax-preloader.gif" alt="loading ..." /'
		+	'		</div>'
		+	'	</div>'
		+	'</div>'
	);
	$('#vchoice-trigger').mouseenter(
		function () {
			$('#vchoice-choices')
			.show()
			.load(
				'http://docs.typo3.org/php/versionchoices.php?url=' + encodeURI(document.URL),
				false,
				function () {
					$('#vchoice-choices td')
						.click(function() {window.location.href = $(this).find("a").attr("href"); })
					;
					// ???
					// $('#vchoice-choices td')
					// 	.attr("title", $(this).find("a").attr("href"))
					// ;
					$('#vchoice-trigger').unbind('mouseenter');
					$('#vchoice-trigger').mouseenter(
						function() {
							$('#vchoice-choices').show();
						}
					);
					$('#vchoice-choices').mouseleave(
						function() {
							$(this).hide();
						}
					);
				}
			);
		}
	);
	if (1) {
		// add PDF link
		setTimeout(function () {
		$.get(
			'http://docs.typo3.org/php/pdfchoices.php?url=' + encodeURI(document.URL),
			function(data) {
				if (data.length) {
					$('.hnav-related ul').html(data + $('.hnav-related ul').html());
				}
			}
		);
		}, 750);
	}
})
