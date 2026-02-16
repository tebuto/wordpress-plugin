export default function save({ attributes }) {
	const {
		primaryColor,
		backgroundColor,
		textPrimary,
		textSecondary,
		borderColor,
		border,
		inheritFont,
		showQuickFilters,
		showProviderFilter,
		categories,
		configuredCategoriesJson,
		customCss,
	} = attributes;

	const uuid = window.tebutoData?.uuid || "";
	const widgetUrl =
		window.tebutoData?.widgetUrl ||
		"https://tebuto.de/widget/booking.js";

	// Build widget attributes
	const widgetAttributes = {
		"data-therapist-uuid": uuid,
		"data-border": border ? "true" : "false",
	};

	// Color attributes (only add if different from defaults)
	if (primaryColor && primaryColor !== "#00B4A9") {
		widgetAttributes["data-primary-color"] = primaryColor;
	}

	if (backgroundColor && backgroundColor !== "#ffffff") {
		widgetAttributes["data-background-color"] = backgroundColor;
	}

	if (textPrimary && textPrimary !== "#374151") {
		widgetAttributes["data-text-primary"] = textPrimary;
	}

	if (textSecondary && textSecondary !== "#6b7280") {
		widgetAttributes["data-text-secondary"] = textSecondary;
	}

	if (borderColor && borderColor !== "#E9E9E9") {
		widgetAttributes["data-border-color"] = borderColor;
	}

	// Boolean attributes
	if (inheritFont) {
		widgetAttributes["data-inherit-font"] = "true";
	}

	if (showQuickFilters) {
		widgetAttributes["data-show-quick-filters"] = "true";
	}

	if (showProviderFilter) {
		widgetAttributes["data-include-subusers"] = "true";
		widgetAttributes["data-show-quick-filters"] = "true";
		// Do NOT pass data-configured-categories when include-subusers is
		// active — the external widget breaks its therapist dropdown when
		// this attribute is present alongside include-subusers.
	}

	// Categories filter — skip when provider filter is active because the
	// main therapist's category IDs differ from the subuser's IDs for
	// identically named categories; restricting would hide subuser events.
	if (categories && !showProviderFilter) {
		widgetAttributes["data-categories"] = categories;
	}

	return (
		<>
			<div id="tebuto-booking-widget" />
			<script src={widgetUrl} {...widgetAttributes} />
			{customCss && (
				<style id="tebuto-custom-css">{customCss}</style>
			)}
		</>
	);
}
