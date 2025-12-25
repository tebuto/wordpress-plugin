<?php
/**
 * Tebuto Categories Management Page.
 *
 * @package Tebuto
 */

defined('ABSPATH') || exit;

/**
 * Render the categories management page.
 *
 * @return void
 */
function tebuto_categories_page(): void {
    $api = new Tebuto_API();
    
    if (!$api->is_connected()) {
        tebuto_render_not_connected_notice();
        return;
    }

    // Handle form submissions
    tebuto_handle_category_actions($api);

    $categories = $api->get_event_categories();
    $editing_category = null;

    // Check if editing
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (isset($_GET['edit']) && isset($_GET['_wpnonce'])) {
        $category_id = absint($_GET['edit']);
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'tebuto_edit_category_' . $category_id)) {
            if (!is_wp_error($categories)) {
                foreach ($categories as $cat) {
                    if ($cat['id'] === $category_id) {
                        $editing_category = $cat;
                        break;
                    }
                }
            }
        }
    }

    ?>
    <div class="wrap tebuto-admin-wrap">
        <div class="tebuto-header">
            <h1><?php esc_html_e('Terminkategorien', 'tebuto-online-terminbuchung'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=tebuto-main')); ?>" class="button">
                <?php esc_html_e('← Dashboard', 'tebuto-online-terminbuchung'); ?>
            </a>
        </div>

        <div class="tebuto-categories-grid">
            <!-- Category Form -->
            <div class="tebuto-card">
                <div class="tebuto-card-header">
                    <h2>
                        <?php if ($editing_category) : ?>
                            <?php esc_html_e('Kategorie bearbeiten', 'tebuto-online-terminbuchung'); ?>
                        <?php else : ?>
                            <?php esc_html_e('Neue Kategorie erstellen', 'tebuto-online-terminbuchung'); ?>
                        <?php endif; ?>
                    </h2>
                    <?php if ($editing_category) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=tebuto-categories')); ?>" class="button button-small">
                            <?php esc_html_e('Abbrechen', 'tebuto-online-terminbuchung'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="tebuto-card-body">
                    <form method="post" class="tebuto-category-form">
                        <?php wp_nonce_field('tebuto_category_action', 'tebuto_category_nonce'); ?>
                        
                        <?php if ($editing_category) : ?>
                            <input type="hidden" name="category_id" value="<?php echo esc_attr($editing_category['id']); ?>">
                            <input type="hidden" name="tebuto_action" value="update_category">
                        <?php else : ?>
                            <input type="hidden" name="tebuto_action" value="create_category">
                        <?php endif; ?>

                        <div class="tebuto-form-row">
                            <div class="tebuto-form-group">
                                <label for="category_name"><?php esc_html_e('Name', 'tebuto-online-terminbuchung'); ?> *</label>
                                <input type="text" id="category_name" name="category_name" required
                                    value="<?php echo esc_attr($editing_category['name'] ?? ''); ?>"
                                    placeholder="<?php esc_attr_e('z.B. Erstgespräch', 'tebuto-online-terminbuchung'); ?>">
                            </div>
                            <div class="tebuto-form-group tebuto-form-group-small">
                                <label for="category_color"><?php esc_html_e('Farbe', 'tebuto-online-terminbuchung'); ?></label>
                                <input type="color" id="category_color" name="category_color" 
                                    value="<?php echo esc_attr($editing_category['color'] ?? '#009087'); ?>">
                            </div>
                        </div>

                        <div class="tebuto-form-group">
                            <label for="category_description"><?php esc_html_e('Beschreibung', 'tebuto-online-terminbuchung'); ?></label>
                            <textarea id="category_description" name="category_description" rows="2"
                                placeholder="<?php esc_attr_e('Optionale Beschreibung für Klienten', 'tebuto-online-terminbuchung'); ?>"><?php echo esc_textarea($editing_category['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="tebuto-form-row">
                            <div class="tebuto-form-group">
                                <label for="category_duration"><?php esc_html_e('Dauer (Minuten)', 'tebuto-online-terminbuchung'); ?> *</label>
                                <input type="number" id="category_duration" name="category_duration" required
                                    min="1" max="720"
                                    value="<?php echo esc_attr($editing_category['duration'] ?? '50'); ?>"
                                    <?php echo $editing_category ? 'readonly' : ''; ?>>
                                <?php if ($editing_category) : ?>
                                    <p class="description"><?php esc_html_e('Die Dauer kann nachträglich nicht geändert werden.', 'tebuto-online-terminbuchung'); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="tebuto-form-group">
                                <label for="category_price"><?php esc_html_e('Preis (€)', 'tebuto-online-terminbuchung'); ?> *</label>
                                <input type="number" id="category_price" name="category_price" required
                                    min="0" step="0.01"
                                    value="<?php echo esc_attr($editing_category['price'] ?? '0'); ?>">
                            </div>
                        </div>

                        <div class="tebuto-form-group">
                            <label for="category_location"><?php esc_html_e('Ort', 'tebuto-online-terminbuchung'); ?> *</label>
                            <select id="category_location" name="category_location" required>
                                <option value="onsite" <?php selected(($editing_category['location'] ?? '') === 'onsite'); ?>>
                                    <?php esc_html_e('Vor Ort', 'tebuto-online-terminbuchung'); ?>
                                </option>
                                <option value="virtual" <?php selected(($editing_category['location'] ?? '') === 'virtual'); ?>>
                                    <?php esc_html_e('Online', 'tebuto-online-terminbuchung'); ?>
                                </option>
                                <option value="not-fixed" <?php selected(($editing_category['location'] ?? 'not-fixed') === 'not-fixed'); ?>>
                                    <?php esc_html_e('Flexibel (Vor Ort oder Online)', 'tebuto-online-terminbuchung'); ?>
                                </option>
                            </select>
                        </div>

                        <div class="tebuto-form-row">
                            <div class="tebuto-form-group tebuto-form-checkbox">
                                <label>
                                    <input type="checkbox" name="category_public_booking" value="1"
                                        <?php checked(!empty($editing_category['publicBookingEnabled'])); ?>>
                                    <?php esc_html_e('Öffentliche Buchung aktivieren', 'tebuto-online-terminbuchung'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Ermöglicht Buchungen über das Widget.', 'tebuto-online-terminbuchung'); ?></p>
                            </div>
                            <div class="tebuto-form-group tebuto-form-checkbox">
                                <label>
                                    <input type="checkbox" name="category_private_booking" value="1"
                                        <?php checked($editing_category['privateBookingEnabled'] ?? true); ?>>
                                    <?php esc_html_e('Private Buchung aktivieren', 'tebuto-online-terminbuchung'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Ermöglicht Buchungen durch Klienten.', 'tebuto-online-terminbuchung'); ?></p>
                            </div>
                        </div>

                        <div class="tebuto-form-actions">
                            <?php if ($editing_category) : ?>
                                <button type="submit" class="button button-primary">
                                    <?php esc_html_e('Änderungen speichern', 'tebuto-online-terminbuchung'); ?>
                                </button>
                            <?php else : ?>
                                <button type="submit" class="button button-primary">
                                    <?php esc_html_e('Kategorie erstellen', 'tebuto-online-terminbuchung'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Categories List -->
            <div class="tebuto-card">
                <div class="tebuto-card-header">
                    <h2><?php esc_html_e('Vorhandene Kategorien', 'tebuto-online-terminbuchung'); ?></h2>
                </div>
                <div class="tebuto-card-body">
                    <?php if (is_wp_error($categories)) : ?>
                        <p class="tebuto-error"><?php echo esc_html($api->get_last_error()); ?></p>
                    <?php elseif (empty($categories)) : ?>
                        <p class="tebuto-empty-state"><?php esc_html_e('Noch keine Kategorien vorhanden.', 'tebuto-online-terminbuchung'); ?></p>
                    <?php else : ?>
                        <div class="tebuto-table-responsive">
                            <table class="tebuto-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Name', 'tebuto-online-terminbuchung'); ?></th>
                                        <th><?php esc_html_e('Dauer', 'tebuto-online-terminbuchung'); ?></th>
                                        <th><?php esc_html_e('Preis', 'tebuto-online-terminbuchung'); ?></th>
                                        <th><?php esc_html_e('Ort', 'tebuto-online-terminbuchung'); ?></th>
                                        <th><?php esc_html_e('Status', 'tebuto-online-terminbuchung'); ?></th>
                                        <th><?php esc_html_e('Aktionen', 'tebuto-online-terminbuchung'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category) : ?>
                                        <tr>
                                            <td>
                                                <div class="tebuto-category-name-cell">
                                                    <span class="tebuto-category-color-dot" style="background-color: <?php echo esc_attr($category['color']); ?>"></span>
                                                    <strong><?php echo esc_html($category['name']); ?></strong>
                                                </div>
                                            </td>
                                            <td><?php echo esc_html($category['duration']); ?> Min.</td>
                                            <td><?php echo esc_html(number_format((float) $category['price'], 2, ',', '.')); ?> €</td>
                                            <td>
                                                <?php
                                                $location_labels = [
                                                    'onsite'    => __('Vor Ort', 'tebuto-online-terminbuchung'),
                                                    'virtual'   => __('Online', 'tebuto-online-terminbuchung'),
                                                    'not-fixed' => __('Flexibel', 'tebuto-online-terminbuchung'),
                                                ];
                                                echo esc_html($location_labels[$category['location']] ?? $category['location']);
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($category['publicBookingEnabled']) : ?>
                                                    <span class="tebuto-badge tebuto-badge-success"><?php esc_html_e('Öffentlich', 'tebuto-online-terminbuchung'); ?></span>
                                                <?php else : ?>
                                                    <span class="tebuto-badge tebuto-badge-default"><?php esc_html_e('Privat', 'tebuto-online-terminbuchung'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="tebuto-action-buttons">
                                                    <?php
                                                    $edit_url = wp_nonce_url(
                                                        admin_url('admin.php?page=tebuto-categories&edit=' . $category['id']),
                                                        'tebuto_edit_category_' . $category['id']
                                                    );
                                                    ?>
                                                    <a href="<?php echo esc_url($edit_url); ?>" class="button button-small">
                                                        <?php esc_html_e('Bearbeiten', 'tebuto-online-terminbuchung'); ?>
                                                    </a>
                                                    <form method="post" style="display: inline;" onsubmit="return confirm('<?php echo esc_js(__('Kategorie wirklich löschen?', 'tebuto-online-terminbuchung')); ?>');">
                                                        <?php wp_nonce_field('tebuto_category_action', 'tebuto_category_nonce'); ?>
                                                        <input type="hidden" name="tebuto_action" value="delete_category">
                                                        <input type="hidden" name="category_id" value="<?php echo esc_attr($category['id']); ?>">
                                                        <button type="submit" class="button button-small tebuto-btn-danger">
                                                            <?php esc_html_e('Löschen', 'tebuto-online-terminbuchung'); ?>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Handle category form actions.
 *
 * @param Tebuto_API $api API instance.
 * @return void
 */
function tebuto_handle_category_actions(Tebuto_API $api): void {
    if (!isset($_POST['tebuto_action'], $_POST['tebuto_category_nonce'])) {
        return;
    }

    $nonce = sanitize_text_field(wp_unslash($_POST['tebuto_category_nonce']));
    if (!wp_verify_nonce($nonce, 'tebuto_category_action')) {
        tebuto_admin_notice(__('Ungültige Anfrage.', 'tebuto-online-terminbuchung'), 'error');
        return;
    }

    $action = sanitize_text_field(wp_unslash($_POST['tebuto_action']));

    switch ($action) {
        case 'create_category':
            tebuto_handle_create_category($api);
            break;
        case 'update_category':
            tebuto_handle_update_category($api);
            break;
        case 'delete_category':
            tebuto_handle_delete_category($api);
            break;
    }
}

/**
 * Handle create category action.
 *
 * @param Tebuto_API $api API instance.
 * @return void
 */
function tebuto_handle_create_category(Tebuto_API $api): void {
    $data = tebuto_get_category_form_data();
    
    if (is_wp_error($data)) {
        tebuto_admin_notice($data->get_error_message(), 'error');
        return;
    }

    $result = $api->create_event_category($data);
    
    if (is_wp_error($result)) {
        tebuto_admin_notice(
            sprintf(__('Fehler beim Erstellen: %s', 'tebuto-online-terminbuchung'), $api->get_last_error()),
            'error'
        );
        return;
    }

    tebuto_admin_notice(__('Kategorie erfolgreich erstellt.', 'tebuto-online-terminbuchung'), 'success');
}

/**
 * Handle update category action.
 *
 * @param Tebuto_API $api API instance.
 * @return void
 */
function tebuto_handle_update_category(Tebuto_API $api): void {
    if (!isset($_POST['category_id'])) {
        return;
    }

    $category_id = absint($_POST['category_id']);
    $data = tebuto_get_category_form_data(true);
    
    if (is_wp_error($data)) {
        tebuto_admin_notice($data->get_error_message(), 'error');
        return;
    }

    $result = $api->update_event_category($category_id, $data);
    
    if (is_wp_error($result)) {
        tebuto_admin_notice(
            sprintf(__('Fehler beim Aktualisieren: %s', 'tebuto-online-terminbuchung'), $api->get_last_error()),
            'error'
        );
        return;
    }

    tebuto_admin_notice(__('Kategorie erfolgreich aktualisiert.', 'tebuto-online-terminbuchung'), 'success');
    wp_safe_redirect(admin_url('admin.php?page=tebuto-categories'));
    exit;
}

/**
 * Handle delete category action.
 *
 * @param Tebuto_API $api API instance.
 * @return void
 */
function tebuto_handle_delete_category(Tebuto_API $api): void {
    if (!isset($_POST['category_id'])) {
        return;
    }

    $category_id = absint($_POST['category_id']);
    $result = $api->delete_event_category($category_id);
    
    if (is_wp_error($result)) {
        tebuto_admin_notice(
            sprintf(__('Fehler beim Löschen: %s', 'tebuto-online-terminbuchung'), $api->get_last_error()),
            'error'
        );
        return;
    }

    tebuto_admin_notice(__('Kategorie erfolgreich gelöscht.', 'tebuto-online-terminbuchung'), 'success');
}

/**
 * Get and validate category form data.
 *
 * @param bool $is_update Whether this is an update operation.
 * @return array|WP_Error Form data or error.
 */
function tebuto_get_category_form_data(bool $is_update = false) {
    $name = isset($_POST['category_name']) ? sanitize_text_field(wp_unslash($_POST['category_name'])) : '';
    $description = isset($_POST['category_description']) ? sanitize_textarea_field(wp_unslash($_POST['category_description'])) : '';
    $color = isset($_POST['category_color']) ? sanitize_hex_color(wp_unslash($_POST['category_color'])) : '#009087';
    $duration = isset($_POST['category_duration']) ? absint($_POST['category_duration']) : 50;
    $price = isset($_POST['category_price']) ? floatval($_POST['category_price']) : 0;
    $location = isset($_POST['category_location']) ? sanitize_text_field(wp_unslash($_POST['category_location'])) : 'not-fixed';
    $public_booking = isset($_POST['category_public_booking']);
    $private_booking = isset($_POST['category_private_booking']);

    if (empty($name)) {
        return new WP_Error('missing_name', __('Bitte gib einen Namen ein.', 'tebuto-online-terminbuchung'));
    }

    if (!$public_booking && !$private_booking) {
        return new WP_Error('no_booking_type', __('Mindestens eine Buchungsart muss aktiviert sein.', 'tebuto-online-terminbuchung'));
    }

    $data = [
        'name'                  => $name,
        'description'           => $description ?: null,
        'color'                 => $color ?: '#009087',
        'price'                 => (string) $price,
        'location'              => $location,
        'publicBookingEnabled'  => $public_booking,
        'privateBookingEnabled' => $private_booking,
        'currency'              => 'EUR',
        'taxRate'               => '0',
        'paymentEnabled'        => false,
        'outageFeeEnabled'      => false,
    ];

    // Duration can only be set on create
    if (!$is_update) {
        $data['duration'] = $duration;
    }

    return $data;
}

/**
 * Add an admin notice to be displayed.
 *
 * @param string $message Notice message.
 * @param string $type    Notice type (success, error, warning, info).
 * @return void
 */
function tebuto_admin_notice(string $message, string $type = 'info'): void {
    add_action('admin_notices', function () use ($message, $type) {
        ?>
        <div class="notice notice-<?php echo esc_attr($type); ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
        <?php
    });
}

