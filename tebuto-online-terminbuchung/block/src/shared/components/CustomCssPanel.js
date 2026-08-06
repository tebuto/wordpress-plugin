import { PanelBody, TextareaControl } from '@wordpress/components'
import { __ } from '@wordpress/i18n'

export default function CustomCssPanel({ variant, value, onChange }) {
	const help =
		variant === 'seminars'
			? __(
					'Füge eigenes CSS hinzu. Verwende #tebuto-seminars-widget als Selektor-Präfix.',
					'tebuto-online-terminbuchung'
				)
			: __(
					'Füge eigenes CSS hinzu. Verwende #tebuto-booking-widget als Selektor-Präfix.',
					'tebuto-online-terminbuchung'
				)

	return (
		<PanelBody title={__('Custom CSS', 'tebuto-online-terminbuchung')} initialOpen={false}>
			<TextareaControl
				label={__('Eigenes CSS', 'tebuto-online-terminbuchung')}
				help={help}
				value={value || ''}
				onChange={onChange}
				rows={8}
				className="tebuto-css-textarea"
			/>
		</PanelBody>
	)
}
