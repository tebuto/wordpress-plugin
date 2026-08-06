import { InspectorControls, useBlockProps } from '@wordpress/block-editor'
import { Button, PanelBody, TextareaControl } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import { useEffect, useMemo, useRef, useState } from 'react'
import { buildShortcode } from './attributeMap'
import CategoryPicker from './components/CategoryPicker'
import ColorSettings from './components/ColorSettings'
import ConnectionPlaceholder from './components/ConnectionPlaceholder'
import CustomCssPanel from './components/CustomCssPanel'
import DisplayOptions from './components/DisplayOptions'
import SeminarPicker from './components/SeminarPicker'
import ThemePresetPicker from './components/ThemePresetPicker'
import { getTebutoData } from './theme'
import useCategories from './useCategories'
import useSeminars from './useSeminars'
import useWidgetPreview from './useWidgetPreview'

export function isCategoryWidgetSelectable(category) {
	return Boolean(category.widgetSelectable ?? category.publicBookingEnabled)
}

function applyPreset(setAttributes, preset) {
	setAttributes({
		primaryColor: preset.primaryColor,
		backgroundColor: preset.backgroundColor,
		textPrimary: preset.textPrimary,
		textSecondary: preset.textSecondary,
		borderColor: preset.borderColor
	})
}

function useBookingCategorySync(attributes, setAttributes, availableCategories) {
	const { categories, configuredCategoriesJson, showProviderFilter } = attributes

	const selectedCategories = useMemo(
		() => (categories ? categories.split(',').map((id) => Number.parseInt(id.trim(), 10)) : []),
		[categories]
	)

	const selectedAvailableCategories = useMemo(
		() =>
			availableCategories.filter(
				(category) => selectedCategories.includes(category.id) && isCategoryWidgetSelectable(category)
			),
		[availableCategories, selectedCategories]
	)

	const hasSubaccountCategoriesSelected = selectedAvailableCategories.some((category) => category.isFromSubaccount)
	const shouldUseConfiguredCategories = showProviderFilter || hasSubaccountCategoriesSelected

	useEffect(() => {
		if (!shouldUseConfiguredCategories || selectedAvailableCategories.length === 0) {
			if (configuredCategoriesJson) {
				setAttributes({ configuredCategoriesJson: '' })
			}
			return
		}

		const nextCategoriesJson = JSON.stringify(
			selectedAvailableCategories.map((category) => ({
				id: category.id,
				name: category.name,
				color: category.color,
				isFromSubaccount: Boolean(category.isFromSubaccount),
				therapistId: category.therapistId ?? 0,
				therapistName: category.therapistName ?? ''
			}))
		)
		if (configuredCategoriesJson !== nextCategoriesJson) {
			setAttributes({
				configuredCategoriesJson: nextCategoriesJson
			})
		}
	}, [selectedAvailableCategories, shouldUseConfiguredCategories, configuredCategoriesJson, setAttributes])

	useEffect(() => {
		if (availableCategories.length > 0 && (!categories || categories.trim() === '')) {
			setAttributes({
				categories: availableCategories
					.filter((category) => isCategoryWidgetSelectable(category))
					.map((category) => category.id)
					.join(',')
			})
		}
	}, [availableCategories, categories, setAttributes])

	const toggleCategory = (categoryId) => {
		const category = availableCategories.find((entry) => entry.id === categoryId)
		if (category && !isCategoryWidgetSelectable(category)) {
			return
		}

		const newSelected = selectedCategories.includes(categoryId)
			? selectedCategories.filter((id) => id !== categoryId)
			: [...selectedCategories, categoryId]

		setAttributes({
			categories: newSelected.join(',')
		})
	}

	return {
		selectedCategories,
		selectedAvailableCategories,
		hasSubaccountCategoriesSelected,
		shouldUseConfiguredCategories,
		toggleCategory
	}
}

function useSeminarSync(attributes, setAttributes) {
	const { seminars } = attributes

	const selectedSeminars = useMemo(
		() =>
			seminars
				? seminars
						.split(',')
						.map((slug) => slug.trim())
						.filter(Boolean)
				: [],
		[seminars]
	)

	const toggleSeminar = (slug) => {
		if (!slug) {
			return
		}

		const newSelected = selectedSeminars.includes(slug)
			? selectedSeminars.filter((entry) => entry !== slug)
			: [...selectedSeminars, slug]

		setAttributes({
			seminars: newSelected.join(',')
		})
	}

	return {
		selectedSeminars,
		toggleSeminar
	}
}

