/**
 * Tebuto Admin JavaScript
 *
 * @package Tebuto
 */

/* global tebutoAdmin, jQuery */

;(($) => {
	/**
	 * Initialize admin functionality
	 */
	function init() {
		initColorPicker()
		initCopyShortcode()
		initBookingActions()
		initModalHandlers()
		initViewBookingDetails()
		initBookingFilters()
	}

	/**
	 * Initialize WordPress color picker
	 */
	function initColorPicker() {
		if ($.fn.wpColorPicker) {
			$('.tebuto-color-picker').wpColorPicker()
		}
	}

	/**
	 * Initialize copy shortcode functionality
	 */
	function initCopyShortcode() {
		$('.tebuto-copy-shortcode').on('click', function () {
			const $input = $(this).siblings('input')
			$input.select()

			try {
				document.execCommand('copy')
				const $btn = $(this)
				const originalText = $btn.text()
				$btn.text(tebutoAdmin?.strings?.copied || 'Kopiert!')
				setTimeout(() => {
					$btn.text(originalText)
				}, 2000)
			} catch (err) {
				console.error('Copy failed:', err)
			}
		})
	}

	/**
	 * Initialize booking action buttons
	 */
	function initBookingActions() {
		// Confirm booking
		$(document).on('click', '.tebuto-confirm-booking', function () {
			const bookingId = $(this).data('booking-id')
			const confirmMsg = tebutoAdmin?.strings?.confirmBooking || 'Buchung bestätigen?'

			if (confirm(confirmMsg)) {
				performBookingAction('confirm', bookingId, $(this))
			}
		})

		// Reject booking
		$(document).on('click', '.tebuto-reject-booking', function () {
			const bookingId = $(this).data('booking-id')
			const confirmMsg = tebutoAdmin?.strings?.rejectBooking || 'Buchung ablehnen?'

			if (confirm(confirmMsg)) {
				performBookingAction('reject', bookingId, $(this))
			}
		})

		// Cancel booking
		$(document).on('click', '.tebuto-cancel-booking', function () {
			const bookingId = $(this).data('booking-id')
			const confirmMsg = tebutoAdmin?.strings?.cancelBooking || 'Buchung absagen?'

			if (confirm(confirmMsg)) {
				performBookingAction('cancel', bookingId, $(this))
			}
		})
	}

	/**
	 * Perform a booking action via AJAX
	 */
	function performBookingAction(action, bookingId, $button) {
		const $row = $button.closest('.tebuto-booking-item, .tebuto-booking-row')
		const $actions = $row.find('.tebuto-booking-actions')

		$.ajax({
			url: tebutoAdmin.ajaxUrl,
			method: 'POST',
			data: {
				action: 'tebuto_booking_action',
				nonce: tebutoAdmin.nonce,
				booking_action: action,
				booking_id: bookingId
			},
			beforeSend: () => {
				$actions.find('button').prop('disabled', true)
				$button.text(tebutoAdmin?.strings?.processing || 'Wird verarbeitet...')
			},
			success: (response) => {
				if (response.success) {
					showNotice(tebutoAdmin?.strings?.actionSuccess || 'Aktion erfolgreich.', 'success')

					// Reload the page to show updated data
					setTimeout(() => {
						location.reload()
					}, 1000)
				} else {
					showNotice(response.data || tebutoAdmin?.strings?.actionError || 'Fehler bei der Aktion.', 'error')
					$actions.find('button').prop('disabled', false)
					$button.text(getOriginalButtonText(action))
				}
			},
			error: () => {
				showNotice(tebutoAdmin?.strings?.actionError || 'Fehler bei der Aktion.', 'error')
				$actions.find('button').prop('disabled', false)
				$button.text(getOriginalButtonText(action))
			}
		})
	}

	/**
	 * Get original button text based on action
	 */
	function getOriginalButtonText(action) {
		switch (action) {
			case 'confirm':
				return tebutoAdmin?.strings?.confirm || 'Bestätigen'
			case 'reject':
				return tebutoAdmin?.strings?.reject || 'Ablehnen'
			case 'cancel':
				return tebutoAdmin?.strings?.cancel || 'Absagen'
			default:
				return action
		}
	}

	/**
	 * Initialize modal handlers
	 */
	function initModalHandlers() {
		// Close modal on X click
		$(document).on('click', '.tebuto-modal-close, .tebuto-modal-close-btn', function () {
			$(this).closest('.tebuto-modal').fadeOut(200)
		})

		// Close modal on background click
		$(document).on('click', '.tebuto-modal', function (e) {
			if ($(e.target).hasClass('tebuto-modal')) {
				$(this).fadeOut(200)
			}
		})

		// Close modal on escape
		$(document).on('keydown', (e) => {
			if (e.key === 'Escape') {
				$('.tebuto-modal:visible').fadeOut(200)
			}
		})
	}

	/**
	 * Initialize view booking details
	 */
	function initViewBookingDetails() {
		$(document).on('click', '.tebuto-view-booking', function () {
			const booking = $(this).data('booking')
			if (!booking) {
				return
			}

			showBookingModal(booking)
		})
	}

	/**
	 * Initialize booking filter interactions
	 */
	function initBookingFilters() {
		const $form = $('#tebuto-bookings-filter-form')
		if (!$form.length) {
			return
		}

		const $dateFrom = $('#date_from')
		const $dateTo = $('#date_to')

		// Date preset buttons
		$('.tebuto-date-preset').on('click', function () {
			const $btn = $(this)
			const fromDate = $btn.data('from')
			const toDate = $btn.data('to')

			// Update inputs
			$dateFrom.val(fromDate)
			$dateTo.val(toDate)

			// Update active state
			$('.tebuto-date-preset').removeClass('active')
			$btn.addClass('active')
		})

		// Remove preset active state when manually changing dates
		$dateFrom.add($dateTo).on('change', () => {
			updatePresetActiveState()
		})

		// Status chip selection
		$('.tebuto-status-chip').on('click', function () {
			$('.tebuto-status-chip').removeClass('active')
			$(this).addClass('active')
		})

		/**
		 * Update preset button active state based on current date values
		 */
		function updatePresetActiveState() {
			const currentFrom = $dateFrom.val()
			const currentTo = $dateTo.val()
			let foundMatch = false

			$('.tebuto-date-preset').each(function () {
				const $btn = $(this)
				const presetFrom = $btn.data('from')
				const presetTo = $btn.data('to')

				if (currentFrom === presetFrom && currentTo === presetTo) {
					$btn.addClass('active')
					foundMatch = true
				} else {
					$btn.removeClass('active')
				}
			})

			return foundMatch
		}
	}

	/**
	 * Show booking details modal
	 */
	function showBookingModal(booking) {
		const $modal = $('#tebuto-booking-modal')
		const $body = $('#tebuto-booking-modal-body')

		if (!$modal.length) {
			return
		}

		// Build modal content
		let html = '<div class="tebuto-booking-details">'

		// Client info
		html += '<div class="tebuto-detail-section">'
		html += `<h4>${tebutoAdmin?.strings?.clientInfo || 'Klient'}</h4>`
		html +=
			'<p><strong>' +
			(tebutoAdmin?.strings?.name || 'Name') +
			':</strong> ' +
			escapeHtml(`${booking.client.firstName} ${booking.client.lastName}`) +
			'</p>'

		if (booking.client.email) {
			html += `<p><strong>E-Mail:</strong> ${escapeHtml(booking.client.email)}</p>`
		}
		if (booking.client.phoneNumber) {
			html +=
				'<p><strong>' +
				(tebutoAdmin?.strings?.phone || 'Telefon') +
				':</strong> ' +
				escapeHtml(booking.client.phoneNumber) +
				'</p>'
		}
		html += '</div>'

		// Event info
		html += '<div class="tebuto-detail-section">'
		html += `<h4>${tebutoAdmin?.strings?.appointmentInfo || 'Termin'}</h4>`

		if (booking.event.category) {
			html +=
				'<p><strong>' +
				(tebutoAdmin?.strings?.category || 'Kategorie') +
				':</strong> ' +
				escapeHtml(booking.event.category.name) +
				'</p>'
		}

		const startDate = new Date(booking.event.start)
		const endDate = new Date(booking.event.end)
		const dateStr = startDate.toLocaleDateString('de-DE', {
			weekday: 'long',
			year: 'numeric',
			month: 'long',
			day: 'numeric'
		})
		const timeStr =
			startDate.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' }) +
			' - ' +
			endDate.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' })

		html += `<p><strong>${tebutoAdmin?.strings?.date || 'Datum'}:</strong> ${dateStr}</p>`
		html += `<p><strong>${tebutoAdmin?.strings?.time || 'Zeit'}:</strong> ${timeStr}</p>`

		const locationStr =
			booking.locationSelection === 'virtual'
				? tebutoAdmin?.strings?.online || 'Online'
				: tebutoAdmin?.strings?.onsite || 'Vor Ort'
		html += `<p><strong>${tebutoAdmin?.strings?.location || 'Ort'}:</strong> ${locationStr}</p>`

		html += '</div>'

		// Booking info
		html += '<div class="tebuto-detail-section">'
		html += `<h4>${tebutoAdmin?.strings?.bookingInfo || 'Buchung'}</h4>`

		const createdAt = new Date(booking.createdAt)
		html +=
			'<p><strong>' +
			(tebutoAdmin?.strings?.bookedOn || 'Gebucht am') +
			':</strong> ' +
			createdAt.toLocaleDateString('de-DE') +
			' ' +
			createdAt.toLocaleTimeString('de-DE') +
			'</p>'

		html +=
			'<p><strong>' +
			(tebutoAdmin?.strings?.price || 'Preis') +
			':</strong> ' +
			parseFloat(booking.price).toFixed(2).replace('.', ',') +
			' €</p>'

		if (booking.isConfirmed) {
			html +=
				'<p><span class="tebuto-badge tebuto-badge-success">' +
				(tebutoAdmin?.strings?.confirmed || 'Bestätigt') +
				'</span></p>'
		}

		html += '</div>'

		html += '</div>'

		$body.html(html)
		$modal.fadeIn(200)
	}

	/**
	 * Escape HTML entities
	 */
	function escapeHtml(text) {
		if (!text) return ''
		const div = document.createElement('div')
		div.textContent = text
		return div.innerHTML
	}

	/**
	 * Show a temporary notice
	 */
	function showNotice(message, type) {
		const $notice = $(`<div class="notice notice-${type} is-dismissible"><p>${escapeHtml(message)}</p></div>`)
		$('.tebuto-admin-wrap .tebuto-header').after($notice)

		setTimeout(() => {
			$notice.fadeOut(300, function () {
				$(this).remove()
			})
		}, 3000)
	}

	// Initialize on document ready
	$(document).ready(init)
})(jQuery)
