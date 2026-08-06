import { InspectorControls, useBlockProps } from '@wordpress/block-editor'
import { Button, ColorPicker, PanelBody, TextareaControl, TextControl, ToggleControl } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import { useCallback, useEffect, useRef } from 'react'
import '../block/editor.scss'

const THEME_PRESETS = [
	{
		name: 'Tebuto',
		description: 'Standard',
		primaryColor: '#00B4A9',
		backgroundColor: '#ffffff',
		textPrimary: '#374151',
		textSecondary: '#6b7280',
		borderColor: '#E9E9E9'
	},
	{
		name: 'Professional Blue',
		description: 'Blau',
		primaryColor: '#3b82f6',
		backgroundColor: '#ffffff',
		textPrimary: '#1e293b',
		textSecondary: '#64748b',
		borderColor: '#e2e8f0'
	},
	{
		name: 'Warm Orange',
		description: 'Orange',
		primaryColor: '#f97316',
		backgroundColor: '#ffffff',
		textPrimary: '#1c1917',
		textSecondary: '#78716c',
		borderColor: '#fed7aa'
	},
	{
		name: 'Elegant Purple',
		description: 'Lila',
		primaryColor: '#8b5cf6',
		backgroundColor: '#ffffff',
		textPrimary: '#1e1b4b',
		textSecondary: '#6b21a8',
		borderColor: '#e9d5ff'
	},
	{
		name: 'Nature Green',
		description: 'Grün',
		primaryColor: '#059669',
		backgroundColor: '#ffffff',
		textPrimary: '#14532d',
		textSecondary: '#166534',
		borderColor: '#bbf7d0'
	}
]

