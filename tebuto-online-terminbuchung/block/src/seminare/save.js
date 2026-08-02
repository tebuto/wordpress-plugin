export default function save( { attributes } ) {
	const {
		primaryColor,
		backgroundColor,
		textPrimary,
		textSecondary,
		borderColor,
		border,
		inheritFont,
		seminars,
		showPast,
		customCss,
	} = attributes;

	const uuid = window.tebutoData?.uuid || '';
	const widgetUrl = 'https://tebuto.de/widget/seminars.js';

	const widgetAttributes = {
		'data-therapist-uuid': uuid,
		'data-border': border ? 'true' : 'false',
	};

	if ( primaryColor && primaryColor !== '#00B4A9' ) {
		widgetAttributes[ 'data-primary-color' ] = primaryColor;
	}

	if ( backgroundColor && backgroundColor !== '#ffffff' ) {
		widgetAttributes[ 'data-background-color' ] = backgroundColor;
	}

	if ( textPrimary && textPrimary !== '#374151' ) {
		widgetAttributes[ 'data-text-primary' ] = textPrimary;
	}

	if ( textSecondary && textSecondary !== '#6b7280' ) {
		widgetAttributes[ 'data-text-secondary' ] = textSecondary;
	}

	if ( borderColor && borderColor !== '#E9E9E9' ) {
		widgetAttributes[ 'data-border-color' ] = borderColor;
	}

	if ( inheritFont ) {
		widgetAttributes[ 'data-inherit-font' ] = 'true';
	}

	if ( seminars?.trim() ) {
		widgetAttributes[ 'data-seminars' ] = seminars.trim();
	}

	if ( showPast ) {
		widgetAttributes[ 'data-show-past' ] = 'true';
	}

	return (
		<>
			<div id="tebuto-seminars-widget" />
			<script src={ widgetUrl } { ...widgetAttributes } />
			{ customCss && (
				<style id="tebuto-seminars-custom-css">{ customCss }</style>
			) }
		</>
	);
}
