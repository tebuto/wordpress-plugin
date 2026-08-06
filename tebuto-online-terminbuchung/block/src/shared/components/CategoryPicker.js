import { Notice, Spinner, ToggleControl } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import SelectableRow from './SelectableRow'

export default function CategoryPicker({
	categories,
	selected,
	onToggle,
	loading,
	error,
	showCategorySelectionFirst,
	onShowCategorySelectionFirstChange,
	selectableCount = 0
}) {
	return (
		<>
			{onShowCategorySelectionFirstChange && (
				<ToggleControl
					label={__('Kategorieauswahl als ersten Schritt', 'tebuto-online-terminbuchung')}
					help={__(
						'Zeigt bei mehreren ausgewählten Kategorien zuerst eine Kategorieauswahl, bevor der Kalender erscheint.',
						'tebuto-online-terminbuchung'
					)}
					checked={selectableCount > 1 && showCategorySelectionFirst !== false}
					disabled={selectableCount <= 1}
					onChange={onShowCategorySelectionFirstChange}
				/>
			)}

			<p className="tebuto-panel-description">
				{__(
					'Alle Kategorien werden angezeigt. Nur öffentliche Kategorien können im Widget verwendet werden.',
					'tebuto-online-terminbuchung'
				)}
			</p>

			{loading && (
				<div className="tebuto-loading">
					<Spinner />
					<span>{__('Kategorien werden geladen…', 'tebuto-online-terminbuchung')}</span>
				</div>
			)}

			{error && (
				<Notice status="error" isDismissible={false}>
					{error}
				</Notice>
			)}

			{!loading && !error && (
				<div className="tebuto-category-list">
					{categories.length === 0 ? (
						<p className="tebuto-empty">{__('Keine Kategorien vorhanden.', 'tebuto-online-terminbuchung')}</p>
					) : (
						categories.map((cat) => {
							const isSelectable = Boolean(cat.widgetSelectable ?? cat.publicBookingEnabled)

							return (
								<SelectableRow
									key={cat.id}
									checked={isSelectable && selected.includes(cat.id)}
									disabled={!isSelectable}
									onChange={() => onToggle(cat.id)}
									leading={
										<span
											className="tebuto-category-color"
											style={{
												backgroundColor: cat.color
											}}
											aria-hidden="true"
										/>
									}
								>
									<span className="tebuto-category-label-text">{cat.name}</span>
									{!isSelectable && (
										<span className="tebuto-category-unavailable-hint">
											{__('Nicht öffentlich', 'tebuto-online-terminbuchung')}
										</span>
									)}
								</SelectableRow>
							)
						})
					)}
				</div>
			)}
		</>
	)
}
