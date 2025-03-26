export default function save({ attributes }) {
	const { backgroundColor, border } = attributes;

	const uuid = tebutoData?.uuid || "";

	const widgetAttributes = {
		"data-therapist-uuid": uuid,
	};

	widgetAttributes["data-border"] = border;

	if (backgroundColor && backgroundColor !== "#ffffff") {
		widgetAttributes["data-background-color"] = backgroundColor;
	}

	return (
		<>
			<div id="tebuto-booking-widget" />
			<script src="https://tebuto.de/widget/booking.js" {...widgetAttributes} />
		</>
	);
}
