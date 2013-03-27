/**
* Condense 0.1 - Condense and expand text heavy elements
*
* (c) 2008 Joseph Sillitoe
* Dual licensed under the MIT License (MIT-LICENSE) and GPL License,version 2 (GPL-LICENSE). 
*/
 
/*
* jQuery plugin
*
* usage:
*  
*   $(document).ready(function(){     
*     $('#example1').condense();
*   });
*
* Options:
*  condensedLength: Target length of condensed element. Default: 200  
*  minTrail: Minimun length of the trailing text. Default: 20
*  delim: Delimiter used for finding the break point. Default: " " - {space}
*  moreText: Text used for the more control. Default: [more]  
*  lessText: Text used for the less control. Default: [less]  
*  ellipsis: Text added to condensed element. Default:  ( ... )  
*  moreSpeed: Animation Speed for expanding. Default: "normal"  
*  lessSpeed: Animation Speed for condensing. Default: "normal"
*  easing: Easing algorith. Default: "linear"
*  expandedWidth: Width of the expanded text (optional)
*/

(function($) {

  // plugin definition
  $.fn.condense = function(options) {
    
    $.metadata ? debug('metadata plugin detected') : debug('metadata plugin not present');//detect the metadata plugin?

    var opts = $.extend({}, $.fn.condense.defaults, options); // build main options before element iteration

    // iterate each matched element
    return this.each(function() {
	    $this = $(this);

      // support metadata plugin (v2.0)
	    var o = $.metadata ? $.extend({}, opts, $this.metadata()) : opts; // build element specific options
     
      debug('Condensing ['+$this.text().length+']: '+$this.text());
      
      var clone = cloneCondensed($this,o);

      if (clone){ 
        // id attribute switch.  make sure that the visible elem keeps the original id (if set).
        $this.attr('id') ? $this.attr('id','condensed_'+$this.attr('id')) : false;

        var controlMore = " <span class='condense_control condense_control_more' style='cursor:pointer;'>"+o.moreText+"</span>";
        var controlLess = " <span class='condense_control condense_control_less' style='cursor:pointer;'>"+o.lessText+"</span>";
        if ($.trim($this.text()).length > opts.condensedLength + opts.minTrail){
        	clone.append(o.ellipsis + controlMore);
        }
        $this.after(clone).hide().append(controlLess);

        $('.condense_control_more',clone).click(function(){
          debug('moreControl clicked.');
          triggerExpand($(this),o)
        });

        $('.condense_control_less',$this).click(function(){
          debug('lessControl clicked.');
          triggerCondense($(this),o)
        });
      }

	  });
  };

  function cloneCondensed(elem, opts){
    // Try to clone and condense the element.  if not possible because of the length/minTrail options, return false.
    // also, dont count tag declarations as part of the text length.
    // check the length of the text first, return false if too short.
   	if ($.trim(elem.text()).length <= opts.condensedLength + opts.minTrail){
   		// Fabien: return clone in any case
   		return elem.clone();
    } 

    var fullbody = $.trim(elem.html());
    // Fabien: added for TYPO3 needs -> remove formatting
    fullbody = strip_tags(fullbody);
    var fulltext = $.trim(elem.text());
    var delim = opts.delim; 
    var clone = elem.clone();
    var delta = 0;

    do {
      // find the location of the next potential break-point.
      var loc = findDelimiterLocation(fullbody, opts.delim, (opts.condensedLength + delta));
      //set the html of the clone to the substring html of the original
      clone.html($.trim(fullbody.substring(0,(loc+1))));
      var cloneTextLength = clone.text().length;
      var cloneHtmlLength = clone.html().length;
      delta = clone.html().length - cloneTextLength; 
      debug ("condensing... [html-length:"+cloneHtmlLength+" text-length:"+cloneTextLength+" delta: "+delta+" break-point: "+loc+"]");
    //is the length of the clone text long enough?
    }while(clone.text().length < opts.condensedLength )

	// Fabien: return clone in any case => comment that
    //  after skipping ahead to the delimiter, do we still have enough trailing text?
    //if ((fulltext.length - cloneTextLength) < opts.minTrail){
    //  debug('not enough trailing text: skipping.');
    //  return false;
    //}

    debug('clone condensed. [text-length:'+cloneTextLength+']');
    return clone;
  }


  function findDelimiterLocation(html, delim, startpos){
    // find the location inside the html of the delimiter, starting at the specified length.
    var foundDelim = false;
    var loc = startpos;    
    do {
      var loc = html.indexOf(delim, loc);
      if (loc < 0){
        debug ("No delimiter found.");
        return html.length;
      } // if there is no delimiter found, just return the length of the entire html string.
      foundDelim = true;
      while (isInsideTag(html, loc)) {
        // if we are inside a tag, this delim doesn't count.  keep looking...      
        loc++;
        foundDelim = false;
      }
    }while(!foundDelim)
    debug ("Delimiter found in html at: "+loc);
    return loc;
  }


  function isInsideTag(html, loc){
    return (html.indexOf('>',loc) < html.indexOf('<',loc));
  }


  function triggerCondense(control, opts){
    debug('Condense Trigger: '+control.html());  
    var orig = control.parent(); // The original element will be the control's immediate parent.
    var condensed = orig.next(); // The condensed element will be the original immediate next sibling.    
    condensed.show();    
    var con_w  = condensed.width();
    var con_h = condensed.height();
    condensed.hide(); //briefly flashed the condensed element so we can get the target width/height
    var orig_w  = orig.width();
    var orig_h = orig.height();
    orig.animate({height:con_h, width:con_w, opacity: 1}, opts.lessSpeed, opts.easing,
      function(){
        orig.height(orig_h).width(orig_w).hide();
        condensed.show(); 
      });
  }


  function triggerExpand(control, opts){
    debug('Expand Trigger: '+control.html());    
    var condensed = control.parent(); // The condensed element will be the control's immediate parent.
    var orig = condensed.prev(); // The original element will be the condensed immediate previous sibling.
    if (opts.expandedWidth) {
      orig.width(opts.expandedWidth);
    }
    orig.show();
    var orig_w  = orig.width();
    var orig_h = orig.height();
    orig.width(condensed.width()+"px").height(condensed.height()+"px"); 
    condensed.hide();
    orig.animate({height:orig_h, width:orig_w, opacity: 1}, opts.moreSpeed, opts.easing);
    if(condensed.attr('id')){
      var idAttr = condensed.attr('id');
      condensed.attr('id','condensed_'+idAttr);
      orig.attr('id',idAttr);
    } 
  }


  /**
   * private function for debugging
   */
  function debug($obj) {
  	if (window.console && window.console.log) {
	  		//window.console.log($obj);
  		}
  	};


  // plugin defaults
  $.fn.condense.defaults = {
    condensedLength: 200,  
    minTrail: 20,
    delim: " ",
    moreText: "[more]",  
    lessText: "[less]",  
    ellipsis: " ( ... )",  
    moreSpeed: "normal",  
    lessSpeed: "normal",
    easing: "linear"
  };

})(jQuery);


	

