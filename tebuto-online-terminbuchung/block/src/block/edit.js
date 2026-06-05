import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState } from 'react';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ColorPicker,
	ToggleControl,
	TextareaControl,
	Button,
	CheckboxControl,
	Spinner,
} from '@wordpress/components';
import './editor.scss';

// Theme presets matching the shortcode page
const THEME_PRESETS = [
	{
		name: 'Tebuto',
		description: 'Standard',
		primaryColor: '#00B4A9',
		backgroundColor: '#ffffff',
		textPrimary: '#374151',
		textSecondary: '#6b7280',
		borderColor: '#E9E9E9',
	},
	{
		name: 'Professional Blue',
		description: 'Blau',
		primaryColor: '#3b82f6',
		backgroundColor: '#ffffff',
		textPrimary: '#1e293b',
		textSecondary: '#64748b',
		borderColor: '#e2e8f0',
	},
	{
		name: 'Warm Orange',
		description: 'Orange',
		primaryColor: '#f97316',
		backgroundColor: '#ffffff',
		textPrimary: '#1c1917',
		textSecondary: '#78716c',
		borderColor: '#fed7aa',
	},
	{
		name: 'Elegant Purple',
		description: 'Lila',
		primaryColor: '#8b5cf6',
		backgroundColor: '#ffffff',
		textPrimary: '#1e1b4b',
		textSecondary: '#6b21a8',
		borderColor: '#e9d5ff',
	},
	{
		name: 'Nature Green',
		description: 'Grün',
		primaryColor: '#059669',
		backgroundColor: '#ffffff',
		textPrimary: '#14532d',
		textSecondary: '#166534',
		borderColor: '#bbf7d0',
	},
];