function ConfiguratorPanels({
	variant,
	attributes,
	setAttributes,
	availableCategories,
	loadingCategories,
	categoriesError,
	selectedCategories,
	selectedAvailableCategories,
	hasSubaccountCategoriesSelected,
	toggleCategory,
	availableSeminars,
	loadingSeminars,
	seminarsError,
	selectedSeminars,
	toggleSeminar
}) {
	const { showCategorySelectionFirst, showListFirst, customCss } = attributes

	return (
		<>
			{variant === 'booking' ? (
				<PanelBody title={__('Kategorien', 'tebuto-online-terminbuchung')} initialOpen={true}>
					<CategoryPicker
						categories={availableCategories}
						selected={selectedCategories}
						onToggle={toggleCategory}
						loading={loadingCategories}
						error={categoriesError}
						showCategorySelectionFirst={showCategorySelectionFirst}
						onShowCategorySelectionFirstChange={(value) =>
							setAttributes({
								showCategorySelectionFirst: value
							})
						}
						selectableCount={selectedAvailableCategories.length}
					/>
				</PanelBody>
			) : (
				<PanelBody title={__('Seminare', 'tebuto-online-terminbuchung')} initialOpen={true}>
					<SeminarPicker
						seminars={availableSeminars}
						selected={selectedSeminars}
						onToggle={toggleSeminar}
						loading={loadingSeminars}
						error={seminarsError}
						showListFirst={showListFirst}
						onShowListFirstChange={(value) => setAttributes({ showListFirst: value })}
					/>
				</PanelBody>
			)}

			<DisplayOptions
				variant={variant}
				attributes={attributes}
				setAttributes={setAttributes}
				hasSubaccountCategoriesSelected={hasSubaccountCategoriesSelected}
			/>

			<PanelBody title={__('Farbvorlagen', 'tebuto-online-terminbuchung')} initialOpen={false}>
				<ThemePresetPicker onSelect={(preset) => applyPreset(setAttributes, preset)} />
			</PanelBody>

			<ColorSettings attributes={attributes} setAttributes={setAttributes} />

			<CustomCssPanel variant={variant} value={customCss} onChange={(value) => setAttributes({ customCss: value })} />
		</>
	)
}

function InspectorConfigurator({ variant, attributes, setAttributes }) {
	const previewContainerRef = useRef(null)
	const data = getTebutoData()
	const therapistUuid = data.uuid || ''
	const authState = data.authState || 'disconnected'
	const widgetUrl =
		variant === 'seminars' ? data.seminarsWidgetUrl || 'https://tebuto.de/widget/seminars.js' : data.widgetUrl || ''

	const { categories: availableCategories, loading, error, sessionExpired } = useCategories()
	const {
		seminars: availableSeminars,
		loading: loadingSeminars,
		error: seminarsError,
		sessionExpired: seminarsSessionExpired
	} = useSeminars()
	const isSessionExpired = sessionExpired || seminarsSessionExpired || authState === 'expired'
	const isDisconnected = !therapistUuid || authState === 'disconnected'

	const bookingSync = useBookingCategorySync(
		attributes,
		setAttributes,
		variant === 'booking' ? availableCategories : []
	)
	const seminarSync = useSeminarSync(attributes, setAttributes)

	useWidgetPreview(previewContainerRef, {
		variant,
		therapistUuid,
		widgetUrl,
		attributes,
		selectedCategories: bookingSync.selectedAvailableCategories,
		shouldUseConfiguredCategories: bookingSync.shouldUseConfiguredCategories
	})

	const blockProps = useBlockProps({
		className: 'tebuto-block-editor'
	})

	if (isSessionExpired || isDisconnected) {
		return (
			<div {...blockProps}>
				<ConnectionPlaceholder expired={isSessionExpired} />
			</div>
		)
	}

	return (
		<>
			<InspectorControls>
				<ConfiguratorPanels
					variant={variant}
					attributes={attributes}
					setAttributes={setAttributes}
					availableCategories={availableCategories}
					loadingCategories={loading}
					categoriesError={error}
					selectedCategories={bookingSync.selectedCategories}
					selectedAvailableCategories={bookingSync.selectedAvailableCategories}
					hasSubaccountCategoriesSelected={bookingSync.hasSubaccountCategoriesSelected}
					toggleCategory={bookingSync.toggleCategory}
					availableSeminars={availableSeminars}
					loadingSeminars={loadingSeminars}
					seminarsError={seminarsError}
					selectedSeminars={seminarSync.selectedSeminars}
					toggleSeminar={seminarSync.toggleSeminar}
				/>
			</InspectorControls>

			<div {...blockProps}>
				<div className="tebuto-block-preview" ref={previewContainerRef}>
					<div id={variant === 'seminars' ? 'tebuto-seminars-widget' : 'tebuto-booking-widget'} />
				</div>
			</div>
		</>
	)
}

function VariantTabs({ variant, onVariantChange }) {
	if (typeof onVariantChange !== 'function') {
		return null
	}

	return (
		<div
			className="tebuto-widget-variant-tabs"
			role="tablist"
			aria-label={__('Widget-Typ', 'tebuto-online-terminbuchung')}
		>
			<button
				type="button"
				role="tab"
				aria-selected={variant === 'booking'}
				className={
					variant === 'booking'
						? 'tebuto-widget-variant-tab tebuto-widget-variant-tab--active'
						: 'tebuto-widget-variant-tab'
				}
				onClick={() => onVariantChange('booking')}
			>
				{__('Termine', 'tebuto-online-terminbuchung')}
			</button>
			<button
				type="button"
				role="tab"
				aria-selected={variant === 'seminars'}
				className={
					variant === 'seminars'
						? 'tebuto-widget-variant-tab tebuto-widget-variant-tab--active'
						: 'tebuto-widget-variant-tab'
				}
				onClick={() => onVariantChange('seminars')}
			>
				{__('Seminare', 'tebuto-online-terminbuchung')}
			</button>
		</div>
	)
}

