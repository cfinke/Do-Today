jQuery( function ( $ ) {
	$( 'form.delete, form.done' ).on( 'submit', function () {
		$( this ).css( 'opacity', '0.25' );
	} );
	
	$( '.chore.deletable' ).on( 'swipeleft', function ( e ) {
		$( this ).find( 'form.delete' ).show();
	} );

	$( '.chore.deletable' ).on( 'swiperight', function ( e ) {
		$( this ).find( 'form.delete' ).hide();
	} );
} );