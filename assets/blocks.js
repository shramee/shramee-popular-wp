CaxtonBlock( {
	id      : 'caxton-boilerplate/demo',
	title   : 'Caxton boilerplate',
	icon    : 'star-filled',
	category: 'layout',
	apiUrl  : function ( props ) {
		// Generate the API URL here
		var
			attr = props.attributes;
		return {
			blockHTML: '/caxton-boilerplate/v1/demo?color=' + attr['color'],
		};
	},
	fields  : {
		'color': {
			label  : 'Text color',
			type   : 'color',
			default: '#e91d63',
		},
	},
	apiCallback: function ( props, that ) {
		if ( props.blockHTML && props.blockHTML.data ) {

			return Caxton.html2el( props.blockHTML.data, {
				className: 'woocommerce',
				key      : 'block-html',
				style    : {},
				onClick  : function ( e ) {
					e.preventDefault();
				}
			} );
		} else {
			return wp.element.createElement( 'div', {
				className: 'caxton-notification',
				key      : 'notice'
			}, 'Loading demo block...' );
		}
	}
} );


