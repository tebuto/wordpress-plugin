import { Button, ColorIndicator, Flex, FlexItem } from '@wordpress/components'
import { getPresets } from '../theme'

export default function ThemePresetPicker({ onSelect }) {
	const presets = getPresets()

	return (
		<Flex gap={2} wrap justify="flex-start">
			{presets.map((preset) => (
				<FlexItem key={preset.name}>
					<Button variant="secondary" onClick={() => onSelect(preset)}>
						<span style={{ display: 'inline-flex', alignItems: 'center', gap: '8px' }}>
							<ColorIndicator colorValue={preset.primaryColor} />
							{preset.description}
						</span>
					</Button>
				</FlexItem>
			))}
		</Flex>
	)
}
