/**
 * Editor side of the Newsletter signup block. Written against the wp.* globals so the plugin
 * needs no build step; the front end is rendered by render.php.
 */
( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, TextControl, ToggleControl } = wp.components;
	const { createElement: el } = wp.element;
	const { __ } = wp.i18n;
	const ServerSideRender = wp.serverSideRender;

	registerBlockType( 'postbag/signup', {
		edit( { attributes, setAttributes } ) {
			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Signup form', 'postbag-signup' ) },
						el( TextControl, {
							label: __( 'Title', 'postbag-signup' ),
							value: attributes.title,
							onChange: ( title ) => setAttributes( { title } ),
						} ),
						el( TextControl, {
							label: __( 'Button text', 'postbag-signup' ),
							value: attributes.buttonText,
							onChange: ( buttonText ) => setAttributes( { buttonText } ),
						} ),
						el( ToggleControl, {
							label: __( 'Ask for a name', 'postbag-signup' ),
							checked: attributes.showName,
							onChange: ( showName ) => setAttributes( { showName } ),
						} ),
						el( TextControl, {
							label: __( 'List ID (optional)', 'postbag-signup' ),
							help: __( 'Leave empty to use the default list from Settings → Postbag.', 'postbag-signup' ),
							value: attributes.listId,
							onChange: ( listId ) => setAttributes( { listId } ),
						} )
					)
				),
				el( ServerSideRender, { block: 'postbag/signup', attributes } )
			);
		},
		save: () => null,
	} );
} )( window.wp );
