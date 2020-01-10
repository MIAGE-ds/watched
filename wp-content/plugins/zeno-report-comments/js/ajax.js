
/*
 * JavaScript for Zeno Report Comments.
 */


jQuery(document).ready(function($) {
	jQuery( 'span.zeno-comments-report-link span a' ).click( function( a_element ) {

		var comment_id = jQuery( this ).attr('data-zeno-comment-id');

		jQuery.post(
			ZenoCommentsAjax.ajaxurl, {
				comment_id : comment_id,
				sc_nonce   : ZenoCommentsAjax.nonce,
				action     : 'zeno_report_comments_flag_comment',
				xhrFields  : {
					withCredentials: true
				}
			},
			function( response ) {
				var span_id = 'zeno-comments-result-' + comment_id;
				jQuery( 'span#' + span_id ).html( response );
			}
		);

		return false;
	});
});

jQuery( document ).ready( function() {
	jQuery( '.hide-if-js' ).hide();
	jQuery( '.hide-if-no-js' ).show();
});
