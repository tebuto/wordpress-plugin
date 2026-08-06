/**
 * Tebuto Admin JavaScript
 *
 * @package Tebuto
 */

/* global tebutoAdmin, jQuery */

;(($) => {
	function init() {
		initBookingActions()
		initModalHandlers()
		initViewBookingDetails()
		initBookingFilters()
	}

	function initBookingActions() {
		$(document).on('click', '.tebuto-confirm-booking', function () {
			const bookingId = $(this).data('booking-id')
			const confirmMsg = tebutoAdmin?.strings?.confirmBooking || 'Buchung bestätigen?'

			if (confirm(confirmMsg)) {
				performBookingAction('confirm', bookingId, $(this))
			}
		})

		$(document).on('click', '.tebuto-reject-booking', function () {
			const bookingId = $(this).data('booking-id')
			const confirmMsg = tebutoAdmin?.strings?.rejectBooking || 'Buchung ablehnen?'

			if (confirm(confirmMsg)) {
				performBookingAction('reject', bookingId, $(this))
			}
		})

		$(document).on('click', '.tebuto-cancel-booking', function () {
			const bookingId = $(this).data('booking-id')
			const confirmMsg = tebutoAdmin?.strings?.cancelBooking || 'Buchung absagen?'

			if (confirm(confirmMsg)) {
				performBookingAction('cancel', bookingId, $(this))
			}
		})
	}

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
			beforeSend() {
				$actions.html(
					`<span class="tebuto-spinner"></span> ${tebutoAdmin?.strings?.processing || 'Wird verarbeitet...'}`
				)
			},
			success(response) {
				if (response.success) {
					showNotice(tebutoAdmin?.strings?.actionSuccess || 'Aktion erfolgreich ausgeführt.', 'success')
					setTimeout(() => {
						window.location.reload()
					}, 800)
				} else {
					showNotice(response.data?.message || tebutoAdmin?.strings?.actionError || 'Fehler', 'error')
					window.location.reload()
				}
			},
			error() {
				showNotice(tebutoAdmin?.strings?.actionError || 'Fehler', 'error')
				window.location.reload()
			}
		})
	}

	function initModalHandlers() {
		$(document).on('click', '.tebuto-modal-close, .tebuto-modal-close-btn', function () {
			$(this).closest('.tebuto-modal').fadeOut(200)
		})

		$(document).on('click', '.tebuto-modal', function (e) {
			if (e.target === this) {
				$(this).fadeOut(200)
			}
		})

		$(document).on('keydown', (e) => {
			if (e.key === 'Escape') {
				$('.tebuto-modal:visible').fadeOut(200)
			}
		})
	}

	function initViewBookingDetails() {
		$(document).on('click', '.tebuto-view-booking', function () {
			const bookingData = $(this).data('booking')
			if (bookingData) {
				showBookingModal(typeof bookingData === 'string' ? JSON.parse(bookingData) : bookingData)
			}
		})
	}

	function initBookingFilters() {
		const $form = $('#tebuto-bookings-filter-form')
		if (!$form.length) {
			return
		}

		$form.on('click', '.tebuto-date-preset', function () {
			const $btn = $(this)
			$form.find('[name="date_from"]').val($btn.data('from'))
			$form.find('[name="date_to"]').val($btn.data('to'))
			$form.find('.tebuto-date-preset').removeClass('active')
			$btn.addClass('active')
			$form.trigger('submit')
		})

		$form.on('change', '.tebuto-status-chip input', function () {
			$form.find('.tebuto-status-chip').removeClass('active')
			$(this).closest('.tebuto-status-chip').addClass('active')
			$form.trigger('submit')
		})

		const dateFrom = $form.find('[name="date_from"]').val()
		const dateTo = $form.find('[name="date_to"]').val()
		if (dateFrom && dateTo) {
			let foundMatch = false
			$form.find('.tebuto-date-preset').each(function () {
				const $btn = $(this)
				if ($btn.data('from') === dateFrom && $btn.data('to') === dateTo) {
					$btn.addClass('active')
					foundMatch = true
				}
			})
			return foundMatch
		}
	}

	function showBookingModal(booking) {
		const $modal = $('#tebuto-booking-modal')
		const $body = $('#tebuto-booking-modal-body')
		const template = document.getElementById('tebuto-booking-details-template')

		if (!$modal.length || !template) {
			return
		}

		const node = template.content.cloneNode(true)
		const strings = tebutoAdmin?.strings || {}

		node.querySelectorAll('[data-label]').forEach((el) => {
			const key = el.getAttribute('data-label')
			const fallbacks = {
				clientInfo: 'Klient',
				appointmentInfo: 'Termin',
				bookingInfo: 'Buchung',
				name: 'Name',
				phone: 'Telefon',
				category: 'Kategorie',
				date: 'Datum',
				time: 'Zeit',
				location: 'Ort',
				bookedOn: 'Gebucht am',
				price: 'Preis'
			}
			const label = strings[key] || fallbacks[key] || key
			if (el.tagName === 'STRONG') {
				el.textContent = `${label}:`
			} else {
				el.textContent = label
			}
		})

		const setField = (name, value) => {
			const el = node.querySelector(`[data-field="${name}"]`)
			if (el) {
				el.textContent = value || ''
			}
		}

		setField('clientName', `${booking.client?.firstName || ''} ${booking.client?.lastName || ''}`.trim())
		setField('clientPhone', booking.client?.phoneNumber || '')

		const emailRow = node.querySelector('[data-field-row="email"]')
		if (emailRow) {
			if (booking.client?.email) {
				setField('clientEmail', booking.client.email)
			} else {
				emailRow.remove()
			}
		}

		setField('category', booking.event?.category?.name || '')

		const startDate = new Date(booking.event.start)
		const endDate = new Date(booking.event.end)
		setField(
			'date',
			startDate.toLocaleDateString('de-DE', {
				weekday: 'long',
				year: 'numeric',
				month: 'long',
				day: 'numeric'
			})
		)
		setField(
			'time',
			`${startDate.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' })} - ${endDate.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' })}`
		)
		setField(
			'location',
			booking.locationSelection === 'virtual' ? strings.online || 'Online' : strings.onsite || 'Vor Ort'
		)

		const createdAt = new Date(booking.createdAt)
		setField('bookedOn', `${createdAt.toLocaleDateString('de-DE')} ${createdAt.toLocaleTimeString('de-DE')}`)
		setField('price', `${Number.parseFloat(booking.price).toFixed(2).replace('.', ',')} €`)

		if (booking.isConfirmed) {
			const badge = document.createElement('p')
			badge.innerHTML = `<span class="tebuto-badge tebuto-badge-success">${strings.confirmed || 'Bestätigt'}</span>`
			node.querySelector('[data-section="booking"]')?.appendChild(badge)
		}

		$body.empty().append(node)
		$modal.fadeIn(200)
	}

	function showNotice(message, type) {
		const $notice = $(`<div class="notice notice-${type} is-dismissible"><p></p></div>`)
		$notice.find('p').text(message)
		$('.tebuto-admin-wrap .tebuto-header').after($notice)

		setTimeout(() => {
			$notice.fadeOut(300, function () {
				$(this).remove()
			})
		}, 3000)
	}

	$(document).ready(init)
})(jQuery)