export default function Edit({ attributes, setAttributes }) {
	const {
		primaryColor,
		backgroundColor,
		textPrimary,
		textSecondary,
		borderColor,
		border,
		inheritFont,
		seminars,
		showListFirst,
		customCss
	} = attributes

	const previewContainerRef = useRef(null)
	const widgetScriptRef = useRef(null)

	const blockProps = useBlockProps({
		className: 'tebuto-block-editor'
	})

	const therapistUUID = window.tebutoData?.uuid || ''
	const authState = window.tebutoData?.authState || 'disconnected'
	const reconnectUrl =
		window.tebutoData?.reconnectUrl || window.tebutoData?.settingsUrl || '/wp-admin/admin.php?page=tebuto-main'
	const widgetUrl = window.tebutoData?.seminarsWidgetUrl || 'https://tebuto.de/widget/seminars.js'

	const isSessionExpired = authState === 'expired'

	const applyPreset = (preset) => {
		setAttributes({
			primaryColor: preset.primaryColor,
			backgroundColor: preset.backgroundColor,
			textPrimary: preset.textPrimary,
			textSecondary: preset.textSecondary,
			borderColor: preset.borderColor
		})
	}

	const loadWidgetPreview = useCallback(() => {
		if (!previewContainerRef.current || !therapistUUID || !widgetUrl) {
			return
		}

		previewContainerRef.current.innerHTML = '<div id="tebuto-seminars-widget"></div>'

		if (widgetScriptRef.current) {
			widgetScriptRef.current.remove()
		}

		const script = document.createElement('script')
		script.src = widgetUrl
		script.dataset.therapistUuid = therapistUUID
		script.dataset.primaryColor = primaryColor
		script.dataset.backgroundColor = backgroundColor
		script.dataset.textPrimary = textPrimary
		script.dataset.textSecondary = textSecondary
		script.dataset.borderColor = borderColor
		script.dataset.border = border ? 'true' : 'false'
		script.dataset.inheritFont = inheritFont ? 'true' : 'false'

		if (seminars?.trim()) {
			script.dataset.seminars = seminars.trim()
		}

		if (showListFirst === false) {
			script.dataset.showListFirst = 'false'
		}

		script.async = true
		widgetScriptRef.current = script
		previewContainerRef.current.appendChild(script)
	}, [
		therapistUUID,
		widgetUrl,
		primaryColor,
		backgroundColor,
		textPrimary,
		textSecondary,
		borderColor,
		border,
		inheritFont,
		seminars,
		showListFirst
	])

	useEffect(() => {
		const timer = setTimeout(() => {
			loadWidgetPreview()
		}, 500)

		return () => clearTimeout(timer)
	}, [loadWidgetPreview])

	if (isSessionExpired) {
		return (
			<div {...blockProps}>
				<div className="tebuto-block-notice tebuto-block-notice-expired">
					<p>
						<strong>{__('Sitzung abgelaufen', 'tebuto-online-terminbuchung')}</strong>
					</p>
					<p>
						{__(
							'Deine Verbindung zu Tebuto ist abgelaufen. Bitte melde dich erneut an, um das Widget zu konfigurieren.',
							'tebuto-online-terminbuchung'
						)}
					</p>
					<Button variant="primary" href={reconnectUrl}>
						{__('Erneut bei Tebuto anmelden', 'tebuto-online-terminbuchung')}
					</Button>
				</div>
			</div>
		)
	}

	if (!therapistUUID || authState === 'disconnected') {
		return (
			<div {...blockProps}>
				<div className="tebuto-block-notice">
					<p>
						<strong>{__('Tebuto nicht verbunden', 'tebuto-online-terminbuchung')}</strong>
					</p>
					<p>
						{__('Bitte verbinde zuerst dein Tebuto-Konto in den Plugin-Einstellungen.', 'tebuto-online-terminbuchung')}
					</p>
					<Button variant="primary" href={reconnectUrl}>
						{__('Jetzt verbinden', 'tebuto-online-terminbuchung')}
					</Button>
				</div>
			</div>
		)
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Seminare', 'tebuto-online-terminbuchung')} initialOpen={true}>
					<TextControl
						label={__('Seminar-Slugs (optional)', 'tebuto-online-terminbuchung')}
						help={__(
							'Kommagetrennte Slugs, z. B. einführung,aufbaukurs. Leer lassen, um alle Seminare anzuzeigen.',
							'tebuto-online-terminbuchung'
						)}
						value={seminars}
						onChange={(value) => setAttributes({ seminars: value })}
					/>

					<ToggleControl
						label={__('Seminarliste zuerst anzeigen', 'tebuto-online-terminbuchung')}
						help={__(
							'Wenn deaktiviert, öffnet das Widget direkt die Detailseite – nur sinnvoll bei genau einem Seminar.',
							'tebuto-online-terminbuchung'
						)}
						checked={showListFirst !== false}
						onChange={(value) => setAttributes({ showListFirst: value })}
					/>
				</PanelBody>

				<PanelBody title={__('Farbvorlagen', 'tebuto-online-terminbuchung')} initialOpen={false}>
					<div className="tebuto-preset-buttons">
						{THEME_PRESETS.map((preset) => (
							<Button
								key={preset.name}
								variant="secondary"
								className="tebuto-preset-button"
								onClick={() => applyPreset(preset)}
							>
								<span
									className="tebuto-preset-color-dot"
									style={{
										backgroundColor: preset.primaryColor
									}}
								/>
								{preset.description}
							</Button>
						))}
					</div>
				</PanelBody>

				<PanelBody title={__('Eigene Farben festlegen', 'tebuto-online-terminbuchung')} initialOpen={false}>
					<div className="tebuto-color-control">
						<span className="tebuto-color-label">{__('Primärfarbe', 'tebuto-online-terminbuchung')}</span>
						<p className="tebuto-color-description">{__('Buttons und Akzente', 'tebuto-online-terminbuchung')}</p>
						<ColorPicker
							color={primaryColor}
							onChange={(color) => setAttributes({ primaryColor: color })}
							enableAlpha={false}
						/>
					</div>

					<hr className="tebuto-divider" />

					<div className="tebuto-color-control">
						<span className="tebuto-color-label">{__('Hintergrund', 'tebuto-online-terminbuchung')}</span>
						<p className="tebuto-color-description">{__('Widget-Hintergrund', 'tebuto-online-terminbuchung')}</p>
						<ColorPicker
							color={backgroundColor}
							onChange={(color) => setAttributes({ backgroundColor: color })}
							enableAlpha={false}
						/>
					</div>

					<hr className="tebuto-divider" />

					<div className="tebuto-color-control">
						<span className="tebuto-color-label">{__('Textfarbe', 'tebuto-online-terminbuchung')}</span>
						<p className="tebuto-color-description">{__('Haupttext', 'tebuto-online-terminbuchung')}</p>
						<ColorPicker
							color={textPrimary}
							onChange={(color) => setAttributes({ textPrimary: color })}
							enableAlpha={false}
						/>
					</div>

					<hr className="tebuto-divider" />

					<div className="tebuto-color-control">
						<span className="tebuto-color-label">{__('Sekundärtext', 'tebuto-online-terminbuchung')}</span>
						<p className="tebuto-color-description">{__('Beschreibungen', 'tebuto-online-terminbuchung')}</p>
						<ColorPicker
							color={textSecondary}
							onChange={(color) => setAttributes({ textSecondary: color })}
							enableAlpha={false}
						/>
					</div>

					<hr className="tebuto-divider" />

					<div className="tebuto-color-control">
						<span className="tebuto-color-label">{__('Rahmenfarbe', 'tebuto-online-terminbuchung')}</span>
						<p className="tebuto-color-description">{__('Rahmen und Trennlinien', 'tebuto-online-terminbuchung')}</p>
						<ColorPicker
							color={borderColor}
							onChange={(color) => setAttributes({ borderColor: color })}
							enableAlpha={false}
						/>
					</div>
				</PanelBody>

				<PanelBody title={__('Anzeige-Optionen', 'tebuto-online-terminbuchung')} initialOpen={false}>
					<ToggleControl
						label={__('Rahmen anzeigen', 'tebuto-online-terminbuchung')}
						help={__('Zeigt einen Rahmen um das Widget', 'tebuto-online-terminbuchung')}
						checked={border}
						onChange={(value) => setAttributes({ border: value })}
					/>

					<ToggleControl
						label={__('Schriftart übernehmen', 'tebuto-online-terminbuchung')}
						help={__('Verwendet die Schriftart deiner Website', 'tebuto-online-terminbuchung')}
						checked={inheritFont}
						onChange={(value) => setAttributes({ inheritFont: value })}
					/>
				</PanelBody>

				<PanelBody title={__('Custom CSS', 'tebuto-online-terminbuchung')} initialOpen={false}>
					<TextareaControl
						label={__('Eigenes CSS', 'tebuto-online-terminbuchung')}
						help={__(
							'Füge eigenes CSS hinzu. Verwende #tebuto-seminars-widget als Selektor-Präfix.',
							'tebuto-online-terminbuchung'
						)}
						value={customCss}
						onChange={(value) => setAttributes({ customCss: value })}
						rows={8}
						className="tebuto-css-textarea"
					/>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				<div className="tebuto-block-preview" ref={previewContainerRef}>
					<div id="tebuto-seminars-widget"></div>
				</div>
			</div>
		</>
	)
}
