<?php
/**
 * Tebuto shortcode settings page.
 *
 * @package Tebuto
 */

defined('ABSPATH') || exit;

/**
 * Render the shortcode settings page.
 *
 * @return void
 */
function tebuto_shortcode_page(): void {
    $current_user_id  = get_current_user_id();
    $therapist_uuid   = tebuto_get_user_meta($current_user_id, 'therapist_uuid');
    $background_color = tebuto_get_user_meta($current_user_id, 'background_color', '#ffffff');
    $border           = tebuto_get_user_meta($current_user_id, 'border', 'false');

    ?>
    <div class="wrap tebuto-admin-wrap">
        <h1><?php esc_html_e('Shortcode', 'tebuto-online-terminbuchung'); ?></h1>

        <?php if (empty($therapist_uuid)) : ?>
            <div class="tebuto-card tebuto-card-warning">
                <p><?php esc_html_e('Du musst dich zuerst mit Tebuto verbinden, um den Shortcode zu verwenden.', 'tebuto-online-terminbuchung'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=tebuto-integration')); ?>" class="button button-primary">
                    <?php esc_html_e('Zu den Einstellungen', 'tebuto-online-terminbuchung'); ?>
                </a>
            </div>
        <?php else : ?>
            <div class="tebuto-card">
                <h2><?php esc_html_e('Shortcode kopieren', 'tebuto-online-terminbuchung'); ?></h2>
                <p><?php esc_html_e('Füge diesen Shortcode in eine Seite oder einen Beitrag ein, um das Tebuto Buchungswidget anzuzeigen:', 'tebuto-online-terminbuchung'); ?></p>
                <div class="tebuto-shortcode-display">
                    <code id="tebuto-shortcode">[tebuto_online_terminbuchung_widget]</code>
                    <button type="button" class="button tebuto-copy-btn" onclick="tebuto_copyShortcode()">
                        <?php esc_html_e('Kopieren', 'tebuto-online-terminbuchung'); ?>
                    </button>
                </div>
                <p class="description"><?php esc_html_e('Alternativ kannst du den Tebuto-Block im Gutenberg-Editor verwenden.', 'tebuto-online-terminbuchung'); ?></p>
            </div>

            <div class="tebuto-card">
                <h2><?php esc_html_e('Widget-Einstellungen', 'tebuto-online-terminbuchung'); ?></h2>
                <form method="post">
                    <?php wp_nonce_field('tebuto_save_settings', 'tebuto_nonce'); ?>
                    <input type="hidden" name="tebuto_save_settings" value="1">

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="background_color"><?php esc_html_e('Hintergrundfarbe', 'tebuto-online-terminbuchung'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="background_color" id="background_color" value="<?php echo esc_attr($background_color); ?>" class="tebuto-color-picker">
                                <p class="description"><?php esc_html_e('Die Hintergrundfarbe des Buchungswidgets.', 'tebuto-online-terminbuchung'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="border"><?php esc_html_e('Rahmen anzeigen', 'tebuto-online-terminbuchung'); ?></label>
                            </th>
                            <td>
                                <label class="tebuto-toggle">
                                    <input type="checkbox" name="border" id="border" value="true" <?php checked($border, 'true'); ?>>
                                    <span class="tebuto-toggle-slider"></span>
                                </label>
                                <p class="description"><?php esc_html_e('Zeigt einen Rahmen um das Widget an.', 'tebuto-online-terminbuchung'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php esc_html_e('Einstellungen speichern', 'tebuto-online-terminbuchung'); ?></button>
                    </p>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function tebuto_copyShortcode() {
        const shortcode = document.getElementById('tebuto-shortcode').innerText;
        navigator.clipboard.writeText(shortcode).then(function() {
            const btn = document.querySelector('.tebuto-copy-btn');
            const originalText = btn.innerText;
            btn.innerText = '<?php echo esc_js(__('Kopiert!', 'tebuto-online-terminbuchung')); ?>';
            setTimeout(function() {
                btn.innerText = originalText;
            }, 2000);
        });
    }
    </script>
    <?php
}
