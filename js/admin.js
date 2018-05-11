jQuery( document ).ready(function( $ ) {
	$('#switch-minor').change(function () {
		if( ! $(this).prop('checked') ) {
			$('#switch-major').prop('checked', false);
		}
	});

	$('#switch-major').change(function () {
		if( $(this).prop('checked') ) {
			$('#switch-minor').prop('checked', true);
		}
	});

});