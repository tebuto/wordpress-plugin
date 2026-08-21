/**
 * Tebuto seminars admin interactions.
 *
 * @package Tebuto
 */
;(($) => {
	const admin = window.tebutoAdmin || {}
	const strings = admin.strings || {}

	const PAST_PAGE_SIZE = 5
	const TIME_ZONE = 'Europe/Berlin'

	function lifecycleLabel(status) {
		const map = strings.lifecycleLabels || {}
		return map[status] || status
	}

	function lifecycleTone(status) {
		const map = {
			draft: 'default',
			published: 'info',
			registration_pending: 'warning',
			registration_open: 'success',
			registration_closed: 'warning',
			running: 'primary',
			completed: 'default',
			cancelled: 'danger'
		}
		return map[status] || 'default'
	}

	function isActiveLifecycle(lifecycle) {
		return lifecycle !== 'completed' && lifecycle !== 'cancelled'
	}

	function monthAndYear(date) {
		return {
			month: new Intl.DateTimeFormat('de-DE', { month: 'long', timeZone: TIME_ZONE }).format(date),
			year: new Intl.DateTimeFormat('de-DE', { year: 'numeric', timeZone: TIME_ZONE }).format(date)
		}
	}

	function formatSessionMonthRange(sessions) {
		if (!sessions?.length) {
			return ''
		}

		const sorted = sessions
			.map((session) => new Date(session.start))
			.filter((date) => !Number.isNaN(date.getTime()))
			.sort((a, b) => a.getTime() - b.getTime())

		if (!sorted.length) {
			return ''
		}

		const start = monthAndYear(sorted[0])
		const end = monthAndYear(sorted[sorted.length - 1])

		if (start.month === end.month && start.year === end.year) {
			return `${start.month} ${start.year}`
		}
		if (start.year === end.year) {
			return `${start.month} - ${end.month} ${start.year}`
		}
		return `${start.month} ${start.year} - ${end.month} ${end.year}`
	}

	function occurrenceTitle(occurrence) {
		const label = (occurrence.label || '').trim()
		if (label) {
			return label
		}
		return formatSessionMonthRange(occurrence.sessions || []) || strings.occurrenceFallback || 'Termin'
	}

	function detailUrl(seminarId, occurrenceId) {
		const base = admin.seminarsPageUrl || ''
		const sep = base.includes('?') ? '&' : '?'
		return `${base + sep}seminar_id=${encodeURIComponent(seminarId)}&occurrence_id=${encodeURIComponent(occurrenceId)}`
	}

	function splitOccurrences(occurrences) {
		const active = []
		const past = []
		occurrences.forEach((occ) => {
			const lifecycle = occ.lifecycleStatus || occ.status || ''
			if (isActiveLifecycle(lifecycle)) {
				active.push(occ)
			} else {
				past.push(occ)
			}
		})
		past.sort((left, right) => {
			const leftStart = (left.sessions || [])[0]?.start || ''
			const rightStart = (right.sessions || [])[0]?.start || ''
			return rightStart.localeCompare(leftStart)
		})
		return { active, past }
	}

	function renderOccurrenceRows(seminarId, occurrences, isInherited, { reorderable }) {
		let rows = ''
		occurrences.forEach((occ, index) => {
			const id = occ.id
			const booked = occ.bookedSeats || 0
			const capacity = occ.capacity || 0
			const lifecycle = occ.lifecycleStatus || occ.status || ''
			const upDisabled = index === 0 ? 'disabled' : ''
			const downDisabled = index === occurrences.length - 1 ? 'disabled' : ''
			rows +=
				'<tr data-occurrence-id="' +
				id +
				'">' +
				'<td><a href="' +
				detailUrl(seminarId, id) +
				'">' +
				$('<div>').text(occurrenceTitle(occ)).html() +
				'</a></td>' +
				'<td>' +
				booked +
				' / ' +
				capacity +
				'</td>' +
				'<td><span class="tebuto-badge tebuto-badge-' +
				lifecycleTone(lifecycle) +
				'">' +
				$('<div>').text(lifecycleLabel(lifecycle)).html() +
				'</span></td>' +
				'<td class="tebuto-actions-col">' +
				'<div class="tebuto-action-buttons">' +
				(reorderable && !isInherited
					? '<button type="button" class="button button-small tebuto-btn tebuto-btn--outline tebuto-btn--neutral tebuto-btn--sm tebuto-move-occurrence" data-dir="up" ' +
						upDisabled +
						' title="' +
						(strings.moveUp || 'Nach oben') +
						'"><span class="dashicons dashicons-arrow-up-alt2"></span></button>' +
						'<button type="button" class="button button-small tebuto-btn tebuto-btn--outline tebuto-btn--neutral tebuto-btn--sm tebuto-move-occurrence" data-dir="down" ' +
						downDisabled +
						' title="' +
						(strings.moveDown || 'Nach unten') +
						'"><span class="dashicons dashicons-arrow-down-alt2"></span></button>'
					: '') +
				'<a class="button button-small tebuto-btn tebuto-btn--outline tebuto-btn--neutral tebuto-btn--sm" href="' +
				detailUrl(seminarId, id) +
				'">' +
				(strings.details || 'Details') +
				'</a>' +
				'</div></td></tr>'
		})
		return rows
	}

	function renderOccurrencesTable(seminarId, occurrences, isInherited, { reorderable, tableClass }) {
		if (!occurrences.length) {
			return ''
		}

		return (
			'<div class="tebuto-table-responsive"><table class="tebuto-table tebuto-occurrences-table ' +
			(tableClass || '') +
			'"><thead><tr>' +
			'<th>' +
			(strings.occurrence || 'Veranstaltung') +
			'</th><th>' +
			(strings.seats || 'Plätze') +
			'</th><th>' +
			(strings.status || 'Status') +
			'</th><th class="tebuto-actions-col">' +
			(strings.actions || 'Aktionen') +
			'</th></tr></thead><tbody>' +
			renderOccurrenceRows(seminarId, occurrences, isInherited, { reorderable }) +
			'</tbody></table></div>'
		)
	}

	function renderPastSection(seminarId, past, isInherited, visibleCount) {
		if (!past.length) {
			return ''
		}

		const visible = past.slice(0, visibleCount)
		const hasMore = visibleCount < past.length

		return (
			'<div class="tebuto-past-occurrences" data-past-visible="' +
			visibleCount +
			'">' +
			'<button type="button" class="tebuto-past-toggle" aria-expanded="false">' +
			'<span class="dashicons dashicons-arrow-right-alt2 tebuto-past-chevron"></span>' +
			'<span class="tebuto-past-toggle-label">' +
			(strings.pastOccurrences || 'Vergangene Veranstaltungen') +
			' <span class="tebuto-past-count">(' +
			past.length +
			')</span></span>' +
			'</button>' +
			'<div class="tebuto-past-body" hidden>' +
			renderOccurrencesTable(seminarId, visible, isInherited, {
				reorderable: false,
				tableClass: 'tebuto-occurrences-table--past'
			}) +
			(hasMore
				? '<div class="tebuto-past-footer"><button type="button" class="button-link tebuto-past-show-more">' +
					(strings.showMore || 'Mehr anzeigen') +
					'</button></div>'
				: '') +
			'</div></div>'
		)
	}

	function renderOccurrences(seminarId, occurrences, isInherited, pastVisibleCount) {
		if (!occurrences.length) {
			return (
				'<div class="tebuto-accordion-empty">' +
				'<p>' +
				(strings.noOccurrences || 'Noch keine Veranstaltungen.') +
				'</p>' +
				'</div>'
			)
		}

		const { active, past } = splitOccurrences(occurrences)
		const visiblePast = typeof pastVisibleCount === 'number' ? pastVisibleCount : PAST_PAGE_SIZE

		let html = ''
		if (active.length) {
			html += renderOccurrencesTable(seminarId, active, isInherited, {
				reorderable: true,
				tableClass: 'tebuto-occurrences-table--active'
			})
		} else if (!past.length) {
			html += `<p class="tebuto-empty">${strings.noOccurrences || 'Noch keine Veranstaltungen.'}</p>`
		}

		html += renderPastSection(seminarId, past, isInherited, visiblePast)
		return html
	}

	function loadOccurrences($item) {
		const seminarId = $item.data('seminar-id')
		const $body = $item.find('.tebuto-accordion-body')
		const $loading = $body.find('.tebuto-accordion-loading')
		const $content = $body.find('.tebuto-accordion-content')

		if ($item.data('loaded')) {
			return
		}

		$loading.show()
		$content.empty()

		$.ajax({
			url: admin.ajaxUrl,
			method: 'POST',
			data: {
				action: 'tebuto_get_seminar_occurrences',
				nonce: admin.nonce,
				seminar_id: seminarId
			}
		})
			.done((response) => {
				if (!response?.success) {
					const message = response?.data?.message || response.data || strings.loadError || 'Fehler beim Laden.'
					$content.html(`<p class="tebuto-error">${$('<div>').text(message).html()}</p>`)
					return
				}

				const occurrences = response.data || []
				$item.data('occurrences', occurrences)
				$item.data('past-visible', PAST_PAGE_SIZE)
				const isInherited = String($item.data('inherited')) === '1'
				$content.html(renderOccurrences(seminarId, occurrences, isInherited, PAST_PAGE_SIZE))
				$item.data('loaded', true)
			})
			.fail((xhr) => {
				const payload = xhr.responseJSON
				if (payload?.data?.code === 'session_expired') {
					window.location.reload()
					return
				}
				$content.html(
					'<p class="tebuto-error">' +
						$('<div>')
							.text(strings.loadError || 'Fehler beim Laden.')
							.html() +
						'</p>'
				)
			})
			.always(() => {
				$loading.hide()
			})
	}

	function openModal($modal) {
		$modal.show()
		$('body').addClass('tebuto-modal-open')
		refreshSeminarEditor()
	}

	function closeModal($modal) {
		$modal.hide()
		if ($('.tebuto-modal:visible').length === 0) {
			$('body').removeClass('tebuto-modal-open')
		}
	}

	function refreshSeminarEditor() {
		const editorId = 'seminar_description'
		window.setTimeout(() => {
			if (window.tinymce && tinymce.get(editorId)) {
				const editor = tinymce.get(editorId)
				editor.show()
				editor.fire('ResizeEditor')
				if (typeof editor.execCommand === 'function') {
					editor.execCommand('mceAutoResize')
				}
			}
			if (window.wp?.editor?.wpautop && $(`#${editorId}`).length) {
				$(window).trigger('resize')
			}
		}, 50)
	}

	function setEditorContent(content) {
		const editorId = 'seminar_description'
		if (window.tinymce && tinymce.get(editorId)) {
			tinymce.get(editorId).setContent(content || '')
		}
		$(`#${editorId}`).val(content || '')
	}

	function getEditorContent() {
		const editorId = 'seminar_description'
		if (window.tinymce && tinymce.get(editorId)) {
			return tinymce.get(editorId).getContent()
		}
		return $(`#${editorId}`).val() || ''
	}

	function setOccurrenceFieldsetEnabled(enabled) {
		const $fieldset = $('#tebuto-seminar-occurrence-fieldset')
		if (enabled) {
			$fieldset.show()
			$fieldset.find('input, select, textarea').prop('disabled', false)
			$('#occurrence_capacity, #occurrence_location_type').prop('required', true)
		} else {
			$fieldset.hide()
			$fieldset.find('input, select, textarea').prop('disabled', true).prop('required', false)
		}
	}

	function resetSeminarForm() {
		const $form = $('#tebuto-seminar-form')
		$form[0].reset()
		$('#tebuto-seminar-action').val('create_seminar')
		$('#tebuto-seminar-id').val('')
		$('#tebuto-seminar-modal-title').text(strings.newSeminar || 'Neues Seminar')
		$('#tebuto-seminar-submit').text(strings.createSeminar || 'Seminar erstellen')
		setOccurrenceFieldsetEnabled(true)
		$('#tebuto-seminar-banner-preview').attr('hidden', true).find('img').attr('src', '')
		setEditorContent('')
		toggleAddressFields($('#occurrence_location_type').val(), $('#tebuto-occurrence-address-fields'))
	}

	function fillSeminarForm(seminar) {
		$('#tebuto-seminar-action').val('update_seminar')
		$('#tebuto-seminar-id').val(seminar.id || '')
		$('#seminar_title').val(seminar.title || '')
		$('#seminar_subtitle').val(seminar.subtitle || '')
		$('#seminar_topic').val(seminar.topic || '')
		$('#seminar_price').val(seminar.price || '0')
		$('#seminar_tax_rate').val(seminar.taxRate || '')
		$('#tebuto-seminar-modal-title').text(strings.editSeminar || 'Seminar bearbeiten')
		$('#tebuto-seminar-submit').text(strings.saveSeminar || 'Seminar speichern')
		setOccurrenceFieldsetEnabled(false)
		setEditorContent(seminar.description || '')

		if (seminar.bannerUrl) {
			$('#tebuto-seminar-banner-preview').removeAttr('hidden').find('img').attr('src', seminar.bannerUrl)
		} else {
			$('#tebuto-seminar-banner-preview').attr('hidden', true).find('img').attr('src', '')
		}
	}

	function toggleAddressFields(locationType, $container) {
		if (locationType === 'onsite') {
			$container.removeAttr('hidden')
		} else {
			$container.attr('hidden', true)
		}
	}

	function occurrenceAction(action, data) {
		return $.ajax({
			url: admin.ajaxUrl,
			method: 'POST',
			data: $.extend(
				{
					action: 'tebuto_seminar_occurrence_action',
					nonce: admin.nonce,
					occurrence_action: action
				},
				data
			)
		})
	}

	function showNotice(message, type) {
		const $notice = $(
			'<div class="notice notice-' +
				(type || 'success') +
				' is-dismissible tebuto-flash-notice"><p>' +
				$('<div>').text(message).html() +
				'</p></div>'
		)
		$('.tebuto-header').after($notice)
		setTimeout(() => {
			$notice.fadeOut(300, function () {
				$(this).remove()
			})
		}, 3000)
	}

	function handleOccurrenceActionDone($btn, response) {
		if (response?.success) {
			window.location.reload()
			return
		}
		showNotice(response?.data || strings.actionError, 'error')
		if ($btn) {
			$btn.prop('disabled', false)
		}
	}

	function handleOccurrenceActionFail($btn) {
		showNotice(strings.actionError || 'Fehler bei der Aktion.', 'error')
		if ($btn) {
			$btn.prop('disabled', false)
		}
	}

	function runOccurrenceStatusChange($btn, action, occurrenceId) {
		$btn.prop('disabled', true).text(strings.processing || 'Wird verarbeitet...')
		return occurrenceAction(action, { occurrence_id: occurrenceId })
			.done((response) => handleOccurrenceActionDone($btn, response))
			.fail(() => handleOccurrenceActionFail($btn))
	}

	function runOccurrenceCancel(occurrenceId, reason) {
		return occurrenceAction('cancel', { occurrence_id: occurrenceId, reason })
			.done((response) => handleOccurrenceActionDone(null, response))
			.fail(() => handleOccurrenceActionFail(null))
	}

	function $contentRefresh($item, activeIds) {
		const occurrences = ($item.data('occurrences') || []).slice()
		const byId = {}
		occurrences.forEach((occ) => {
			byId[occ.id] = occ
		})

		const { past } = splitOccurrences(occurrences)
		const orderedActive = activeIds.map((id) => byId[id]).filter(Boolean)
		const ordered = orderedActive.concat(past)
		$item.data('occurrences', ordered)
		const isInherited = String($item.data('inherited')) === '1'
		const pastVisible = Number.parseInt($item.data('past-visible'), 10) || PAST_PAGE_SIZE
		$item
			.find('.tebuto-accordion-content')
			.html(renderOccurrences($item.data('seminar-id'), ordered, isInherited, pastVisible))
	}

	$(() => {
		if (!$('.tebuto-page-seminars').length) {
			return
		}

		$(document).on('click', '.tebuto-accordion-toggle', function (event) {
			event.preventDefault()
			const $item = $(this).closest('.tebuto-accordion-item')
			const $body = $item.find('.tebuto-accordion-body')
			const expanded = $(this).attr('aria-expanded') === 'true'

			if (expanded) {
				$(this).attr('aria-expanded', 'false')
				$item.removeClass('is-open')
				$body.attr('hidden', true)
				return
			}

			$(this).attr('aria-expanded', 'true')
			$item.addClass('is-open')
			$body.removeAttr('hidden')
			loadOccurrences($item)
		})

		$(document).on('click', '#tebuto-add-seminar-btn, #tebuto-add-seminar-btn-empty', () => {
			resetSeminarForm()
			openModal($('#tebuto-seminar-modal'))
		})

		$(document).on('click', '.tebuto-edit-seminar', function () {
			const seminar = $(this).data('seminar')
			resetSeminarForm()
			fillSeminarForm(seminar || {})
			openModal($('#tebuto-seminar-modal'))
		})

		$(document).on('click', '.tebuto-add-occurrence-btn', function () {
			const seminarId = $(this).data('seminar-id')
			$('#tebuto-create-occurrence-form')[0].reset()
			$('#tebuto-create-occurrence-seminar-id').val(seminarId)
			toggleAddressFields('virtual', $('#tebuto-new-occurrence-address'))
			openModal($('#tebuto-create-occurrence-modal'))
		})

		$(document).on('change', '#occurrence_location_type', function () {
			toggleAddressFields($(this).val(), $('#tebuto-occurrence-address-fields'))
		})

		$(document).on('change', '#new_occurrence_location_type', function () {
			toggleAddressFields($(this).val(), $('#tebuto-new-occurrence-address'))
		})

		$(document).on('change', '#edit_occurrence_location_type', function () {
			toggleAddressFields($(this).val(), $('#tebuto-edit-occurrence-address'))
		})

		$(document).on('change', '#edit_outage_fee_enabled', function () {
			if ($(this).is(':checked')) {
				$('#tebuto-edit-outage-fields').removeAttr('hidden')
			} else {
				$('#tebuto-edit-outage-fields').attr('hidden', true)
			}
		})

		$(document).on('click', '.tebuto-modal-close, .tebuto-modal-close-btn', function () {
			const $modal = $(this).closest('.tebuto-modal')
			if ($modal.is('#tebuto-confirm-modal')) {
				return
			}
			closeModal($modal)
		})

		$(document).on('click', '.tebuto-modal', function (event) {
			if (event.target !== this || this.id === 'tebuto-confirm-modal') {
				return
			}
			closeModal($(this))
		})

		$(document).on('keydown', (event) => {
			if (event.key !== 'Escape') {
				return
			}
			if ($('#tebuto-confirm-modal').is(':visible')) {
				return
			}
			$('.tebuto-modal:visible').each(function () {
				closeModal($(this))
			})
		})

		$('#tebuto-seminar-form').on('submit', () => {
			$('#seminar_description').val(getEditorContent())
		})

		$(document).on('click', '#tebuto-edit-occurrence-btn', () => {
			openModal($('#tebuto-occurrence-edit-modal'))
		})

		let newSessionRowIndex = 0
		$(document).on('click', '#tebuto-add-session', () => {
			const template = document.getElementById('tebuto-session-row-template')
			if (!template) {
				return
			}
			const row = template.content.cloneNode(true)
			const index = `new-${newSessionRowIndex++}`
			for (const el of row.querySelectorAll('[id], [for]')) {
				if (el.id) {
					el.id = el.id.replace('__INDEX__', index)
				}
				if (el.htmlFor) {
					el.htmlFor = el.htmlFor.replace('__INDEX__', index)
				}
			}
			const $list = $('#tebuto-sessions-list')
			$list.find('.tebuto-empty').remove()
			$list.append($(row))
		})

		$(document).on('click', '.tebuto-remove-session', function () {
			$(this).closest('.tebuto-session-row').remove()
			if (!$('#tebuto-sessions-list .tebuto-session-row').length) {
				$('#tebuto-sessions-list').append(
					`<p class="tebuto-empty">${strings.noSessions || 'Noch keine Termine hinterlegt.'}</p>`
				)
			}
		})

		$('#tebuto-sessions-form').on('submit', (event) => {
			let valid = true
			$('#tebuto-sessions-list .tebuto-session-row').each(function () {
				const start = $(this).find('input[name="session_starts[]"]').val()
				const end = $(this).find('input[name="session_ends[]"]').val()
				if (start && end && new Date(end) <= new Date(start)) {
					valid = false
				}
			})
			if (!valid) {
				event.preventDefault()
				showNotice(strings.sessionRangeError || 'Das Ende muss nach dem Beginn liegen.', 'error')
			}
		})

		$(document).on('click', '.tebuto-occurrence-status-btn', function () {
			const $btn = $(this)
			const occurrenceId = $btn.data('occurrence-id')
			const status = $btn.data('status')
			const action = status === 'published' ? 'publish' : 'unpublish'
			const confirmMsg =
				action === 'publish'
					? strings.confirmPublish || 'Veranstaltung wirklich veröffentlichen?'
					: strings.confirmUnpublish || 'Veröffentlichung wirklich zurückziehen?'
			const confirmLabel =
				action === 'publish' ? strings.publishLabel || 'Veröffentlichen' : strings.draftLabel || 'Als Entwurf speichern'

			if (typeof window.tebutoConfirm === 'function') {
				window
					.tebutoConfirm({
						title: strings.confirmTitle || 'Bitte bestätigen',
						message: confirmMsg,
						confirmLabel,
						danger: action !== 'publish'
					})
					.then((result) => {
						if (result.confirmed) {
							runOccurrenceStatusChange($btn, action, occurrenceId)
						}
					})
				return
			}

			runOccurrenceStatusChange($btn, action, occurrenceId)
		})

		$(document).on('click', '.tebuto-occurrence-cancel-btn', function () {
			const occurrenceId = $(this).data('occurrence-id')

			if (typeof window.tebutoConfirm === 'function') {
				window
					.tebutoConfirm({
						title: strings.confirmTitle || 'Bitte bestätigen',
						message: strings.confirmCancelOccurrence || 'Veranstaltung wirklich absagen?',
						confirmLabel: strings.cancel || 'Absagen',
						danger: true,
						prompt: {
							label: strings.cancelReasonPrompt || 'Optionaler Absagegrund:',
							placeholder: strings.cancelReasonPlaceholder || ''
						}
					})
					.then((result) => {
						if (result.confirmed) {
							runOccurrenceCancel(occurrenceId, result.value || '')
						}
					})
				return
			}

			runOccurrenceCancel(occurrenceId, '')
		})

		$(document).on('click', '.tebuto-past-toggle', function () {
			const $toggle = $(this)
			const $section = $toggle.closest('.tebuto-past-occurrences')
			const $body = $section.find('.tebuto-past-body')
			const expanded = $toggle.attr('aria-expanded') === 'true'

			if (expanded) {
				$toggle.attr('aria-expanded', 'false')
				$section.removeClass('is-open')
				$body.attr('hidden', true)
			} else {
				$toggle.attr('aria-expanded', 'true')
				$section.addClass('is-open')
				$body.removeAttr('hidden')
			}
		})

		$(document).on('click', '.tebuto-past-show-more', function () {
			const $item = $(this).closest('.tebuto-accordion-item')
			const current = Number.parseInt($item.data('past-visible'), 10) || PAST_PAGE_SIZE
			const next = current + PAST_PAGE_SIZE
			$item.data('past-visible', next)
			const isInherited = String($item.data('inherited')) === '1'
			$item
				.find('.tebuto-accordion-content')
				.html(renderOccurrences($item.data('seminar-id'), $item.data('occurrences') || [], isInherited, next))
			$item.find('.tebuto-past-toggle').attr('aria-expanded', 'true')
			$item.find('.tebuto-past-occurrences').addClass('is-open')
			$item.find('.tebuto-past-body').removeAttr('hidden')
		})

		$(document).on('click', '.tebuto-move-occurrence', function () {
			const $btn = $(this)
			const $row = $btn.closest('tr')
			const $item = $btn.closest('.tebuto-accordion-item')
			const seminarId = $item.data('seminar-id')
			const dir = $btn.data('dir')
			const $tbody = $row.parent()

			if (dir === 'up') {
				$row.prev('tr').before($row)
			} else {
				$row.next('tr').after($row)
			}

			const activeIds = []
			$tbody.find('tr').each(function () {
				activeIds.push($(this).data('occurrence-id'))
			})

			occurrenceAction('reorder', {
				seminar_id: seminarId,
				occurrence_ids: JSON.stringify(activeIds)
			})
				.done((response) => {
					if (!response?.success) {
						showNotice(response?.data || strings.actionError, 'error')
						$item.data('loaded', false)
						loadOccurrences($item)
						return
					}
					$contentRefresh($item, activeIds)
				})
				.fail(() => {
					showNotice(strings.actionError || 'Fehler bei der Aktion.', 'error')
					$item.data('loaded', false)
					loadOccurrences($item)
				})
		})
	})
})(jQuery)
