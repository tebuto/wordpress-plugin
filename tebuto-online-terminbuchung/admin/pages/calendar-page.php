<?php
/**
 * Tebuto Calendar Page.
 *
 * @package Tebuto
 */

defined('ABSPATH') || exit;

/**
 * Render the calendar page.
 *
 * @return void
 */
function tebuto_calendar_page(): void {
    $api = new Tebuto_API();
    
    if (!$api->is_connected()) {
        tebuto_render_not_connected_notice();
        return;
    }

    // Get categories for filtering
    $categories = $api->get_event_categories();

    ?>
    <div class="wrap tebuto-admin-wrap">
        <div class="tebuto-header">
            <h1><?php esc_html_e('Terminkalender', 'tebuto-online-terminbuchung'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=tebuto-main')); ?>" class="button">
                <?php esc_html_e('← Dashboard', 'tebuto-online-terminbuchung'); ?>
            </a>
        </div>

        <div class="tebuto-calendar-container">
            <!-- Filter Bar -->
            <div class="tebuto-calendar-filters">
                <div class="tebuto-filter-group">
                    <label for="tebuto-category-filter"><?php esc_html_e('Kategorie:', 'tebuto-online-terminbuchung'); ?></label>
                    <select id="tebuto-category-filter">
                        <option value=""><?php esc_html_e('Alle Kategorien', 'tebuto-online-terminbuchung'); ?></option>
                        <?php if (!is_wp_error($categories)) : ?>
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?php echo esc_attr($category['id']); ?>" data-color="<?php echo esc_attr($category['color']); ?>">
                                    <?php echo esc_html($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="tebuto-filter-group">
                    <label for="tebuto-status-filter"><?php esc_html_e('Status:', 'tebuto-online-terminbuchung'); ?></label>
                    <select id="tebuto-status-filter">
                        <option value=""><?php esc_html_e('Alle Status', 'tebuto-online-terminbuchung'); ?></option>
                        <option value="available"><?php esc_html_e('Verfügbar', 'tebuto-online-terminbuchung'); ?></option>
                        <option value="booked"><?php esc_html_e('Gebucht (unbestätigt)', 'tebuto-online-terminbuchung'); ?></option>
                        <option value="approved"><?php esc_html_e('Bestätigt', 'tebuto-online-terminbuchung'); ?></option>
                    </select>
                </div>
                <div class="tebuto-filter-actions">
                    <button type="button" id="tebuto-refresh-calendar" class="button">
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e('Aktualisieren', 'tebuto-online-terminbuchung'); ?>
                    </button>
                </div>
            </div>

            <!-- Calendar Legend -->
            <div class="tebuto-calendar-legend">
                <span class="tebuto-legend-item">
                    <span class="tebuto-legend-color tebuto-legend-available"></span>
                    <?php esc_html_e('Verfügbar', 'tebuto-online-terminbuchung'); ?>
                </span>
                <span class="tebuto-legend-item">
                    <span class="tebuto-legend-color tebuto-legend-pending"></span>
                    <?php esc_html_e('Gebucht (unbestätigt)', 'tebuto-online-terminbuchung'); ?>
                </span>
                <span class="tebuto-legend-item">
                    <span class="tebuto-legend-color tebuto-legend-confirmed"></span>
                    <?php esc_html_e('Bestätigt', 'tebuto-online-terminbuchung'); ?>
                </span>
                <span class="tebuto-legend-item">
                    <span class="tebuto-legend-color tebuto-legend-skipped"></span>
                    <?php esc_html_e('Übersprungen', 'tebuto-online-terminbuchung'); ?>
                </span>
            </div>

            <!-- Calendar Container -->
            <div id="tebuto-calendar"></div>

            <!-- Loading Overlay -->
            <div id="tebuto-calendar-loading" class="tebuto-calendar-loading" style="display: none;">
                <span class="spinner is-active"></span>
                <span><?php esc_html_e('Termine werden geladen...', 'tebuto-online-terminbuchung'); ?></span>
            </div>
        </div>

        <!-- Event Details Modal -->
        <div id="tebuto-event-modal" class="tebuto-modal" style="display: none;">
            <div class="tebuto-modal-content">
                <div class="tebuto-modal-header">
                    <h3 id="tebuto-modal-title"></h3>
                    <button type="button" class="tebuto-modal-close">&times;</button>
                </div>
                <div class="tebuto-modal-body" id="tebuto-modal-body">
                    <!-- Content filled via JS -->
                </div>
                <div class="tebuto-modal-footer" id="tebuto-modal-footer">
                    <!-- Actions filled via JS -->
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Enqueue calendar page assets.
 *
 * @return void
 */
function tebuto_enqueue_calendar_assets(): void {
    // FullCalendar CSS
    wp_enqueue_style(
        'fullcalendar',
        'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css',
        [],
        '6.1.11'
    );

    // FullCalendar JS
    wp_enqueue_script(
        'fullcalendar',
        'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js',
        [],
        '6.1.11',
        true
    );

    // German locale
    wp_enqueue_script(
        'fullcalendar-locale-de',
        'https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/de.global.min.js',
        ['fullcalendar'],
        '6.1.11',
        true
    );

    // Custom calendar script
    wp_enqueue_script(
        'tebuto-calendar',
        TEBUTO_PLUGIN_URL . 'js/admin-calendar.js',
        ['fullcalendar', 'fullcalendar-locale-de', 'jquery'],
        TEBUTO_VERSION,
        true
    );

    // Localize script
    wp_localize_script('tebuto-calendar', 'tebutoCalendar', [
        'ajaxUrl'  => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('tebuto_calendar'),
        'strings'  => [
            'available'       => __('Verfügbar', 'tebuto-online-terminbuchung'),
            'booked'          => __('Gebucht', 'tebuto-online-terminbuchung'),
            'approved'        => __('Bestätigt', 'tebuto-online-terminbuchung'),
            'cancelled'       => __('Abgesagt', 'tebuto-online-terminbuchung'),
            'skipped'         => __('Übersprungen', 'tebuto-online-terminbuchung'),
            'client'          => __('Klient:', 'tebuto-online-terminbuchung'),
            'category'        => __('Kategorie:', 'tebuto-online-terminbuchung'),
            'time'            => __('Zeit:', 'tebuto-online-terminbuchung'),
            'duration'        => __('Dauer:', 'tebuto-online-terminbuchung'),
            'minutes'         => __('Minuten', 'tebuto-online-terminbuchung'),
            'location'        => __('Ort:', 'tebuto-online-terminbuchung'),
            'onsite'          => __('Vor Ort', 'tebuto-online-terminbuchung'),
            'virtual'         => __('Online', 'tebuto-online-terminbuchung'),
            'confirm'         => __('Bestätigen', 'tebuto-online-terminbuchung'),
            'reject'          => __('Ablehnen', 'tebuto-online-terminbuchung'),
            'cancel'          => __('Absagen', 'tebuto-online-terminbuchung'),
            'close'           => __('Schließen', 'tebuto-online-terminbuchung'),
            'confirmAction'   => __('Buchung wirklich bestätigen?', 'tebuto-online-terminbuchung'),
            'rejectAction'    => __('Buchung wirklich ablehnen?', 'tebuto-online-terminbuchung'),
            'cancelAction'    => __('Termin wirklich absagen?', 'tebuto-online-terminbuchung'),
            'actionSuccess'   => __('Aktion erfolgreich ausgeführt.', 'tebuto-online-terminbuchung'),
            'actionError'     => __('Fehler bei der Aktion.', 'tebuto-online-terminbuchung'),
            'loadingError'    => __('Fehler beim Laden der Termine.', 'tebuto-online-terminbuchung'),
        ],
    ]);
}

