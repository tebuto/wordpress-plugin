import { PanelColorSettings } from '@wordpress/block-editor'
import { __ } from '@wordpress/i18n'

export default function ColorSettings({ attributes, setAttributes }) {
	const { primaryColor, backgroundColor, textPrimary, textSecondary, borderColor } = attributes

	return (
		<PanelColorSettings
			title={__('Eigene Farben festlegen', 'tebuto-online-terminbuchung')}
			initialOpen={false}
			__experimentalIsRenderedInSidebar
			enableAlpha={false}
			colorSettings={[
				{
					value: primaryColor,
					onChange: (color) => setAttributes({ primaryColor: color }),
					label: __('Primärfarbe', 'tebuto-online-terminbuchung'),
					description: __('Buttons und Akzente', 'tebuto-online-terminbuchung')
				},
				{
					value: backgroundColor,
					onChange: (color) => setAttributes({ backgroundColor: color }),
					label: __('Hintergrund', 'tebuto-online-terminbuchung'),
					description: __('Widget-Hintergrund', 'tebuto-online-terminbuchung')
				},
				{
					value: textPrimary,
					onChange: (color) => setAttributes({ textPrimary: color }),
					label: __('Textfarbe', 'tebuto-online-terminbuchung'),
					description: __('Haupttext', 'tebuto-online-terminbuchung')
				},
				{
					value: textSecondary,
					onChange: (color) => setAttributes({ textSecondary: color }),
					label: __('Sekundärtext', 'tebuto-online-terminbuchung'),
					description: __('Beschreibungen', 'tebuto-online-terminbuchung')
				},
				{
					value: borderColor,
					onChange: (color) => setAttributes({ borderColor: color }),
					label: __('Rahmenfarbe', 'tebuto-online-terminbuchung'),
					description: __('Rahmen und Trennlinien', 'tebuto-online-terminbuchung')
				}
			]}
		/>
	)
}
