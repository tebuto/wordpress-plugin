<?php
/**
 * Tebuto Dashboard Page.
 *
 * @package Tebuto
 */

defined('ABSPATH') || exit;

/**
 * Render the Tebuto dashboard page.
 *
 * @return void
 */
function tebuto_dashboard_page(): void {
    $api = new Tebuto_API();
    
    if (!$api->is_connected()) {
        tebuto_render_not_connected_notice();
        return;
    }

    // Get data for dashboard
    $today = wp_date('Y-m-d\T00:00:00');
    $today_end = wp_date('Y-m-d\T23:59:59');
    $month_start = wp_date('Y-m-01\T00:00:00');

    $upcoming_events = $api->get_events($today, $today_end);
    $bookings_result = $api->get_bookings([
        'start' => $month_start,
        'page_size' => 100,
    ]);
    $categories = $api->get_aggregated_event_categories();

    // Calculate statistics
    $stats = tebuto_calculate_dashboard_stats($bookings_result, $upcoming_events);

    ?>
    <div class="wrap tebuto-admin-wrap">
        <div class="tebuto-header">
            <h1><?php esc_html_e('Dashboard', 'tebuto-online-terminbuchung'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=tebuto-integration')); ?>" class="button">
                <?php esc_html_e('Einstellungen', 'tebuto-online-terminbuchung'); ?>
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="tebuto-stats-grid">
            <div class="tebuto-stat-card">
                <div class="tebuto-stat-icon tebuto-stat-icon-primary">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="tebuto-stat-content">
                    <span class="tebuto-stat-number"><?php echo esc_html($stats['upcoming_count']); ?></span>
                    <span class="tebuto-stat-label"><?php esc_html_e('Anstehende Termine', 'tebuto-online-terminbuchung'); ?></span>
                </div>
            </div>

            <div class="tebuto-stat-card">
                <div class="tebuto-stat-icon tebuto-stat-icon-success">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="tebuto-stat-content">
                    <span class="tebuto-stat-number"><?php echo esc_html($stats['confirmed_count']); ?></span>
                    <span class="tebuto-stat-label"><?php esc_html_e('Bestätigte Termine (Monat)', 'tebuto-online-terminbuchung'); ?></span>
                </div>
            </div>

            <div class="tebuto-stat-card">
                <div class="tebuto-stat-icon tebuto-stat-icon-info">
                    <span class="dashicons dashicons-category"></span>
                </div>
                <div class="tebuto-stat-content">
                    <span class="tebuto-stat-number"><?php echo esc_html(is_array($categories) ? count($categories) : 0); ?></span>
                    <span class="tebuto-stat-label"><?php esc_html_e('Terminkategorien', 'tebuto-online-terminbuchung'); ?></span>
                </div>
            </div>
        </div>

        <div class="tebuto-dashboard-grid">
            <!-- Upcoming Appointments -->
            <div class="tebuto-card">
                <div class="tebuto-card-header">
                    <h2><?php esc_html_e('Termine heute', 'tebuto-online-terminbuchung'); ?></h2>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=tebuto-bookings')); ?>" class="button button-small">
                        <?php esc_html_e('Alle Buchungen', 'tebuto-online-terminbuchung'); ?>
                    </a>
                </div>
                <div class="tebuto-card-body">
                    <?php
                    $all_events = [];
                    if (!is_wp_error($upcoming_events) && is_array($upcoming_events)) {
                        $all_events = tebuto_merge_events($upcoming_events);
                    }
                    ?>
                    <?php if (empty($all_events)) : ?>
                        <p class="tebuto-empty-state"><?php esc_html_e('Keine Termine für heute.', 'tebuto-online-terminbuchung'); ?></p>
                    <?php else : ?>
                        <ul class="tebuto-event-list">
                            <?php
                            $displayed = 0;
                            foreach ($all_events as $event) :
                                if ($displayed >= 10) break;
                                $displayed++;
                                $is_booked = isset($event['details']) && !empty($event['details']['booking']);
                            ?>
                                <li class="tebuto-event-item <?php echo $is_booked ? 'tebuto-event-booked' : ''; ?>">
                                    <div class="tebuto-event-color" style="background-color: <?php echo esc_attr($event['color'] ?? '#009087'); ?>"></div>
                                    <div class="tebuto-event-info">
                                        <span class="tebuto-event-title"><?php echo esc_html($event['title']); ?></span>
                                        <span class="tebuto-event-time">
                                            <?php echo esc_html(tebuto_format_event_datetime($event['start'], $event['end'])); ?>
                                        </span>
                                    </div>
                                    <?php if ($is_booked) : ?>
                                        <span class="tebuto-badge tebuto-badge-success"><?php esc_html_e('Gebucht', 'tebuto-online-terminbuchung'); ?></span>
                                    <?php else : ?>
                                        <span class="tebuto-badge tebuto-badge-default"><?php esc_html_e('Frei', 'tebuto-online-terminbuchung'); ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Categories Overview -->
            <div class="tebuto-card">
                <div class="tebuto-card-header">
                    <h2><?php esc_html_e('Terminkategorien', 'tebuto-online-terminbuchung'); ?></h2>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=tebuto-categories')); ?>" class="button button-small">
                        <?php esc_html_e('Verwalten', 'tebuto-online-terminbuchung'); ?>
                    </a>
                </div>
                <div class="tebuto-card-body">
                    <?php if (is_wp_error($categories)) : ?>
                        <p class="tebuto-error"><?php echo esc_html($api->get_last_error()); ?></p>
                    <?php elseif (empty($categories)) : ?>
                        <p class="tebuto-empty-state"><?php esc_html_e('Noch keine Kategorien erstellt.', 'tebuto-online-terminbuchung'); ?></p>
                    <?php else : ?>
                        <ul class="tebuto-category-list">
                            <?php foreach ($categories as $category) :
                                $is_widget_selectable = ! empty($category['widgetSelectable']);
                                ?>
                                <li
                                    class="tebuto-category-item<?php echo $is_widget_selectable ? '' : ' tebuto-category-item--unavailable'; ?>"
                                    <?php if (!$is_widget_selectable) : ?>
                                        title="<?php esc_attr_e('Nur öffentliche Kategorien können im WordPress-Widget verwendet werden.', 'tebuto-online-terminbuchung'); ?>"
                                    <?php endif; ?>
                                >
                                    <div class="tebuto-category-color" style="background-color: <?php echo esc_attr($category['color']); ?>"></div>
                                    <div class="tebuto-category-info">
                                        <span class="tebuto-category-name"><?php echo esc_html($category['displayName'] ?? $category['name']); ?></span>
                                        <span class="tebuto-category-meta">
                                            <?php echo esc_html($category['duration']); ?> <?php esc_html_e('Min.', 'tebuto-online-terminbuchung'); ?>
                                            · <?php echo esc_html(number_format((float) $category['price'], 2, ',', '.')); ?> €
                                        </span>
                                    </div>
                                    <div class="tebuto-category-badges">
                                        <?php if ($is_widget_selectable) : ?>
                                            <span class="tebuto-badge tebuto-badge-success"><?php esc_html_e('Öffentlich', 'tebuto-online-terminbuchung'); ?></span>
                                        <?php else : ?>
                                            <span class="tebuto-badge tebuto-badge-warning"><?php esc_html_e('Nicht öffentlich', 'tebuto-online-terminbuchung'); ?></span>
                                        <?php endif; ?>
                                        <?php if ($category['privateBookingEnabled']) : ?>
                                            <span class="tebuto-badge tebuto-badge-info"><?php esc_html_e('Privat', 'tebuto-online-terminbuchung'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Calculate dashboard statistics.
 *
 * @param array|WP_Error $bookings_result Bookings response.
 * @param array|WP_Error $events_result   Events response.
 * @return array Statistics.
 */
function tebuto_calculate_dashboard_stats($bookings_result, $events_result): array {
    $stats = [
        'upcoming_count'  => 0,
        'confirmed_count' => 0,
    ];

    // Count upcoming events
    if (!is_wp_error($events_result)) {
        $all_events = tebuto_merge_events($events_result);
        $stats['upcoming_count'] = count($all_events);
    }

    // Count bookings by status
    if (!is_wp_error($bookings_result) && !empty($bookings_result['bookings'])) {
        foreach ($bookings_result['bookings'] as $booking) {
            $status = $booking['event']['status'] ?? '';
            if ($status === 'approved') {
                $stats['confirmed_count']++;
            }
        }
    }

    return $stats;
}

/**
 * Merge rule events and standalone events into a single sorted array.
 *
 * @param array $events_response Events API response.
 * @return array Merged and sorted events.
 */
function tebuto_merge_events(array $events_response): array {
    $all_events = [];

    if (!empty($events_response['rules'])) {
        foreach ($events_response['rules'] as $event) {
            if (!($event['isSkipped'] ?? false)) {
                $all_events[] = $event;
            }
        }
    }

    if (!empty($events_response['standalone'])) {
        $all_events = array_merge($all_events, $events_response['standalone']);
    }

    // Sort by start date
    usort($all_events, function ($a, $b) {
        return strcmp($a['start'] ?? '', $b['start'] ?? '');
    });

    return $all_events;
}

/**
 * Format event datetime for display.
 *
 * @param string $start Start datetime.
 * @param string $end   End datetime.
 * @return string Formatted datetime.
 */
function tebuto_format_event_datetime(string $start, string $end): string {
    $start_ts = strtotime($start);
    $end_ts = strtotime($end);
    
    $date = wp_date('D, d.m.Y', $start_ts);
    $time_start = wp_date('H:i', $start_ts);
    $time_end = wp_date('H:i', $end_ts);
    
    return sprintf('%s, %s - %s', $date, $time_start, $time_end);
}

/**
 * Render the "not connected" notice.
 *
 * @return void
 */
function tebuto_render_not_connected_notice(): void {
    ?>
    <div class="wrap tebuto-admin-wrap">
        <div class="tebuto-card tebuto-card-warning">
            <h2><?php esc_html_e('Verbindung erforderlich', 'tebuto-online-terminbuchung'); ?></h2>
            <p><?php esc_html_e('Du musst dein Tebuto-Konto verbinden, um diese Funktionen nutzen zu können.', 'tebuto-online-terminbuchung'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=tebuto-integration')); ?>" class="button button-primary">
                <?php esc_html_e('Jetzt verbinden', 'tebuto-online-terminbuchung'); ?>
            </a>
        </div>
    </div>
    <?php
}

