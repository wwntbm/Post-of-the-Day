/**
 * @author Morgan Davison
 */

jQuery(document).ready(function($) {
	/**
	 * Error checking on the settings form in Admin
	 */
	$('#potd_form').submit(function(){
		// Remove any existing error messages
		$('.error').remove();
		
		if ( isNaN(parseInt($('#potd_amount').val())) ) {
			$('#potd_form').before('<div class="error"><p>Please enter a number for the interval amount.</p></div>');
			return false;
		}
	});
});