function strip_tags (input, allowed) {
    // Strips HTML and PHP tags from a string  
    // 
    // version: 1109.2015
    // discuss at: http://phpjs.org/functions/strip_tags    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Luke Godfrey
    // +      input by: Pul
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Onno Marsman    // +      input by: Alex
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: Marc Palau
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: Brett Zamir (http://brett-zamir.me)    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Eric Nagel
    // +      input by: Bobby Drake
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Tomasz Wesolowski    // +      input by: Evertjan Garretsen
    // +    revised by: Rafał Kukawski (http://blog.kukawski.pl/)
    // *     example 1: strip_tags('<p>Kevin</p> <b>van</b> <i>Zonneveld</i>', '<i><b>');
    // *     returns 1: 'Kevin <b>van</b> <i>Zonneveld</i>'
    // *     example 2: strip_tags('<p>Kevin <img src="someimage.png" onmouseover="someFunction()">van <i>Zonneveld</i></p>', '<p>');    // *     returns 2: '<p>Kevin van Zonneveld</p>'
    // *     example 3: strip_tags("<a href='http://kevin.vanzonneveld.net'>Kevin van Zonneveld</a>", "<a>");
    // *     returns 3: '<a href='http://kevin.vanzonneveld.net'>Kevin van Zonneveld</a>'
    // *     example 4: strip_tags('1 < 5 5 > 1');
    // *     returns 4: '1 < 5 5 > 1'    // *     example 5: strip_tags('1 <br/> 1');
    // *     returns 5: '1  1'
    // *     example 6: strip_tags('1 <br/> 1', '<br>');
    // *     returns 6: '1  1'
    // *     example 7: strip_tags('1 <br/> 1', '<br><br/>');    // *     returns 7: '1 <br/> 1'
    allowed = (((allowed || "") + "").toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join(''); // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
    var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi,
        commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
    return input.replace(commentsAndPhpTags, '').replace(tags, function ($0, $1) {        return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
    });
}
