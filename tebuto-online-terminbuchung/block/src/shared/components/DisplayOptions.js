import * as WpComponents from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import { getDefaults, getTebutoData } from '../theme'

const ToolsPanel = WpComponents.ToolsPanel || WpComponents.__experimentalToolsPanel
const ToolsPanelItem = WpComponents.ToolsPanelItem || WpComponents.__experimentalToolsPanelItem
const { ToggleControl } = WpComponents

export { ToolsPanel, ToolsPanelItem }

export default function DisplayOptions({
	variant,
	attributes,
	setAttributes,
	hasSubaccountCategoriesSelected = false
}) {
	const { border, inheritFont, showProviderFilter, showLocationQuickFilter } = attributes
	const data = getTebutoData()
	const hasManagedUsers = Boolean(data.hasManagedUsers)
	const isManagingUser = Boolean(data.isManagingUser)
	const defaults = getDefaults()

	const resetAll = () => {
		const next = {
			border: defaults.border ?? true,
			inheritFont: defaults.inheritFont ?? false
		}

		if (variant !== 'seminars') {
			next.showProviderFilter = defaults.showProviderFilter ?? false
			next.showLocationQuickFilter = defaults.showLocationQuickFilter ?? false
		}

		setAttributes(next)
	}

	return (
		<ToolsPanel label={__('Anzeige-Optionen', 'tebuto-online-terminbuchung')} resetAll={resetAll}>
			<ToolsPanelItem
				hasValue={() => border !== (defaults.border ?? true)}
				label={__('Rahmen anzeigen', 'tebuto-online-terminbuchung')}
				onDeselect={() => setAttributes({ border: defaults.border ?? true })}
				isShownByDefault
			>
				<ToggleControl
					label={__('Rahmen anzeigen', 'tebuto-online-terminbuchung')}
					help={__('Zeigt einen Rahmen um das Widget', 'tebuto-online-terminbuchung')}
					checked={border}
					onChange={(value) => setAttributes({ border: value })}
				/>
			</ToolsPanelItem>

			<ToolsPanelItem
				hasValue={() => inheritFont !== (defaults.inheritFont ?? false)}
				label={__('Schriftart übernehmen', 'tebuto-online-terminbuchung')}
				onDeselect={() => setAttributes({ inheritFont: defaults.inheritFont ?? false })}
				isShownByDefault
			>
				<ToggleControl
					label={__('Schriftart übernehmen', 'tebuto-online-terminbuchung')}
					help={__('Verwendet die Schriftart deiner Website', 'tebuto-online-terminbuchung')}
					checked={inheritFont}
					onChange={(value) => setAttributes({ inheritFont: value })}
				/>
			</ToolsPanelItem>

			{variant === 'booking' && hasManagedUsers && (
				<ToolsPanelItem
					hasValue={() => showProviderFilter !== (defaults.showProviderFilter ?? false)}
					label={__('Termine von verwalteten Konten anzeigen', 'tebuto-online-terminbuchung')}
					onDeselect={() =>
						setAttributes({
							showProviderFilter: defaults.showProviderFilter ?? false
						})
					}
					isShownByDefault
				>
					<ToggleControl
						label={__('Termine von verwalteten Konten anzeigen', 'tebuto-online-terminbuchung')}
						help={
							hasSubaccountCategoriesSelected
								? __(
										'Automatisch aktiv, weil Kategorien von verwalteten Konten ausgewählt sind.',
										'tebuto-online-terminbuchung'
									)
								: __(
										'Zeigt auch Termine von verwalteten Konten an – z. B. bei gemeinsam genutzten Kategorien, die Sie erstellt haben. Klient:innen können im Widget per Anbieterfilter den gewünschten Termin wählen.',
										'tebuto-online-terminbuchung'
									)
						}
						checked={showProviderFilter || hasSubaccountCategoriesSelected}
						disabled={hasSubaccountCategoriesSelected}
						onChange={(value) => setAttributes({ showProviderFilter: value })}
					/>
				</ToolsPanelItem>
			)}

			{variant === 'booking' && isManagingUser && (
				<ToolsPanelItem
					hasValue={() => showLocationQuickFilter !== (defaults.showLocationQuickFilter ?? false)}
					label={__('Ortsfilter anzeigen', 'tebuto-online-terminbuchung')}
					onDeselect={() =>
						setAttributes({
							showLocationQuickFilter: defaults.showLocationQuickFilter ?? false
						})
					}
					isShownByDefault
				>
					<ToggleControl
						label={__('Ortsfilter anzeigen', 'tebuto-online-terminbuchung')}
						help={__('Zeigt einen Schnellfilter nach Standort im Widget', 'tebuto-online-terminbuchung')}
						checked={showLocationQuickFilter}
						onChange={(value) =>
							setAttributes({
								showLocationQuickFilter: value
							})
						}
					/>
				</ToolsPanelItem>
			)}
		</ToolsPanel>
	)
}
