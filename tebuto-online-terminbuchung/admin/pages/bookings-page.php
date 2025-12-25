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
    $api = new Tebuto_API();
    
    if (!$api->is_connected()) {
        tebuto_render_not_connected_notice();
        return;
    }

    // Get filter values from query string
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $current_status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $current_page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    
    // Build filters
    $filters = [
        'page'      => $current_page,
        'page_size' => 20,
    ];
    
    if ($current_status) {
        $filters['status'] = $current_status;
    }

    // Fetch bookings
    $bookings_result = $api->get_bookings($filters);

    ?>
    <div class="wrap tebuto-admin-wrap">
        <div class="tebuto-header">
            <h1><?php esc_html_e('Buchungen', 'tebuto-online-terminbuchung'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=tebuto-main')); ?>" class="button">
                <?php esc_html_e('← Dashboard', 'tebuto-online-terminbuchung'); ?>
            </a>
        </div>

        <!-- Filters -->
        <div class="tebuto-card tebuto-filters-card">
            <form method="get" class="tebuto-bookings-filters">
                <input type="hidden" name="page" value="tebuto-bookings">
                
                <div class="tebuto-filter-group">
                    <label for="status"><?php esc_html_e('Status:', 'tebuto-online-terminbuchung'); ?></label>
                    <select name="status" id="status">
                        <option value=""><?php esc_html_e('Alle Status', 'tebuto-online-terminbuchung'); ?></option>
                        <option value="booked" <?php selected($current_status, 'booked'); ?>><?php esc_html_e('Gebucht (unbestätigt)', 'tebuto-online-terminbuchung'); ?></option>
                        <option value="approved" <?php selected($current_status, 'approved'); ?>><?php esc_html_e('Bestätigt', 'tebuto-online-terminbuchung'); ?></option>
                        <option value="cancelled" <?php selected($current_status, 'cancelled'); ?>><?php esc_html_e('Abgesagt', 'tebuto-online-terminbuchung'); ?></option>
                        <option value="rejected" <?php selected($current_status, 'rejected'); ?>><?php esc_html_e('Abgelehnt', 'tebuto-online-terminbuchung'); ?></option>
                        <option value="outage" <?php selected($current_status, 'outage'); ?>><?php esc_html_e('Ausfall', 'tebuto-online-terminbuchung'); ?></option>
                    </select>
                </div>

                <button type="submit" class="button"><?php esc_html_e('Filtern', 'tebuto-online-terminbuchung'); ?></button>
                
                <?php if ($current_status) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=tebuto-bookings')); ?>" class="button">
                        <?php esc_html_e('Filter zurücksetzen', 'tebuto-online-terminbuchung'); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Bookings Table -->
        <div class="tebuto-card">
            <?php if (is_wp_error($bookings_result)) : ?>
                <div class="tebuto-error-message">
                    <p><?php echo esc_html($api->get_last_error()); ?></p>
                </div>
            <?php elseif (empty($bookings_result['bookings'])) : ?>
                <div class="tebuto-empty-state">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <p><?php esc_html_e('Keine Buchungen gefunden.', 'tebuto-online-terminbuchung'); ?></p>
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
                            <?php foreach ($bookings_result['bookings'] as $booking) : ?>
                                <tr class="tebuto-booking-row" data-booking-id="<?php echo esc_attr($booking['id']); ?>">
                                    <td>
                                        <div class="tebuto-client-info">
                                            <strong><?php echo esc_html($booking['client']['firstName'] . ' ' . $booking['client']['lastName']); ?></strong>
                                            <?php if (!empty($booking['client']['email'])) : ?>
                                                <span class="tebuto-client-email"><?php echo esc_html($booking['client']['email']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tebuto-category-info">
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
                                    esc_html__('%d Buchungen insgesamt', 'tebuto-online-terminbuchung'),
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

