import { Notice, Spinner, ToggleControl } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import SelectableRow from './SelectableRow'

export default function SeminarPicker({
	seminars,
	selected,
	onToggle,
	loading,
	error,
	showListFirst,
	onShowListFirstChange
}) {
	return (
		<>
			{onShowListFirstChange && (
				<ToggleControl
					label={__('Seminarliste zuerst anzeigen', 'tebuto-online-terminbuchung')}
					help={__(
						'Wenn deaktiviert, öffnet das Widget direkt die Detailseite – nur sinnvoll bei genau einem Seminar.',
						'tebuto-online-terminbuchung'
					)}
					checked={showListFirst !== false}
					onChange={onShowListFirstChange}
				/>
			)}

			<p className="tebuto-panel-description">
				{__(
					'Keine Auswahl = alle Seminare. Nur öffentliche Seminare können im Widget verwendet werden.',
					'tebuto-online-terminbuchung'
				)}
			</p>

			{loading && (
				<div className="tebuto-loading">
					<Spinner />
					<span>{__('Seminare werden geladen…', 'tebuto-online-terminbuchung')}</span>
				</div>
			)}

			{error && (
				<Notice status="error" isDismissible={false}>
					{error}
				</Notice>
			)}

			{!loading && !error && (
				<div className="tebuto-category-list">
					{seminars.length === 0 ? (
						<p className="tebuto-empty">{__('Keine Seminare vorhanden.', 'tebuto-online-terminbuchung')}</p>
					) : (
						seminars.map((seminar) => {
							const isSelectable = Boolean(seminar.publicPageEnabled)
							const slug = seminar.slug || ''

							return (
								<SelectableRow
									key={seminar.id || slug}
									checked={isSelectable && selected.includes(slug)}
									disabled={!isSelectable || !slug}
									onChange={() => onToggle(slug)}
								>
									<span className="tebuto-category-label-text">{seminar.title}</span>
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
