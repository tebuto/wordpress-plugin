import { BaseControl, Button, ColorIndicator, ColorPicker, Dropdown, Flex, PanelBody } from '@wordpress/components'
import { __ } from '@wordpress/i18n'

const COLOR_FIELDS = [
	{
		key: 'primaryColor',
		label: __('Primärfarbe', 'tebuto-online-terminbuchung')
	},
	{
		key: 'backgroundColor',
		label: __('Hintergrund', 'tebuto-online-terminbuchung')
	},
	{
		key: 'textPrimary',
		label: __('Textfarbe', 'tebuto-online-terminbuchung')
	},
	{
		key: 'textSecondary',
		label: __('Sekundärtext', 'tebuto-online-terminbuchung')
	},
	{
		key: 'borderColor',
		label: __('Rahmenfarbe', 'tebuto-online-terminbuchung')
	}
]

function ColorRow({ label, value, onChange }) {
	const colorValue = value || '#000000'

	return (
		<BaseControl label={label} __nextHasNoMarginBottom className="tebuto-color-row">
			<Dropdown
				popoverProps={{ placement: 'left-start' }}
				renderToggle={({ isOpen, onToggle }) => (
					<Button variant="secondary" onClick={onToggle} aria-expanded={isOpen} className="tebuto-color-row__toggle">
						<Flex align="center" gap={2}>
							<ColorIndicator colorValue={colorValue} />
							<span>{colorValue}</span>
						</Flex>
					</Button>
				)}
				renderContent={() => (
					<div className="tebuto-color-row__picker">
						<ColorPicker color={colorValue} onChange={onChange} enableAlpha={false} defaultValue={colorValue} />
					</div>
				)}
			/>
		</BaseControl>
	)
}

export default function ColorSettings({ attributes, setAttributes }) {
	return (
		<PanelBody title={__('Eigene Farben festlegen', 'tebuto-online-terminbuchung')} initialOpen={false}>
			{COLOR_FIELDS.map((field) => (
				<ColorRow
					key={field.key}
					label={field.label}
					value={attributes[field.key]}
					onChange={(color) => setAttributes({ [field.key]: color })}
				/>
			))}
		</PanelBody>
	)
}
