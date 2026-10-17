/* Submits the signup form without a page reload. Without JavaScript the form still posts to the REST route. */
document.addEventListener( 'submit', async ( event ) => {
	const form = event.target;
	if ( ! ( form instanceof HTMLFormElement ) || ! form.hasAttribute( 'data-postbag-signup' ) ) {
		return;
	}
	event.preventDefault();
	const message = form.querySelector( '.postbag-signup__message' );
	const button = form.querySelector( 'button[type="submit"]' );
	const label = button.textContent;
	button.disabled = true;
	button.textContent = '…';
	message.textContent = '';
	message.classList.remove( 'is-error' );
	try {
		const response = await fetch( form.action, { method: 'POST', body: new FormData( form ), headers: { Accept: 'application/json' } } );
		const data = await response.json().catch( () => ( {} ) );
		message.textContent = data.message || 'Something went wrong. Please try again later.';
		if ( response.ok ) {
			form.querySelector( 'input[type="email"]' ).value = '';
		} else {
			message.classList.add( 'is-error' );
		}
	} catch {
		message.textContent = 'Could not connect. Please check your connection and try again.';
		message.classList.add( 'is-error' );
	} finally {
		button.disabled = false;
		button.textContent = label;
	}
} );