export default function Edit( { attributes, setAttributes } ) {
	const {
		primaryColor,
		backgroundColor,
		textPrimary,
		textSecondary,
		borderColor,
		border,
		inheritFont,
		showQuickFilters,
		showProviderFilter,
		categories,
		configuredCategoriesJson,
		customCss,
	} = attributes;

	const previewContainerRef = useRef( null );
	const widgetScriptRef = useRef( null );

	// Categories state
	const [ availableCategories, setAvailableCategories ] = useState( [] );
	const [ loadingCategories, setLoadingCategories ] = useState( true );
	const [ categoriesError, setCategoriesError ] = useState( null );
	const [ isMultiUser, setIsMultiUser ] = useState( false );

	const blockProps = useBlockProps( {
		className: 'tebuto-block-editor',
	} );

	// Get therapist UUID and other data from localized data
	const therapistUUID = window.tebutoData?.uuid || '';
	const widgetUrl = window.tebutoData?.widgetUrl || '';
	const ajaxUrl = window.ajaxurl || '/wp-admin/admin-ajax.php';

	// Parse selected categories from string
	const selectedCategories = categories
		? categories.split( ',' ).map( ( id ) => parseInt( id.trim(), 10 ) )
		: [];

	// Fetch categories on mount
	useEffect( () => {
		if ( ! therapistUUID ) {
			return;
		}

		const fetchCategories = async () => {
			try {
				const formData = new FormData();
				formData.append( 'action', 'tebuto_get_categories' );
				formData.append( 'nonce', window.tebutoData?.nonce || '' );

				const response = await fetch( ajaxUrl, {
					method: 'POST',
					body: formData,
					credentials: 'same-origin',
				} );

				const data = await response.json();

				if ( data.success ) {
					setAvailableCategories( data.data );
				} else {
					setCategoriesError(
						data.data ||
							__(
								'Kategorien konnten nicht geladen werden.',
								'tebuto-online-terminbuchung'
							)
					);
				}
			} catch ( error ) {
				setCategoriesError(
					__(
						'Verbindungsfehler beim Laden der Kategorien.',
						'tebuto-online-terminbuchung'
					)
				);
			} finally {
				setLoadingCategories( false );
			}
		};

		fetchCategories();
	}, [ therapistUUID, ajaxUrl ] );

	// Check if multi-user (for quick filters option)
	useEffect( () => {
		setIsMultiUser( window.tebutoData?.isMultiUser || false );
	}, [] );

	// Store deduplicated categories as configured-categories for the widget
	// UI dropdown. This prevents duplicate category names when multiple
	// providers share identically named categories.
	useEffect( () => {
		if ( availableCategories.length > 0 ) {
			setAttributes( {
				configuredCategoriesJson: JSON.stringify( availableCategories ),
			} );
		}
	}, [ availableCategories ] );

	// Toggle category selection
	const toggleCategory = ( categoryId ) => {
		const newSelected = selectedCategories.includes( categoryId )
			? selectedCategories.filter( ( id ) => id !== categoryId )
			: [ ...selectedCategories, categoryId ];

		setAttributes( {
			categories: newSelected.join( ',' ),
		} );
	};

	// Apply a preset
	const applyPreset = ( preset ) => {
		setAttributes( {
			primaryColor: preset.primaryColor,
			backgroundColor: preset.backgroundColor,
			textPrimary: preset.textPrimary,
			textSecondary: preset.textSecondary,
			borderColor: preset.borderColor,
		} );
	};

	// Load/reload widget preview
	const loadWidgetPreview = () => {
		if ( ! previewContainerRef.current || ! therapistUUID || ! widgetUrl ) {
			return;
		}

		// Clear container
		previewContainerRef.current.innerHTML =
			'<div id="tebuto-booking-widget"></div>';

		// Remove old script
		if ( widgetScriptRef.current ) {
			widgetScriptRef.current.remove();
		}

		// Create new script
		const script = document.createElement( 'script' );
		script.src = widgetUrl;
		script.dataset.therapistUuid = therapistUUID;
		script.dataset.primaryColor = primaryColor;
		script.dataset.backgroundColor = backgroundColor;
		script.dataset.textPrimary = textPrimary;
		script.dataset.textSecondary = textSecondary;
		script.dataset.borderColor = borderColor;
		script.dataset.border = border ? 'true' : 'false';
		script.dataset.inheritFont = inheritFont ? 'true' : 'false';
		script.dataset.showQuickFilters = showQuickFilters ? 'true' : 'false';
		if ( showProviderFilter ) {
			script.dataset.includeSubusers = 'true';
			script.dataset.showQuickFilters = 'true';
		}

		// Pass deduplicated categories for the widget UI dropdown to prevent
		// duplicate category names across providers.
		if ( configuredCategoriesJson ) {
			script.dataset.configuredCategories = configuredCategoriesJson;
		}

		// Pass category IDs to constrain the event API query.
		// When the provider filter is active, skip category IDs because
		// the main therapist's IDs differ from subuser IDs for identically
		// named categories — restricting would hide subuser events.
		if ( ! showProviderFilter && categories ) {
			script.dataset.categories = categories;
		}

		script.async = true;
		widgetScriptRef.current = script;
		previewContainerRef.current.appendChild( script );
	};

	// Reload widget when attributes change
	useEffect( () => {
		const timer = setTimeout( () => {
			loadWidgetPreview();
		}, 500 );

		return () => clearTimeout( timer );
	}, [
		primaryColor,
		backgroundColor,
		textPrimary,
		textSecondary,
		borderColor,
		border,
		inheritFont,
		showQuickFilters,
		showProviderFilter,
		categories,
		therapistUUID,
	] );

	// Initial load
	useEffect( () => {
		loadWidgetPreview();
	}, [] );

	if ( ! therapistUUID ) {
		return (
			<div { ...blockProps }>
				<div className="tebuto-block-notice">
					<p>
						<strong>
							{ __(
								'Tebuto nicht verbunden',
								'tebuto-online-terminbuchung'
							) }
						</strong>
					</p>
					<p>
						{ __(
							'Bitte verbinde zuerst dein Tebuto-Konto in den Plugin-Einstellungen.',
							'tebuto-online-terminbuchung'
						) }
					</p>
					<Button
						variant="primary"
						href={
							window.tebutoData?.settingsUrl ||
							'/wp-admin/admin.php?page=tebuto-integration'
						}
					>
						{ __(
							'Jetzt verbinden',
							'tebuto-online-terminbuchung'
						) }
					</Button>
				</div>
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				{ /* Theme Presets */ }
				<PanelBody
					title={ __(
						'Farbvorlagen',
						'tebuto-online-terminbuchung'
					) }
					initialOpen={ true }
				>
					<div className="tebuto-preset-buttons">
						{ THEME_PRESETS.map( ( preset ) => (
							<Button
								key={ preset.name }
								variant="secondary"
								className="tebuto-preset-button"
								onClick={ () => applyPreset( preset ) }
							>
								<span
									className="tebuto-preset-color-dot"
									style={ {
										backgroundColor: preset.primaryColor,
									} }
								/>
								{ preset.description }
							</Button>
						) ) }
					</div>
				</PanelBody>

				{ /* Colors */ }
				<PanelBody
					title={ __(
						'Eigene Farben festlegen',
						'tebuto-online-terminbuchung'
					) }
					initialOpen={ false }
				>
					<div className="tebuto-color-control">
						<span className="tebuto-color-label">
							{ __(
								'Primärfarbe',
								'tebuto-online-terminbuchung'
							) }
						</span>
						<p className="tebuto-color-description">
							{ __(
								'Buttons und Akzente',
								'tebuto-online-terminbuchung'
							) }
						</p>
						<ColorPicker
							color={ primaryColor }
							onChange={ ( color ) =>
								setAttributes( { primaryColor: color } )
							}
							enableAlpha={ false }
						/>
					</div>

					<hr className="tebuto-divider" />

					<div className="tebuto-color-control">
						<span className="tebuto-color-label">
							{ __(
								'Hintergrund',
								'tebuto-online-terminbuchung'
							) }
						</span>
						<p className="tebuto-color-description">
							{ __(
								'Widget-Hintergrund',
								'tebuto-online-terminbuchung'
							) }
						</p>
						<ColorPicker
							color={ backgroundColor }
							onChange={ ( color ) =>
								setAttributes( { backgroundColor: color } )
							}
							enableAlpha={ false }
						/>
					</div>

					<hr className="tebuto-divider" />

					<div className="tebuto-color-control">
						<span className="tebuto-color-label">
							{ __( 'Textfarbe', 'tebuto-online-terminbuchung' ) }
						</span>
						<p className="tebuto-color-description">
							{ __( 'Haupttext', 'tebuto-online-terminbuchung' ) }
						</p>
						<ColorPicker
							color={ textPrimary }
							onChange={ ( color ) =>
								setAttributes( { textPrimary: color } )
							}
							enableAlpha={ false }
						/>
					</div>

					<hr className="tebuto-divider" />

					<div className="tebuto-color-control">
						<span className="tebuto-color-label">
							{ __(
								'Sekundärtext',
								'tebuto-online-terminbuchung'
							) }
						</span>
						<p className="tebuto-color-description">
							{ __(
								'Beschreibungen',
								'tebuto-online-terminbuchung'
							) }
						</p>
						<ColorPicker
							color={ textSecondary }
							onChange={ ( color ) =>
								setAttributes( { textSecondary: color } )
							}
							enableAlpha={ false }
						/>
					</div>

					<hr className="tebuto-divider" />

					<div className="tebuto-color-control">
						<span className="tebuto-color-label">
							{ __(
								'Rahmenfarbe',
								'tebuto-online-terminbuchung'
							) }
						</span>
						<p className="tebuto-color-description">
							{ __(
								'Rahmen und Trennlinien',
								'tebuto-online-terminbuchung'
							) }
						</p>
						<ColorPicker
							color={ borderColor }
							onChange={ ( color ) =>
								setAttributes( { borderColor: color } )
							}
							enableAlpha={ false }
						/>
					</div>
				</PanelBody>

				{ /* Display Options */ }
				<PanelBody
					title={ __(
						'Anzeige-Optionen',
						'tebuto-online-terminbuchung'
					) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __(
							'Rahmen anzeigen',
							'tebuto-online-terminbuchung'
						) }
						help={ __(
							'Zeigt einen Rahmen um das Widget',
							'tebuto-online-terminbuchung'
						) }
						checked={ border }
						onChange={ ( value ) =>
							setAttributes( { border: value } )
						}
					/>

					<ToggleControl
						label={ __(
							'Schriftart übernehmen',
							'tebuto-online-terminbuchung'
						) }
						help={ __(
							'Verwendet die Schriftart deiner Website',
							'tebuto-online-terminbuchung'
						) }
						checked={ inheritFont }
						onChange={ ( value ) =>
							setAttributes( { inheritFont: value } )
						}
					/>

					{ isMultiUser && (
						<ToggleControl
							label={ __(
								'Schnellfilter anzeigen',
								'tebuto-online-terminbuchung'
							) }
							help={ __(
								'Zeigt Schnellfilter für Termine',
								'tebuto-online-terminbuchung'
							) }
							checked={ showQuickFilters }
							onChange={ ( value ) =>
								setAttributes( { showQuickFilters: value } )
							}
						/>
					) }

					<ToggleControl
						label={ __(
							'Anbieterfilter anzeigen',
							'tebuto-online-terminbuchung'
						) }
						help={ __(
							'Zeigt einen Filter zur Auswahl des Anbieters im Widget',
							'tebuto-online-terminbuchung'
						) }
						checked={ showProviderFilter }
						onChange={ ( value ) =>
							setAttributes( { showProviderFilter: value } )
						}
					/>
				</PanelBody>

				{ /* Categories */ }
				<PanelBody
					title={ __( 'Kategorien', 'tebuto-online-terminbuchung' ) }
					initialOpen={ false }
				>
					<p className="tebuto-panel-description">
						{ __(
							'Wähle die Kategorien aus, die im Widget angezeigt werden sollen. Keine Auswahl = alle Kategorien.',
							'tebuto-online-terminbuchung'
						) }
					</p>

					{ loadingCategories && (
						<div className="tebuto-loading">
							<Spinner />
							<span>
								{ __(
									'Kategorien werden geladen…',
									'tebuto-online-terminbuchung'
								) }
							</span>
						</div>
					) }

					{ categoriesError && (
						<p className="tebuto-error">{ categoriesError }</p>
					) }

					{ ! loadingCategories && ! categoriesError && (
						<div className="tebuto-category-list">
							{ availableCategories.length === 0 ? (
								<p className="tebuto-empty">
									{ __(
										'Keine Kategorien vorhanden.',
										'tebuto-online-terminbuchung'
									) }
								</p>
							) : (
								availableCategories.map( ( cat ) => (
									<div
										key={ cat.id }
										className="tebuto-category-item"
									>
										<CheckboxControl
											label={
												<span className="tebuto-category-label">
													<span
														className="tebuto-category-color"
														style={ {
															backgroundColor:
																cat.color,
														} }
													/>
													{ cat.name }
												</span>
											}
											checked={ selectedCategories.includes(
												cat.id
											) }
											onChange={ () =>
												toggleCategory( cat.id )
											}
										/>
									</div>
								) )
							) }
						</div>
					) }
				</PanelBody>

				{ /* Custom CSS */ }
				<PanelBody
					title={ __( 'Custom CSS', 'tebuto-online-terminbuchung' ) }
					initialOpen={ false }
				>
					<TextareaControl
						label={ __(
							'Eigenes CSS',
							'tebuto-online-terminbuchung'
						) }
						help={ __(
							'Füge eigenes CSS hinzu. Verwende #tebuto-booking-widget als Selektor-Präfix.',
							'tebuto-online-terminbuchung'
						) }
						value={ customCss }
						onChange={ ( value ) =>
							setAttributes( { customCss: value } )
						}
						rows={ 8 }
						className="tebuto-css-textarea"
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div
					className="tebuto-block-preview"
					ref={ previewContainerRef }
				>
					<div id="tebuto-booking-widget"></div>
				</div>
			</div>
		</>
	);
}
