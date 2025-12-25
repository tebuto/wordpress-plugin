<?php
/**
 * Tebuto settings page.
 *
 * @package Tebuto
 */

defined('ABSPATH') || exit;

/**
 * Render the Tebuto admin settings page.
 *
 * @return void
 */
function tebuto_admin_page(): void {
    $is_connected = tebuto_is_connected();

    ?>
    <div class="wrap tebuto-admin-wrap">
        <div class="tebuto-header">
            <h1><?php esc_html_e('Tebuto Online-Terminbuchung', 'tebuto-online-terminbuchung'); ?></h1>
            <?php if ($is_connected) : ?>
                <form method="post" class="tebuto-disconnect-form">
                    <?php wp_nonce_field('tebuto_disconnect', 'tebuto_nonce'); ?>
                    <input type="hidden" name="tebuto_disconnect" value="1">
                    <button type="submit" class="button tebuto-btn-danger" onclick="return confirm('<?php echo esc_js(__('Möchtest du die Verbindung wirklich trennen?', 'tebuto-online-terminbuchung')); ?>');">
                        <?php esc_html_e('Verbindung trennen', 'tebuto-online-terminbuchung'); ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (! $is_connected) : ?>
            <div class="tebuto-card tebuto-card-connect">
                <div class="tebuto-card-icon">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="tebuto-card-content">
                    <h2><?php esc_html_e('Mit Tebuto verbinden', 'tebuto-online-terminbuchung'); ?></h2>
                    <p><?php esc_html_e('Du bist derzeit nicht mit Tebuto verbunden. Verbinde dein Konto, um öffentliche Termine auf deiner Website anzubieten.', 'tebuto-online-terminbuchung'); ?></p>
                    <a href="<?php echo esc_url(tebuto_get_authorize_url()); ?>" class="button button-primary button-hero">
                        <?php esc_html_e('Mit Tebuto verbinden', 'tebuto-online-terminbuchung'); ?>
                    </a>
                </div>
            </div>
        <?php else : ?>
            <div class="tebuto-card tebuto-card-success">
                <div class="tebuto-card-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="tebuto-card-content">
                    <h2><?php esc_html_e('Mit Tebuto verbunden', 'tebuto-online-terminbuchung'); ?></h2>
                    <p><?php esc_html_e('Dein Tebuto-Konto ist verbunden. Du kannst jetzt Termine verwalten und das Buchungs-Widget auf deiner Website einbinden.', 'tebuto-online-terminbuchung'); ?></p>
                </div>
            </div>

            <div class="tebuto-settings-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=tebuto-main')); ?>" class="button button-primary button-hero">
                    <span class="dashicons dashicons-dashboard"></span>
                    <?php esc_html_e('Zum Dashboard', 'tebuto-online-terminbuchung'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=tebuto-shortcode')); ?>" class="button button-hero">
                    <span class="dashicons dashicons-shortcode"></span>
                    <?php esc_html_e('Widget einbinden', 'tebuto-online-terminbuchung'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
    <?php
}


