CaxtonBlock( {
	id      : 'sm-popular-wp/plugins',
	title   : 'Popular plugins',
	icon    : 'star-filled',
	category: 'layout',
	apiUrl  : function ( props ) {
		// Generate the API URL here
		var
			attr = props.attributes;
		return {
			blockHTML: '/sm-popular-wp/v1/plugins?display=' + attr.display,
		};
	},
	fields  : {
		'display': {
			label  : 'Top 50 plugins by',
			type   : 'select',
			options: [
				{ value: 'downloads_per_day', label: 'Downloads per day', },
				{ value: 'active_installs', label: 'Active installs', }
			],
			default: 'downloads_per_day',
		},
	},
	apiCallback: function ( props, that ) {
		if ( props.blockHTML && props.blockHTML.data ) {

			return Caxton.html2el( props.blockHTML.data, {
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
			}, 'Loading popular plugins block...' );
		}
	}
} );


