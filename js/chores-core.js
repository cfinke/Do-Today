jQuery( function ( $ ) {
	$( 'form.delete, form.done' ).on( 'submit', function () {
		$( this ).css( 'opacity', '0.25' );
	} );
} );