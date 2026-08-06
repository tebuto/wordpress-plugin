import { CheckboxControl, Notice, Spinner } from '@wordpress/components'
import { __ } from '@wordpress/i18n'

export default function SeminarPicker({ seminars, selected, onToggle, loading, error }) {
	return (
		<>
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
								<div
									key={seminar.id || slug}
									className={
										isSelectable ? 'tebuto-category-item' : 'tebuto-category-item tebuto-category-item--unavailable'
									}
								>
									<CheckboxControl
										label={
											<span className="tebuto-category-label">
												{seminar.title}
												{!isSelectable && (
													<span className="tebuto-category-unavailable-hint">
														{__('Nicht öffentlich', 'tebuto-online-terminbuchung')}
													</span>
												)}
											</span>
										}
										checked={isSelectable && selected.includes(slug)}
										disabled={!isSelectable || !slug}
										onChange={() => onToggle(slug)}
									/>
								</div>
							)
						})
					)}
				</div>
			)}
		</>
	)
}
