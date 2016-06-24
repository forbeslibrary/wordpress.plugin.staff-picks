// var isbn = prompt("Fetch Open Library data by ISBN?", "");
jQuery( document ).ready( function() {
  jQuery("#coverImageDialog").dialog( {
    'dialogClass'    : 'wp-dialog',
    'modal'          : true,
    'autoOpen'       : false,
    'closeOnEscape'  : true,
    'title'          : 'Suggested Cover Image',
    'width'          : 300,
    'open'           : function(event, ui) {
      jQuery('.ui-dialog').css('z-index',1001);
    }
  });
  jQuery("#isbnDialog").dialog( {
    'dialogClass'   : 'wp-dialog',
    'modal'         : true,
    'autoOpen'      : true,
    'closeOnEscape' : true,
    'title'         : 'Fetch metadata by ISBN?',
    'open'          : function(event, ui) {
      jQuery('.ui-dialog').css('z-index',1001);
      jQuery('.ui-dialog').on(
        'keypress',
        function(e) {
          if (e.which === jQuery.ui.keyCode.ENTER) {
             jQuery('#openlibrary-data-fetch-button').click();
           }
         }
       );
      },
      'buttons': [
        {
          'text': 'No thanks',
          'click': function() {
            jQuery(this).dialog('close');
          }
        },
        {
          'text': 'Fetch',
          'id': 'openlibrary-data-fetch-button',
          'style': 'border: 4px outset',
          'click': function() {
            var isbn = jQuery('#ISBN').val();
            jQuery.getJSON( "https://openlibrary.org/api/books?callback=?&jscmd=data&bibkeys=ISBN:" + isbn, function( data ) {
              var key = 'ISBN:' + isbn;
              if (!(key in data)) {
                alert('Could not find metadata for this ISBN');
                return;
              }
              var metadata = data['ISBN:' + isbn];

              var title = metadata.title;
              if (metadata.subtitle) {
                title = title + ': ' + metadata.subtitle;
              }

              var author = metadata.by_statement.replace(/by |[\.$]/g,'');

              var catalog_url = jQuery('#catalog_url_template').val().replace('%s', isbn);

              jQuery('#title').val(titleCaps(title));
              jQuery('.author-input').val(author);
              jQuery('.catalog_url-input').val(catalog_url);

              if (metadata.cover.large) {
                jQuery('#coverImageSuggestion').attr('src', metadata.cover.large);
                jQuery('#coverImageDialog').dialog('open');
              }
          });
          jQuery(this).dialog('close');
        }
      }
    ]
  }).css('z-index','1001');
});

/*
 * Title Caps
 *
 * Ported to JavaScript By John Resig - http://ejohn.org/ - 21 May 2008
 * Original by John Gruber - http://daringfireball.net/ - 10 May 2008
 * URL: http://ejohn.org/files/titleCaps.js
 * License: http://www.opensource.org/licenses/mit-license.php
 */

var small = "(a|an|and|as|at|but|by|en|for|if|in|of|on|or|the|to|v[.]?|via|vs[.]?)";
var punct = "([!\"#$%&'()*+,./:;<=>?@[\\\\\\]^_`{|}~-]*)";

titleCaps = function(title){
	var parts = [], split = /[:.;?!] |(?: |^)["Ò]/g, index = 0;

	while (true) {
		var m = split.exec(title);

		parts.push( title.substring(index, m ? m.index : title.length)
			.replace(/\b([A-Za-z][a-z.'Õ]*)\b/g, function(all){
				return /[A-Za-z]\.[A-Za-z]/.test(all) ? all : upper(all);
			})
			.replace(RegExp("\\b" + small + "\\b", "ig"), lower)
			.replace(RegExp("^" + punct + small + "\\b", "ig"), function(all, punct, word){
				return punct + upper(word);
			})
			.replace(RegExp("\\b" + small + punct + "$", "ig"), upper));

		index = split.lastIndex;

		if ( m ) parts.push( m[0] );
		else break;
	}

	return parts.join("").replace(/ V(s?)\. /ig, " v$1. ")
		.replace(/(['Õ])S\b/ig, "$1s")
		.replace(/\b(AT&T|Q&A)\b/ig, function(all){
			return all.toUpperCase();
		});
};

function lower(word){
	return word.toLowerCase();
}

function upper(word){
  return word.substr(0,1).toUpperCase() + word.substr(1);
}
