export default function save( { attributes } ) {
	const {
		primaryColor,
		backgroundColor,
		textPrimary,
		textSecondary,
		borderColor,
		border,
		inheritFont,
		showProviderFilter,
		showLocationQuickFilter,
		showCategorySelectionFirst,
		categories,
		configuredCategoriesJson,
		customCss,
	} = attributes;

	const uuid = window.tebutoData?.uuid || '';
	const widgetUrl = 'https://tebuto.de/widget/booking.js';

	// Build widget attributes
	const widgetAttributes = {
		'data-therapist-uuid': uuid,
		'data-border': border ? 'true' : 'false',
	};

	// Color attributes (only add if different from defaults)
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

	// Boolean attributes
	if ( inheritFont ) {
		widgetAttributes[ 'data-inherit-font' ] = 'true';
	}

	let hasSubaccountCategories = false;
	if ( configuredCategoriesJson ) {
		try {
			const configuredCategories = JSON.parse( configuredCategoriesJson );
			hasSubaccountCategories = Array.isArray( configuredCategories )
				? configuredCategories.some(
						( category ) => category.isFromSubaccount === true
				  )
				: false;
		} catch {
			hasSubaccountCategories = false;
		}
	}

	if ( showProviderFilter || hasSubaccountCategories ) {
		widgetAttributes[ 'data-include-subusers' ] = 'true';
		widgetAttributes[ 'data-show-quick-filters' ] = 'true';
	}

	if ( showLocationQuickFilter ) {
		widgetAttributes[ 'data-show-location-quick-filter' ] = 'true';
	}

	if (
		( showProviderFilter || hasSubaccountCategories ) &&
		configuredCategoriesJson
	) {
		widgetAttributes[ 'data-configured-categories' ] =
			configuredCategoriesJson;
	}

	let categoriesForEmbed = categories;
	if ( ! categoriesForEmbed?.trim() && configuredCategoriesJson ) {
		try {
			const configuredCategories = JSON.parse( configuredCategoriesJson );
			if ( Array.isArray( configuredCategories ) ) {
				const categoryIds = configuredCategories
					.map( ( category ) => Number( category.id ) )
					.filter(
						( categoryId ) =>
							Number.isFinite( categoryId ) && categoryId > 0
					);
				if ( categoryIds.length > 0 ) {
					categoriesForEmbed = categoryIds.join( ',' );
				}
			}
		} catch {
			categoriesForEmbed = categories;
		}
	}

	if ( categoriesForEmbed?.trim() ) {
		widgetAttributes[ 'data-categories' ] = categoriesForEmbed;
	}

	if ( showCategorySelectionFirst === false ) {
		widgetAttributes[ 'data-show-category-selection-first' ] = 'false';
	}

	return (
		<>
			<div id="tebuto-booking-widget" />
			<script src={ widgetUrl } { ...widgetAttributes } />
			{ customCss && <style id="tebuto-custom-css">{ customCss }</style> }
		</>
	);
}
