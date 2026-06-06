import { __ } from '@wordpress/i18n';
import { useEffect, useMemo, useRef, useState } from 'react';
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
		showProviderFilter,
		showLocationQuickFilter,
		showCategorySelectionFirst,
		categories,
		configuredCategoriesJson,
		configuredTherapistsJson,
		customCss,
	} = attributes;

	const previewContainerRef = useRef( null );
	const widgetScriptRef = useRef( null );

	// Categories state
	const [ availableCategories, setAvailableCategories ] = useState( [] );
	const [ loadingCategories, setLoadingCategories ] = useState( true );
	const [ categoriesError, setCategoriesError ] = useState( null );
	const [ hasManagedUsers, setHasManagedUsers ] = useState( false );
	const [ isManagingUser, setIsManagingUser ] = useState( false );

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

	useEffect( () => {
		setHasManagedUsers( window.tebutoData?.hasManagedUsers || false );
		setIsManagingUser( window.tebutoData?.isManagingUser || false );
	}, [] );

	const isCategoryWidgetSelectable = ( category ) =>
		Boolean( category.widgetSelectable ?? category.publicBookingEnabled );

	const selectedAvailableCategories = useMemo(
		() =>
			availableCategories.filter(
				( category ) =>
					selectedCategories.includes( category.id ) &&
					isCategoryWidgetSelectable( category )
			),
		[ availableCategories, selectedCategories ]
	);
	const hasSubaccountCategoriesSelected = selectedAvailableCategories.some(
		( category ) => category.isFromSubaccount
	);
	const shouldUseConfiguredCategories =
		showProviderFilter || hasSubaccountCategoriesSelected;

	const configuredTherapistsForEmbed = useMemo( () => {
		if (
			! shouldUseConfiguredCategories ||
			selectedAvailableCategories.length === 0
		) {
			return [];
		}

		const seenTherapistIds = new Set();
		return selectedAvailableCategories.reduce( ( therapists, category ) => {
			const therapistId = category.therapistId;
			if (
				! therapistId ||
				seenTherapistIds.has( therapistId )
			) {
				return therapists;
			}

			seenTherapistIds.add( therapistId );
			therapists.push( {
				id: therapistId,
				name: category.therapistName || '',
			} );
			return therapists;
		}, [] );
	}, [ shouldUseConfiguredCategories, selectedAvailableCategories ] );

	useEffect( () => {
		if ( ! shouldUseConfiguredCategories || selectedAvailableCategories.length === 0 ) {
			if ( configuredCategoriesJson ) {
				setAttributes( { configuredCategoriesJson: '' } );
			}
			return;
		}

		const nextCategoriesJson = JSON.stringify(
			selectedAvailableCategories.map( ( category ) => ( {
				id: category.id,
				name: category.name,
				color: category.color,
				isFromSubaccount: Boolean( category.isFromSubaccount ),
				therapistId: category.therapistId ?? 0,
				therapistName: category.therapistName ?? '',
			} ) )
		);
		if ( configuredCategoriesJson !== nextCategoriesJson ) {
			setAttributes( {
				configuredCategoriesJson: nextCategoriesJson,
			} );
		}
	}, [
		selectedAvailableCategories,
		shouldUseConfiguredCategories,
		configuredCategoriesJson,
	] );

	useEffect( () => {
		if ( ! shouldUseConfiguredCategories ) {
			if ( configuredTherapistsJson ) {
				setAttributes( { configuredTherapistsJson: '' } );
			}
			return;
		}

		const nextTherapistsJson =
			configuredTherapistsForEmbed.length > 0
				? JSON.stringify( configuredTherapistsForEmbed )
				: '';

		if ( configuredTherapistsJson !== nextTherapistsJson ) {
			setAttributes( {
				configuredTherapistsJson: nextTherapistsJson,
			} );
		}
	}, [
		shouldUseConfiguredCategories,
		configuredTherapistsForEmbed,
		configuredTherapistsJson,
	] );

	useEffect( () => {
		if (
			availableCategories.length > 0 &&
			( ! categories || categories.trim() === '' )
		) {
			setAttributes( {
				categories: availableCategories
					.filter( ( category ) => isCategoryWidgetSelectable( category ) )
					.map( ( category ) => category.id )
					.join( ',' ),
			} );
		}
	}, [ availableCategories, categories ] );

	// Toggle category selection
	const toggleCategory = ( categoryId ) => {
		const category = availableCategories.find(
			( entry ) => entry.id === categoryId
		);
		if ( category && ! isCategoryWidgetSelectable( category ) ) {
			return;
		}

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
		const embedUsesManagedAccounts =
			shouldUseConfiguredCategories ||
			( Boolean( categories?.trim() ) && hasManagedUsers );

		if ( embedUsesManagedAccounts ) {
			script.dataset.includeSubusers = 'true';
			script.dataset.showQuickFilters = 'true';
		}
		if ( showLocationQuickFilter ) {
			script.dataset.showLocationQuickFilter = 'true';
		}

		if (
			shouldUseConfiguredCategories &&
			selectedAvailableCategories.length > 0
		) {
			script.dataset.configuredCategories = JSON.stringify(
				selectedAvailableCategories.map( ( category ) => ( {
					id: category.id,
					name: category.name,
					color: category.color,
					isFromSubaccount: Boolean( category.isFromSubaccount ),
					therapistId: category.therapistId ?? 0,
					therapistName: category.therapistName ?? '',
				} ) )
			);
		}
		if (
			shouldUseConfiguredCategories &&
			configuredTherapistsForEmbed.length > 0
		) {
			script.dataset.configuredTherapists = JSON.stringify(
				configuredTherapistsForEmbed
			);
		}

		// Always pass explicit category selections to the API. Subaccount-owned
		// categories use local row IDs; omitting this reverts to all manager
		// categories and surfaces inherited slots the user did not select.
		if ( categories ) {
			script.dataset.categories = categories;
		}

		if ( showCategorySelectionFirst === false ) {
			script.dataset.showCategorySelectionFirst = 'false';
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
		showProviderFilter,
		showLocationQuickFilter,
		showCategorySelectionFirst,
		categories,
		shouldUseConfiguredCategories,
		selectedAvailableCategories,
		configuredTherapistsForEmbed,
		hasManagedUsers,
		therapistUUID,
	] );

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
				{ selectedAvailableCategories.length > 1 && (
					<PanelBody
						title={ __(
							'Kategorieauswahl',
							'tebuto-online-terminbuchung'
						) }
						initialOpen={ true }
					>
						<ToggleControl
							label={ __(
								'Kategorieauswahl als ersten Schritt',
								'tebuto-online-terminbuchung'
							) }
							help={ __(
								'Zeigt bei mehreren ausgewählten Kategorien zuerst eine Kategorieauswahl, bevor der Kalender erscheint.',
								'tebuto-online-terminbuchung'
							) }
							checked={ showCategorySelectionFirst !== false }
							onChange={ ( value ) =>
								setAttributes( {
									showCategorySelectionFirst: value,
								} )
							}
						/>
					</PanelBody>
				) }

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

					{ hasManagedUsers && (
						<ToggleControl
							label={ __(
								'Termine von verwalteten Konten anzeigen',
								'tebuto-online-terminbuchung'
							) }
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
							checked={
								showProviderFilter ||
								hasSubaccountCategoriesSelected
							}
							disabled={ hasSubaccountCategoriesSelected }
							onChange={ ( value ) =>
								setAttributes( { showProviderFilter: value } )
							}
						/>
					) }

					{ isManagingUser && (
						<ToggleControl
							label={ __(
								'Ortsfilter anzeigen',
								'tebuto-online-terminbuchung'
							) }
							help={ __(
								'Zeigt einen Schnellfilter nach Standort im Widget',
								'tebuto-online-terminbuchung'
							) }
							checked={ showLocationQuickFilter }
							onChange={ ( value ) =>
								setAttributes( {
									showLocationQuickFilter: value,
								} )
							}
						/>
					) }
				</PanelBody>

				{ /* Categories */ }
				<PanelBody
					title={ __( 'Kategorien', 'tebuto-online-terminbuchung' ) }
					initialOpen={ false }
				>
					<p className="tebuto-panel-description">
						{ __(
							'Alle Kategorien werden angezeigt. Nur öffentliche Kategorien können im Widget verwendet werden.',
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
								availableCategories.map( ( cat ) => {
									const isSelectable =
										isCategoryWidgetSelectable( cat );

									return (
										<div
											key={ cat.id }
											className={
												isSelectable
													? 'tebuto-category-item'
													: 'tebuto-category-item tebuto-category-item--unavailable'
											}
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
														{ ! isSelectable && (
															<span className="tebuto-category-unavailable-hint">
																{ __(
																	'Nicht öffentlich',
																	'tebuto-online-terminbuchung'
																) }
															</span>
														) }
													</span>
												}
												checked={
													isSelectable &&
													selectedCategories.includes(
														cat.id
													)
												}
												disabled={ ! isSelectable }
												onChange={ () =>
													toggleCategory( cat.id )
												}
											/>
										</div>
									);
								} )
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
