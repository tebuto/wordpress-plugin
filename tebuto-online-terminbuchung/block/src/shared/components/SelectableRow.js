export default function SelectableRow({ checked, disabled, onChange, leading = null, children }) {
	return (
		<label className={disabled ? 'tebuto-category-item tebuto-category-item--unavailable' : 'tebuto-category-item'}>
			<input
				type="checkbox"
				className="tebuto-category-checkbox"
				checked={checked}
				disabled={disabled}
				onChange={(event) => onChange(event.target.checked)}
			/>
			{leading}
			<span className="tebuto-category-label">{children}</span>
		</label>
	)
}
