/**
 * Tebuto Calendar JavaScript
 *
 * @package Tebuto
 */

/* global tebutoCalendar, FullCalendar */

(function ($) {
    'use strict';

    let calendar = null;
    let currentFilters = {
        category: '',
        status: ''
    };

    /**
     * Initialize the calendar
     */
    function initCalendar() {
        const calendarEl = document.getElementById('tebuto-calendar');
        if (!calendarEl) {
            return;
        }

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            locale: 'de',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            slotMinTime: '06:00:00',
            slotMaxTime: '22:00:00',
            allDaySlot: false,
            weekNumbers: true,
            nowIndicator: true,
            navLinks: true,
            selectable: false,
            selectMirror: true,
            editable: false,
            dayMaxEvents: true,
            loading: function (isLoading) {
                $('#tebuto-calendar-loading').toggle(isLoading);
            },
            events: function (info, successCallback, failureCallback) {
                fetchEvents(info.start, info.end, successCallback, failureCallback);
            },
            eventClick: function (info) {
                showEventModal(info.event);
            },
            eventClassNames: function (arg) {
                const classes = ['tebuto-calendar-event'];
                const status = arg.event.extendedProps.status;

                if (status === 'available') {
                    classes.push('tebuto-event-available');
                } else if (status === 'booked') {
                    classes.push('tebuto-event-pending');
                } else if (status === 'approved') {
                    classes.push('tebuto-event-confirmed');
                } else if (status === 'skipped') {
                    classes.push('tebuto-event-skipped');
                }

                return classes;
            },
            eventDidMount: function (info) {
                // Add tooltip
                const props = info.event.extendedProps;
                let tooltipContent = info.event.title;
                
                if (props.clientName) {
                    tooltipContent += '\n' + tebutoCalendar.strings.client + ' ' + props.clientName;
                }
                if (props.categoryName) {
                    tooltipContent += '\n' + tebutoCalendar.strings.category + ' ' + props.categoryName;
                }

                info.el.setAttribute('title', tooltipContent);
            }
        });

        calendar.render();

        // Bind filter events
        bindFilterEvents();

        // Bind refresh button
        $('#tebuto-refresh-calendar').on('click', function () {
            calendar.refetchEvents();
        });
    }

    /**
     * Fetch events from the server
     */
    function fetchEvents(start, end, successCallback, failureCallback) {
        $.ajax({
            url: tebutoCalendar.ajaxUrl,
            method: 'POST',
            data: {
                action: 'tebuto_get_events',
                nonce: tebutoCalendar.nonce,
                start: start.toISOString(),
                end: end.toISOString(),
                category: currentFilters.category,
                status: currentFilters.status
            },
            success: function (response) {
                if (response.success && response.data) {
                    successCallback(response.data);
                } else {
                    console.error('Error fetching events:', response);
                    failureCallback(new Error(tebutoCalendar.strings.loadingError));
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', error);
                failureCallback(new Error(tebutoCalendar.strings.loadingError));
            }
        });
    }

    /**
     * Bind filter change events
     */
    function bindFilterEvents() {
        $('#tebuto-category-filter').on('change', function () {
            currentFilters.category = $(this).val();
            calendar.refetchEvents();
        });

        $('#tebuto-status-filter').on('change', function () {
            currentFilters.status = $(this).val();
            calendar.refetchEvents();
        });
    }

    /**
     * Show event modal with details
     */
    function showEventModal(event) {
        const props = event.extendedProps;
        const $modal = $('#tebuto-event-modal');
        const $title = $('#tebuto-modal-title');
        const $body = $('#tebuto-modal-body');
        const $footer = $('#tebuto-modal-footer');

        // Set title
        $title.text(event.title);

        // Build body content
        let bodyHtml = '<div class="tebuto-modal-details">';
        
        // Time
        const startDate = new Date(event.start);
        const endDate = new Date(event.end);
        const dateStr = startDate.toLocaleDateString('de-DE', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        const timeStr = startDate.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' }) +
            ' - ' + endDate.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });

        bodyHtml += `<p><strong>${tebutoCalendar.strings.time}</strong> ${dateStr}, ${timeStr}</p>`;

        // Category
        if (props.categoryName) {
            bodyHtml += `<p><strong>${tebutoCalendar.strings.category}</strong> ${props.categoryName}</p>`;
        }

        // Duration
        if (props.duration) {
            bodyHtml += `<p><strong>${tebutoCalendar.strings.duration}</strong> ${props.duration} ${tebutoCalendar.strings.minutes}</p>`;
        }

        // Client
        if (props.clientName) {
            bodyHtml += `<p><strong>${tebutoCalendar.strings.client}</strong> ${props.clientName}</p>`;
        }

        // Location
        if (props.location) {
            const locationStr = props.location === 'virtual' ? 
                tebutoCalendar.strings.virtual : tebutoCalendar.strings.onsite;
            bodyHtml += `<p><strong>${tebutoCalendar.strings.location}</strong> ${locationStr}</p>`;
        }

        // Status badge
        let statusClass = 'tebuto-badge-default';
        let statusLabel = props.status;

        switch (props.status) {
            case 'available':
                statusClass = 'tebuto-badge-info';
                statusLabel = tebutoCalendar.strings.available;
                break;
            case 'booked':
                statusClass = 'tebuto-badge-warning';
                statusLabel = tebutoCalendar.strings.booked;
                break;
            case 'approved':
                statusClass = 'tebuto-badge-success';
                statusLabel = tebutoCalendar.strings.approved;
                break;
            case 'cancelled':
                statusClass = 'tebuto-badge-danger';
                statusLabel = tebutoCalendar.strings.cancelled;
                break;
            case 'skipped':
                statusClass = 'tebuto-badge-default';
                statusLabel = tebutoCalendar.strings.skipped;
                break;
        }

        bodyHtml += `<p><strong>Status:</strong> <span class="tebuto-badge ${statusClass}">${statusLabel}</span></p>`;
        bodyHtml += '</div>';

        $body.html(bodyHtml);

        // Build footer actions
        let footerHtml = '';

        if (props.bookingId && props.status === 'booked') {
            footerHtml += `<button type="button" class="button button-primary tebuto-modal-confirm" data-booking-id="${props.bookingId}">${tebutoCalendar.strings.confirm}</button>`;
            footerHtml += `<button type="button" class="button tebuto-modal-reject" data-booking-id="${props.bookingId}">${tebutoCalendar.strings.reject}</button>`;
        } else if (props.bookingId && props.status === 'approved') {
            footerHtml += `<button type="button" class="button tebuto-modal-cancel" data-booking-id="${props.bookingId}">${tebutoCalendar.strings.cancel}</button>`;
        }

        footerHtml += `<button type="button" class="button tebuto-modal-close-btn">${tebutoCalendar.strings.close}</button>`;

        $footer.html(footerHtml);

        // Show modal
        $modal.fadeIn(200);

        // Bind action buttons
        bindModalActions($modal);
    }

    /**
     * Bind modal action buttons
     */
    function bindModalActions($modal) {
        $modal.find('.tebuto-modal-confirm').off('click').on('click', function () {
            const bookingId = $(this).data('booking-id');
            if (confirm(tebutoCalendar.strings.confirmAction)) {
                performBookingAction('confirm', bookingId);
            }
        });

        $modal.find('.tebuto-modal-reject').off('click').on('click', function () {
            const bookingId = $(this).data('booking-id');
            if (confirm(tebutoCalendar.strings.rejectAction)) {
                performBookingAction('reject', bookingId);
            }
        });

        $modal.find('.tebuto-modal-cancel').off('click').on('click', function () {
            const bookingId = $(this).data('booking-id');
            if (confirm(tebutoCalendar.strings.cancelAction)) {
                performBookingAction('cancel', bookingId);
            }
        });

        $modal.find('.tebuto-modal-close, .tebuto-modal-close-btn').off('click').on('click', function () {
            $modal.fadeOut(200);
        });
    }

    /**
     * Perform a booking action via AJAX
     */
    function performBookingAction(action, bookingId) {
        $.ajax({
            url: tebutoCalendar.ajaxUrl,
            method: 'POST',
            data: {
                action: 'tebuto_booking_action',
                nonce: tebutoCalendar.nonce,
                booking_action: action,
                booking_id: bookingId
            },
            beforeSend: function () {
                $('#tebuto-event-modal').find('button').prop('disabled', true);
            },
            success: function (response) {
                if (response.success) {
                    $('#tebuto-event-modal').fadeOut(200);
                    calendar.refetchEvents();
                    showNotice(tebutoCalendar.strings.actionSuccess, 'success');
                } else {
                    showNotice(response.data || tebutoCalendar.strings.actionError, 'error');
                }
            },
            error: function () {
                showNotice(tebutoCalendar.strings.actionError, 'error');
            },
            complete: function () {
                $('#tebuto-event-modal').find('button').prop('disabled', false);
            }
        });
    }

    /**
     * Show a temporary notice
     */
    function showNotice(message, type) {
        const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.tebuto-admin-wrap .tebuto-header').after($notice);
        
        setTimeout(function () {
            $notice.fadeOut(300, function () {
                $(this).remove();
            });
        }, 3000);
    }

    /**
     * Close modal on escape key
     */
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            $('.tebuto-modal:visible').fadeOut(200);
        }
    });

    /**
     * Close modal on background click
     */
    $(document).on('click', '.tebuto-modal', function (e) {
        if ($(e.target).hasClass('tebuto-modal')) {
            $(this).fadeOut(200);
        }
    });

    // Initialize on document ready
    $(document).ready(function () {
        initCalendar();
    });

})(jQuery);

