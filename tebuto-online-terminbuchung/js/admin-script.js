/**
 * Tebuto Admin JavaScript
 *
 * @package Tebuto
 */

/* global tebutoAdmin, jQuery */

;(($) => {
	let confirmResolver = null

	function init() {
		ensureConfirmModal()
		initConfirmFormIntercept()
		initBookingActions()
		initModalHandlers()
		initViewBookingDetails()
		initBookingFilters()
	}

	function strings() {
		return tebutoAdmin?.strings || {}
	}

	function ensureConfirmModal() {
		if ($('#tebuto-confirm-modal').length) {
			return
		}

		const s = strings()
		$('body').append(`
			<div id="tebuto-confirm-modal" class="tebuto-modal tebuto-confirm-modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="tebuto-confirm-title">
				<div class="tebuto-modal-content">
					<div class="tebuto-modal-header">
						<h3 id="tebuto-confirm-title"></h3>
						<button type="button" class="tebuto-modal-close" data-tebuto-confirm-cancel aria-label="${escapeHtml(s.close || 'Schließen')}">&times;</button>
					</div>
					<div class="tebuto-modal-body">
						<p id="tebuto-confirm-message" class="tebuto-confirm-message"></p>
						<div id="tebuto-confirm-prompt" class="tebuto-modal-field" hidden>
							<label for="tebuto-confirm-input" id="tebuto-confirm-input-label"></label>
							<textarea id="tebuto-confirm-input" rows="3"></textarea>
						</div>
					</div>
					<div class="tebuto-modal-footer">
						<button type="button" class="button tebuto-btn tebuto-btn--outline tebuto-btn--neutral" data-tebuto-confirm-cancel>
							${escapeHtml(s.cancelDialog || 'Abbrechen')}
						</button>
						<button type="button" class="button button-primary tebuto-btn tebuto-btn--solid" id="tebuto-confirm-ok"></button>
					</div>
				</div>
			</div>
		`)
	}

	function escapeHtml(value) {
		return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')
	}

	/**
	 * Show a confirm dialog. Resolves to { confirmed, value }.
	 *
	 * @param {object} options
	 * @param {string} [options.title]
	 * @param {string} options.message
	 * @param {string} [options.confirmLabel]
	 * @param {string} [options.cancelLabel]
	 * @param {boolean} [options.danger]
	 * @param {object} [options.prompt] Optional text input: { label, placeholder, value }
	 * @returns {Promise<{confirmed: boolean, value: string}>}
	 */
	function tebutoConfirm(options) {
		ensureConfirmModal()

		const opts = options || {}
		const s = strings()
		const $modal = $('#tebuto-confirm-modal')
		const $ok = $('#tebuto-confirm-ok')
		const $prompt = $('#tebuto-confirm-prompt')
		const $input = $('#tebuto-confirm-input')
		const danger = Boolean(opts.danger)

		if (confirmResolver) {
			confirmResolver({ confirmed: false, value: '' })
			confirmResolver = null
		}

		$('#tebuto-confirm-title').text(opts.title || s.confirmTitle || 'Bitte bestätigen')
		$('#tebuto-confirm-message').text(opts.message || '')

		$ok
			.text(opts.confirmLabel || s.confirm || 'Bestätigen')
			.removeClass('tebuto-btn--danger tebuto-btn--primary tebuto-btn--neutral button-primary')
			.addClass(danger ? 'tebuto-btn--danger' : 'tebuto-btn--primary button-primary')

		$modal
			.find('[data-tebuto-confirm-cancel]')
			.filter('button.tebuto-btn')
			.text(opts.cancelLabel || s.cancelDialog || 'Abbrechen')

		if (opts.prompt) {
			$prompt.prop('hidden', false)
			$('#tebuto-confirm-input-label').text(opts.prompt.label || '')
			$input.attr('placeholder', opts.prompt.placeholder || '').val(opts.prompt.value || '')
		} else {
			$prompt.prop('hidden', true)
			$input.val('')
		}

		return new Promise((resolve) => {
			confirmResolver = resolve
			$('body').addClass('tebuto-modal-open')
			$modal.fadeIn(200, () => {
				if (opts.prompt) {
					$input.trigger('focus')
				} else {
					$ok.trigger('focus')
				}
			})
		})
	}

	function closeConfirmModal(result) {
		const $modal = $('#tebuto-confirm-modal')
		const resolver = confirmResolver
		confirmResolver = null

		$modal.fadeOut(200, () => {
			if ($('.tebuto-modal:visible').length === 0) {
				$('body').removeClass('tebuto-modal-open')
			}
		})

		if (resolver) {
			resolver(result)
		}
	}

	function acceptConfirm() {
		const value = $('#tebuto-confirm-prompt').prop('hidden') ? '' : String($('#tebuto-confirm-input').val() || '')
		closeConfirmModal({ confirmed: true, value })
	}

	function cancelConfirm() {
		closeConfirmModal({ confirmed: false, value: '' })
	}

	function initConfirmFormIntercept() {
		$(document).on('submit', 'form[data-tebuto-confirm]', function (event) {
			if (this.getAttribute('data-tebuto-confirmed') === '1') {
				return
			}

			event.preventDefault()
			event.stopImmediatePropagation()

			tebutoConfirm({
				title: this.getAttribute('data-tebuto-confirm-title') || strings().confirmTitle || 'Bitte bestätigen',
				message: this.getAttribute('data-tebuto-confirm') || '',
				confirmLabel: this.getAttribute('data-tebuto-confirm-label') || strings().deleteLabel || 'Löschen',
				danger: this.getAttribute('data-tebuto-confirm-danger') !== '0'
			}).then((result) => {
				if (!result.confirmed) {
					return
				}
				this.setAttribute('data-tebuto-confirmed', '1')
				this.submit()
			})
		})

		$(document).on('click', '#tebuto-confirm-ok', (event) => {
			event.preventDefault()
			if ($('#tebuto-confirm-modal').is(':visible')) {
				acceptConfirm()
			}
		})

		$(document).on('click', '#tebuto-confirm-modal [data-tebuto-confirm-cancel]', (event) => {
			event.preventDefault()
			cancelConfirm()
		})
	}

	function initBookingActions() {
		$(document).on('click', '.tebuto-confirm-booking', function () {
			const $btn = $(this)
			const bookingId = $btn.data('booking-id')
			const s = strings()

			tebutoConfirm({
				title: s.confirmTitle || 'Bitte bestätigen',
				message: s.confirmBooking || 'Buchung bestätigen?',
				confirmLabel: s.confirm || 'Bestätigen',
				danger: false
			}).then((result) => {
				if (result.confirmed) {
					performBookingAction('confirm', bookingId, $btn)
				}
			})
		})

		$(document).on('click', '.tebuto-reject-booking', function () {
			const $btn = $(this)
			const bookingId = $btn.data('booking-id')
			const s = strings()

			tebutoConfirm({
				title: s.confirmTitle || 'Bitte bestätigen',
				message: s.rejectBooking || 'Buchung ablehnen?',
				confirmLabel: s.reject || 'Ablehnen',
				danger: true
			}).then((result) => {
				if (result.confirmed) {
					performBookingAction('reject', bookingId, $btn)
				}
			})
		})

		$(document).on('click', '.tebuto-cancel-booking', function () {
			const $btn = $(this)
			const bookingId = $btn.data('booking-id')
			const s = strings()

			tebutoConfirm({
				title: s.confirmTitle || 'Bitte bestätigen',
				message: s.cancelBooking || 'Buchung absagen?',
				confirmLabel: s.cancel || 'Absagen',
				danger: true
			}).then((result) => {
				if (result.confirmed) {
					performBookingAction('cancel', bookingId, $btn)
				}
			})
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
		$(document).on('click', '.tebuto-modal-close, .tebuto-modal-close-btn', function (event) {
			const $modal = $(this).closest('.tebuto-modal')
			if ($modal.is('#tebuto-confirm-modal')) {
				event.preventDefault()
				cancelConfirm()
				return
			}
			$modal.fadeOut(200, () => {
				if ($('.tebuto-modal:visible').length === 0) {
					$('body').removeClass('tebuto-modal-open')
				}
			})
		})

		$(document).on('click', '.tebuto-modal', function (e) {
			if (e.target !== this) {
				return
			}
			if (this.id === 'tebuto-confirm-modal') {
				cancelConfirm()
				return
			}
			$(this).fadeOut(200, () => {
				if ($('.tebuto-modal:visible').length === 0) {
					$('body').removeClass('tebuto-modal-open')
				}
			})
		})

		$(document).on('keydown', (e) => {
			if (e.key !== 'Escape') {
				return
			}
			if ($('#tebuto-confirm-modal').is(':visible')) {
				cancelConfirm()
				return
			}
			$('.tebuto-modal:visible').fadeOut(200, () => {
				if ($('.tebuto-modal:visible').length === 0) {
					$('body').removeClass('tebuto-modal-open')
				}
			})
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
		const s = strings()

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
			const label = s[key] || fallbacks[key] || key
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
		setField('location', booking.locationSelection === 'virtual' ? s.online || 'Online' : s.onsite || 'Vor Ort')

		const createdAt = new Date(booking.createdAt)
		setField('bookedOn', `${createdAt.toLocaleDateString('de-DE')} ${createdAt.toLocaleTimeString('de-DE')}`)
		setField('price', `${Number.parseFloat(booking.price).toFixed(2).replace('.', ',')} €`)

		if (booking.isConfirmed) {
			const badge = document.createElement('p')
			badge.innerHTML = `<span class="tebuto-badge tebuto-badge-success">${s.confirmed || 'Bestätigt'}</span>`
			node.querySelector('[data-section="booking"]')?.appendChild(badge)
		}

		$body.empty().append(node)
		$('body').addClass('tebuto-modal-open')
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

	window.tebutoConfirm = tebutoConfirm

	$(document).ready(init)
})(jQuery)
