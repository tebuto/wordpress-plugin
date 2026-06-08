<?php
/**
 * Tebuto Bookings Management Page.
 *
 * @package Tebuto
 */

defined('ABSPATH') || exit;

/**
 * Render the bookings management page.
 *
 * @return void
 */
function tebuto_bookings_page(): void {
    $api = tebuto_require_tebuto_connection();
    if ($api === null) {
        return;
    }

    // Get filter values from query string
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $current_status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $current_page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    
    // Date range filter - default to today to +30 days
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $date_from = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : wp_date('Y-m-d');
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $date_to = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : wp_date('Y-m-d', strtotime('+30 days'));
    
    // Build filters
    $filters = [
        'page'      => $current_page,
        'page_size' => 20,
        'start'     => $date_from . 'T00:00:00',
        'end'       => $date_to . 'T23:59:59',
    ];
    
    if ($current_status) {
        $filters['status'] = $current_status;
    }

    // Fetch bookings
    $bookings_result = $api->get_bookings($filters);
    if (is_wp_error($bookings_result) && tebuto_maybe_render_session_expired_from_error($bookings_result)) {
        return;
    }

    // Current time for past appointment check
    $now = time();

    ?>
    <div class="wrap tebuto-admin-wrap">
        <div class="tebuto-header">
            <h1><?php esc_html_e('Buchungen', 'tebuto-online-terminbuchung'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=tebuto-main')); ?>" class="button">
                <?php esc_html_e('← Dashboard', 'tebuto-online-terminbuchung'); ?>
            </a>
        </div>

        <!-- Filters -->
        <div class="tebuto-filters-panel">
            <form method="get" class="tebuto-filters-form" id="tebuto-bookings-filter-form">
                <input type="hidden" name="page" value="tebuto-bookings">
                
                <!-- Quick Date Presets -->
                <div class="tebuto-filter-section">
                    <div class="tebuto-filter-section-header">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <span><?php esc_html_e('Zeitraum', 'tebuto-online-terminbuchung'); ?></span>
                    </div>
                    <div class="tebuto-date-presets">
                        <?php
                        $today = wp_date('Y-m-d');
                        $week_start = wp_date('Y-m-d', strtotime('monday this week'));
                        $week_end = wp_date('Y-m-d', strtotime('sunday this week'));
                        $month_start = wp_date('Y-m-01');
                        $month_end = wp_date('Y-m-t');
                        $next_7 = wp_date('Y-m-d', strtotime('+7 days'));
                        $next_30 = wp_date('Y-m-d', strtotime('+30 days'));
                        
                        $presets = [
                            'today' => [
                                'label' => __('Heute', 'tebuto-online-terminbuchung'),
                                'from' => $today,
                                'to' => $today,
                                'icon' => 'dashicons-marker'
                            ],
                            'week' => [
                                'label' => __('Diese Woche', 'tebuto-online-terminbuchung'),
                                'from' => $week_start,
                                'to' => $week_end,
                                'icon' => 'dashicons-calendar'
                            ],
                            'month' => [
                                'label' => __('Dieser Monat', 'tebuto-online-terminbuchung'),
                                'from' => $month_start,
                                'to' => $month_end,
                                'icon' => 'dashicons-calendar-alt'
                            ],
                            'next7' => [
                                'label' => __('Nächste 7 Tage', 'tebuto-online-terminbuchung'),
                                'from' => $today,
                                'to' => $next_7,
                                'icon' => 'dashicons-arrow-right-alt'
                            ],
                            'next30' => [
                                'label' => __('Nächste 30 Tage', 'tebuto-online-terminbuchung'),
                                'from' => $today,
                                'to' => $next_30,
                                'icon' => 'dashicons-arrow-right-alt2'
                            ],
                        ];
                        
                        foreach ($presets as $key => $preset) :
                            $is_active = ($date_from === $preset['from'] && $date_to === $preset['to']);
                        ?>
                            <button type="button" 
                                class="tebuto-date-preset <?php echo $is_active ? 'active' : ''; ?>"
                                data-from="<?php echo esc_attr($preset['from']); ?>"
                                data-to="<?php echo esc_attr($preset['to']); ?>">
                                <?php echo esc_html($preset['label']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="tebuto-date-range">
                        <div class="tebuto-date-input-group">
                            <label for="date_from"><?php esc_html_e('Von', 'tebuto-online-terminbuchung'); ?></label>
                            <div class="tebuto-date-input-wrapper">
                                <span class="dashicons dashicons-calendar"></span>
                                <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($date_from); ?>">
                            </div>
                        </div>
                        <div class="tebuto-date-range-separator">
                            <span class="dashicons dashicons-arrow-right-alt"></span>
                        </div>
                        <div class="tebuto-date-input-group">
                            <label for="date_to"><?php esc_html_e('Bis', 'tebuto-online-terminbuchung'); ?></label>
                            <div class="tebuto-date-input-wrapper">
                                <span class="dashicons dashicons-calendar"></span>
                                <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($date_to); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="tebuto-filter-section">
                    <div class="tebuto-filter-section-header">
                        <span class="dashicons dashicons-tag"></span>
                        <span><?php esc_html_e('Status', 'tebuto-online-terminbuchung'); ?></span>
                    </div>
                    <div class="tebuto-status-chips">
                        <?php
                        $statuses = [
                            '' => [
                                'label' => __('Alle', 'tebuto-online-terminbuchung'),
                                'color' => 'default'
                            ],
                            'booked' => [
                                'label' => __('Unbestätigt', 'tebuto-online-terminbuchung'),
                                'color' => 'warning'
                            ],
                            'approved' => [
                                'label' => __('Bestätigt', 'tebuto-online-terminbuchung'),
                                'color' => 'success'
                            ],
                            'cancelled' => [
                                'label' => __('Abgesagt', 'tebuto-online-terminbuchung'),
                                'color' => 'danger'
                            ],
                            'rejected' => [
                                'label' => __('Abgelehnt', 'tebuto-online-terminbuchung'),
                                'color' => 'danger'
                            ],
                            'outage' => [
                                'label' => __('Ausfall', 'tebuto-online-terminbuchung'),
                                'color' => 'muted'
                            ],
                        ];
                        
                        foreach ($statuses as $value => $status) :
                            $is_selected = ($current_status === $value);
                        ?>
                            <label class="tebuto-status-chip tebuto-status-chip-<?php echo esc_attr($status['color']); ?> <?php echo $is_selected ? 'active' : ''; ?>">
                                <input type="radio" name="status" value="<?php echo esc_attr($value); ?>" <?php checked($current_status, $value); ?>>
                                <span class="tebuto-status-chip-dot"></span>
                                <span class="tebuto-status-chip-label"><?php echo esc_html($status['label']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="tebuto-filter-actions-bar">
                    <button type="submit" class="tebuto-filter-submit">
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e('Filter anwenden', 'tebuto-online-terminbuchung'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=tebuto-bookings')); ?>" class="tebuto-filter-reset">
                        <span class="dashicons dashicons-image-rotate"></span>
                        <?php esc_html_e('Zurücksetzen', 'tebuto-online-terminbuchung'); ?>
                    </a>
                </div>
            </form>
        </div>

        <!-- Bookings Table -->
        <div class="tebuto-card">
            <?php if (is_wp_error($bookings_result)) : ?>
                <div class="tebuto-card-body">
                    <div class="tebuto-error-message">
                        <p><?php echo esc_html($api->get_last_error()); ?></p>
                    </div>
                </div>
            <?php elseif (empty($bookings_result['bookings'])) : ?>
                <div class="tebuto-card-body">
                    <div class="tebuto-empty-state">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <p><?php esc_html_e('Keine Buchungen im gewählten Zeitraum gefunden.', 'tebuto-online-terminbuchung'); ?></p>
                    </div>
                </div>
            <?php else : ?>
                <div class="tebuto-table-responsive">
                    <table class="tebuto-table tebuto-bookings-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Klient', 'tebuto-online-terminbuchung'); ?></th>
                                <th><?php esc_html_e('Kategorie', 'tebuto-online-terminbuchung'); ?></th>
                                <th><?php esc_html_e('Datum & Zeit', 'tebuto-online-terminbuchung'); ?></th>
                                <th><?php esc_html_e('Ort', 'tebuto-online-terminbuchung'); ?></th>
                                <th><?php esc_html_e('Status', 'tebuto-online-terminbuchung'); ?></th>
                                <th><?php esc_html_e('Aktionen', 'tebuto-online-terminbuchung'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings_result['bookings'] as $booking) : 
                                $event_start = strtotime($booking['event']['start']);
                                $is_past = $event_start < $now;
                            ?>
                                <tr class="tebuto-booking-row <?php echo $is_past ? 'tebuto-booking-past' : ''; ?>" data-booking-id="<?php echo esc_attr($booking['id']); ?>">
                                    <td>
                                        <div class="tebuto-client-info">
                                            <strong><?php echo esc_html($booking['client']['firstName'] . ' ' . $booking['client']['lastName']); ?></strong>
                                            <?php if (!empty($booking['client']['email'])) : ?>
                                                <span class="tebuto-client-email"><?php echo esc_html($booking['client']['email']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tebuto-table .tebuto-category-info">
                                            <span class="tebuto-category-color-dot" style="background-color: <?php echo esc_attr($booking['event']['category']['color'] ?? '#009087'); ?>"></span>
                                            <?php echo esc_html($booking['event']['category']['name'] ?? '-'); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tebuto-datetime-info">
                                            <span class="tebuto-date"><?php echo esc_html(tebuto_format_date($booking['event']['start'])); ?></span>
                                            <span class="tebuto-time"><?php echo esc_html(tebuto_format_time_range($booking['event']['start'], $booking['event']['end'])); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $location = $booking['locationSelection'] ?? 'onsite';
                                        $location_label = $location === 'virtual' 
                                            ? __('Online', 'tebuto-online-terminbuchung')
                                            : __('Vor Ort', 'tebuto-online-terminbuchung');
                                        $location_icon = $location === 'virtual' ? 'dashicons-video-alt3' : 'dashicons-location';
                                        ?>
                                        <span class="tebuto-location">
                                            <span class="dashicons <?php echo esc_attr($location_icon); ?>"></span>
                                            <?php echo esc_html($location_label); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo wp_kses_post(tebuto_get_status_badge($booking['event']['status'])); ?>
                                        <?php if (!$booking['isConfirmed'] && $booking['event']['status'] === 'booked') : ?>
                                            <span class="tebuto-badge tebuto-badge-warning"><?php esc_html_e('Ausstehend', 'tebuto-online-terminbuchung'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="tebuto-booking-actions">
                                            <?php if (!$is_past) : ?>
                                                <?php if ($booking['event']['status'] === 'booked' && !$booking['isConfirmed']) : ?>
                                                    <button type="button" class="button button-small button-primary tebuto-confirm-booking" 
                                                        data-booking-id="<?php echo esc_attr($booking['id']); ?>">
                                                        <?php esc_html_e('Bestätigen', 'tebuto-online-terminbuchung'); ?>
                                                    </button>
                                                    <button type="button" class="button button-small tebuto-reject-booking" 
                                                        data-booking-id="<?php echo esc_attr($booking['id']); ?>">
                                                        <?php esc_html_e('Ablehnen', 'tebuto-online-terminbuchung'); ?>
                                                    </button>
                                                <?php elseif ($booking['event']['status'] === 'approved') : ?>
                                                    <button type="button" class="button button-small tebuto-cancel-booking" 
                                                        data-booking-id="<?php echo esc_attr($booking['id']); ?>">
                                                        <?php esc_html_e('Absagen', 'tebuto-online-terminbuchung'); ?>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <button type="button" class="button button-small tebuto-view-booking" 
                                                data-booking="<?php echo esc_attr(wp_json_encode($booking)); ?>">
                                                <?php esc_html_e('Details', 'tebuto-online-terminbuchung'); ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($bookings_result['totalPages'] > 1) : ?>
                    <div class="tebuto-pagination">
                        <?php
                        $base_url = admin_url('admin.php?page=tebuto-bookings');
                        $base_url = add_query_arg('date_from', $date_from, $base_url);
                        $base_url = add_query_arg('date_to', $date_to, $base_url);
                        if ($current_status) {
                            $base_url = add_query_arg('status', $current_status, $base_url);
                        }

                        // Previous
                        if ($current_page > 1) :
                            ?>
                            <a href="<?php echo esc_url(add_query_arg('paged', $current_page - 1, $base_url)); ?>" class="button">
                                &laquo; <?php esc_html_e('Zurück', 'tebuto-online-terminbuchung'); ?>
                            </a>
                        <?php endif; ?>

                        <span class="tebuto-pagination-info">
                            <?php
                            printf(
                                /* translators: 1: current page, 2: total pages */
                                esc_html__('Seite %1$d von %2$d', 'tebuto-online-terminbuchung'),
                                $current_page,
                                $bookings_result['totalPages']
                            );
                            ?>
                            <span class="tebuto-pagination-total">
                                (<?php
                                printf(
                                    /* translators: %d: total items */
                                    esc_html__('%d Buchungen', 'tebuto-online-terminbuchung'),
                                    $bookings_result['totalItems']
                                );
                                ?>)
                            </span>
                        </span>

                        <?php
                        // Next
                        if ($current_page < $bookings_result['totalPages']) :
                            ?>
                            <a href="<?php echo esc_url(add_query_arg('paged', $current_page + 1, $base_url)); ?>" class="button">
                                <?php esc_html_e('Weiter', 'tebuto-online-terminbuchung'); ?> &raquo;
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Booking Details Modal -->
        <div id="tebuto-booking-modal" class="tebuto-modal" style="display: none;">
            <div class="tebuto-modal-content">
                <div class="tebuto-modal-header">
                    <h3><?php esc_html_e('Buchungsdetails', 'tebuto-online-terminbuchung'); ?></h3>
                    <button type="button" class="tebuto-modal-close">&times;</button>
                </div>
                <div class="tebuto-modal-body" id="tebuto-booking-modal-body">
                    <!-- Filled by JavaScript -->
                </div>
                <div class="tebuto-modal-footer">
                    <button type="button" class="button tebuto-modal-close-btn"><?php esc_html_e('Schließen', 'tebuto-online-terminbuchung'); ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Format a date string for display.
 *
 * @param string $datetime ISO datetime string.
 * @return string Formatted date.
 */
function tebuto_format_date(string $datetime): string {
    $timestamp = strtotime($datetime);
    return wp_date('D, d.m.Y', $timestamp);
}

/**
 * Format a time range for display.
 *
 * @param string $start Start datetime.
 * @param string $end   End datetime.
 * @return string Formatted time range.
 */
function tebuto_format_time_range(string $start, string $end): string {
    $start_ts = strtotime($start);
    $end_ts = strtotime($end);
    return wp_date('H:i', $start_ts) . ' - ' . wp_date('H:i', $end_ts);
}

/**
 * Get status badge HTML.
 *
 * @param string $status Event status.
 * @return string Badge HTML.
 */
function tebuto_get_status_badge(string $status): string {
    $badges = [
        'open'      => ['label' => __('Offen', 'tebuto-online-terminbuchung'), 'class' => 'tebuto-badge-default'],
        'booked'    => ['label' => __('Gebucht', 'tebuto-online-terminbuchung'), 'class' => 'tebuto-badge-warning'],
        'approved'  => ['label' => __('Bestätigt', 'tebuto-online-terminbuchung'), 'class' => 'tebuto-badge-success'],
        'cancelled' => ['label' => __('Abgesagt', 'tebuto-online-terminbuchung'), 'class' => 'tebuto-badge-danger'],
        'rejected'  => ['label' => __('Abgelehnt', 'tebuto-online-terminbuchung'), 'class' => 'tebuto-badge-danger'],
        'outage'    => ['label' => __('Ausfall', 'tebuto-online-terminbuchung'), 'class' => 'tebuto-badge-danger'],
        'skipped'   => ['label' => __('Übersprungen', 'tebuto-online-terminbuchung'), 'class' => 'tebuto-badge-default'],
    ];

    $badge = $badges[$status] ?? ['label' => ucfirst($status), 'class' => 'tebuto-badge-default'];

    return sprintf(
        '<span class="tebuto-badge %s">%s</span>',
        esc_attr($badge['class']),
        esc_html($badge['label'])
    );
}