function AdminConfigurator({ variant, attributes, setAttributes, onVariantChange }) {
	const previewContainerRef = useRef(null)
	const [copyLabel, setCopyLabel] = useState(null)
	const data = getTebutoData()
	const therapistUuid = data.uuid || ''
	const authState = data.authState || 'disconnected'
	const widgetUrl =
		variant === 'seminars' ? data.seminarsWidgetUrl || 'https://tebuto.de/widget/seminars.js' : data.widgetUrl || ''

	const { categories: availableCategories, loading, error, sessionExpired } = useCategories()
	const {
		seminars: availableSeminars,
		loading: loadingSeminars,
		error: seminarsError,
		sessionExpired: seminarsSessionExpired
	} = useSeminars()
	const isSessionExpired = sessionExpired || seminarsSessionExpired || authState === 'expired'
	const isDisconnected = !therapistUuid || authState === 'disconnected'

	const bookingSync = useBookingCategorySync(
		attributes,
		setAttributes,
		variant === 'booking' ? availableCategories : []
	)
	const seminarSync = useSeminarSync(attributes, setAttributes)

	useWidgetPreview(previewContainerRef, {
		variant,
		therapistUuid,
		widgetUrl,
		attributes,
		selectedCategories: bookingSync.selectedAvailableCategories,
		shouldUseConfiguredCategories: bookingSync.shouldUseConfiguredCategories
	})

	const shortcodeTag = variant === 'seminars' ? 'tebuto_seminare_widget' : 'tebuto_online_terminbuchung_widget'
	const shortcode = buildShortcode(attributes, shortcodeTag)

	const copyShortcode = async () => {
		try {
			await navigator.clipboard.writeText(shortcode)
			setCopyLabel(__('Kopiert!', 'tebuto-online-terminbuchung'))
			setTimeout(() => setCopyLabel(null), 2000)
		} catch {
			setCopyLabel(null)
		}
	}

	if (isSessionExpired || isDisconnected) {
		return <ConnectionPlaceholder expired={isSessionExpired} />
	}

	return (
		<div className="tebuto-widget-settings-layout">
			<div className="tebuto-widget-settings-controls">
				<VariantTabs variant={variant} onVariantChange={onVariantChange} />

				<div className="tebuto-widget-settings-controls-scroll">
					<ConfiguratorPanels
						variant={variant}
						attributes={attributes}
						setAttributes={setAttributes}
						availableCategories={availableCategories}
						loadingCategories={loading}
						categoriesError={error}
						selectedCategories={bookingSync.selectedCategories}
						selectedAvailableCategories={bookingSync.selectedAvailableCategories}
						hasSubaccountCategoriesSelected={bookingSync.hasSubaccountCategoriesSelected}
						toggleCategory={bookingSync.toggleCategory}
						availableSeminars={availableSeminars}
						loadingSeminars={loadingSeminars}
						seminarsError={seminarsError}
						selectedSeminars={seminarSync.selectedSeminars}
						toggleSeminar={seminarSync.toggleSeminar}
					/>

					<PanelBody title={__('Shortcode', 'tebuto-online-terminbuchung')} initialOpen={true}>
						<TextareaControl
							label={__('Shortcode kopieren', 'tebuto-online-terminbuchung')}
							value={shortcode}
							readOnly
							rows={3}
							className="tebuto-css-textarea"
						/>
						<Button variant="primary" onClick={copyShortcode}>
							{copyLabel || __('Shortcode kopieren', 'tebuto-online-terminbuchung')}
						</Button>
					</PanelBody>
				</div>
			</div>

			<div className="tebuto-widget-settings-preview">
				<div className="tebuto-block-preview" ref={previewContainerRef}>
					<div id={variant === 'seminars' ? 'tebuto-seminars-widget' : 'tebuto-booking-widget'} />
				</div>
			</div>
		</div>
	)
}

/**
 * Shared widget configurator for Gutenberg inspector and admin shortcode page.
 *
 * @param {{
 *   variant: 'booking'|'seminars',
 *   surface: 'inspector'|'admin',
 *   attributes: Record<string, unknown>,
 *   setAttributes: (attrs: Record<string, unknown>) => void,
 *   onVariantChange?: (variant: 'booking'|'seminars') => void,
 * }} props
 */
export default function WidgetConfigurator({ variant, surface, attributes, setAttributes, onVariantChange }) {
	if (surface === 'admin') {
		return (
			<AdminConfigurator
				variant={variant}
				attributes={attributes}
				setAttributes={setAttributes}
				onVariantChange={onVariantChange}
			/>
		)
	}

	return <InspectorConfigurator variant={variant} attributes={attributes} setAttributes={setAttributes} />
}
