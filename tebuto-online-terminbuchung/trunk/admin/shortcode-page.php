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
    $current_user_id    = get_current_user_id();
    $therapist_uuid     = tebuto_get_user_meta($current_user_id, 'therapist_uuid');
    $background_color   = tebuto_get_user_meta($current_user_id, 'background_color', '#ffffff');
    $primary_color      = tebuto_get_user_meta($current_user_id, 'primary_color', '#00B4A9');
    $text_primary       = tebuto_get_user_meta($current_user_id, 'text_primary', '#374151');
    $text_secondary     = tebuto_get_user_meta($current_user_id, 'text_secondary', '#6b7280');
    $border_color       = tebuto_get_user_meta($current_user_id, 'border_color', '#E9E9E9');
    $border             = tebuto_get_user_meta($current_user_id, 'border', 'true');
    $inherit_font       = tebuto_get_user_meta($current_user_id, 'inherit_font', 'false');
    $categories         = tebuto_get_user_meta($current_user_id, 'categories', '');
    $show_quick_filters = tebuto_get_user_meta($current_user_id, 'show_quick_filters', 'false');
    $custom_css         = tebuto_get_user_meta($current_user_id, 'custom_css', '');

    // Convert categories string to array
    $selected_categories = [];
    if (! empty($categories)) {
        $selected_categories = array_map('intval', explode(',', $categories));
    }

    // Check if user has subusers (multi-user account)
    $is_multi_user = false;
    $api = new Tebuto_API();
    if ($api->is_connected()) {
        $who_am_i = $api->who_am_i();
        if (! is_wp_error($who_am_i) && isset($who_am_i['therapists'][0]['therapist']['subusers'])) {
            $is_multi_user = count($who_am_i['therapists'][0]['therapist']['subusers']) > 0;
        }
    }

    // Theme presets
    $presets = [
        [
            'name'          => 'Tebuto',
            'description'   => 'Standard',
            'primaryColor'  => '#00B4A9',
            'backgroundColor' => '#ffffff',
            'textPrimary'   => '#374151',
            'textSecondary' => '#6b7280',
            'borderColor'   => '#E9E9E9',
        ],
        [
            'name'          => 'Professional Blue',
            'description'   => 'Blau',
            'primaryColor'  => '#3b82f6',
            'backgroundColor' => '#ffffff',
            'textPrimary'   => '#1e293b',
            'textSecondary' => '#64748b',
            'borderColor'   => '#e2e8f0',
        ],
        [
            'name'          => 'Warm Orange',
            'description'   => 'Orange',
            'primaryColor'  => '#f97316',
            'backgroundColor' => '#ffffff',
            'textPrimary'   => '#1c1917',
            'textSecondary' => '#78716c',
            'borderColor'   => '#fed7aa',
        ],
        [
            'name'          => 'Elegant Purple',
            'description'   => 'Lila',
            'primaryColor'  => '#8b5cf6',
            'backgroundColor' => '#ffffff',
            'textPrimary'   => '#1e1b4b',
            'textSecondary' => '#6b21a8',
            'borderColor'   => '#e9d5ff',
        ],
        [
            'name'          => 'Nature Green',
            'description'   => 'Grün',
            'primaryColor'  => '#059669',
            'backgroundColor' => '#ffffff',
            'textPrimary'   => '#14532d',
            'textSecondary' => '#166534',
            'borderColor'   => '#bbf7d0',
        ],
    ];

    ?>
    <div class="wrap tebuto-admin-wrap">
        <div class="tebuto-header">
            <h1><?php esc_html_e('Shortcode & Widget', 'tebuto-online-terminbuchung'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=tebuto-main')); ?>" class="button">
                <?php esc_html_e('← Dashboard', 'tebuto-online-terminbuchung'); ?>
            </a>
        </div>

        <?php if (isset($_GET['saved'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Einstellungen wurden gespeichert.', 'tebuto-online-terminbuchung'); ?></p>
            </div>
        <?php endif; ?>

        <?php if (empty($therapist_uuid)) : ?>
            <div class="tebuto-card tebuto-card-warning">
                <h2><?php esc_html_e('Verbindung erforderlich', 'tebuto-online-terminbuchung'); ?></h2>
                <p><?php esc_html_e('Du musst dich zuerst mit Tebuto verbinden, um den Shortcode zu verwenden.', 'tebuto-online-terminbuchung'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=tebuto-integration')); ?>" class="button button-primary">
                    <?php esc_html_e('Jetzt verbinden', 'tebuto-online-terminbuchung'); ?>
                </a>
            </div>
        <?php else : ?>
            <div class="tebuto-widget-editor-layout">
                <!-- Left Column: Settings -->
                <div class="tebuto-widget-settings-column">
                <!-- Shortcode Copy Section -->
                <div class="tebuto-card">
                    <div class="tebuto-card-header">
                        <h2><?php esc_html_e('Shortcode einbinden', 'tebuto-online-terminbuchung'); ?></h2>
                    </div>
                    <div class="tebuto-card-body">
                        <p class="tebuto-intro-text"><?php esc_html_e('Füge diesen Shortcode in eine Seite oder einen Beitrag ein, um das Tebuto Buchungswidget anzuzeigen:', 'tebuto-online-terminbuchung'); ?></p>
                        
                        <div class="tebuto-shortcode-box">
                            <code id="tebuto-shortcode">[tebuto_online_terminbuchung_widget]</code>
                            <button type="button" class="button button-primary tebuto-copy-btn" onclick="tebuto_copyShortcode()">
                                <span class="dashicons dashicons-clipboard"></span>
                                <?php esc_html_e('Kopieren', 'tebuto-online-terminbuchung'); ?>
                            </button>
                        </div>

                        <div class="tebuto-info-box">
                            <span class="dashicons dashicons-info"></span>
                            <p><?php esc_html_e('Alternativ kannst du den Tebuto-Block im Gutenberg-Editor verwenden. Suche einfach nach "Tebuto" in der Block-Bibliothek.', 'tebuto-online-terminbuchung'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Widget Settings Section -->
                <div class="tebuto-card">
                    <div class="tebuto-card-header">
                        <h2><?php esc_html_e('Widget-Einstellungen', 'tebuto-online-terminbuchung'); ?></h2>
                    </div>
                    <div class="tebuto-card-body">
                            <form method="post" class="tebuto-settings-form" id="tebuto-widget-settings-form">
                            <?php wp_nonce_field('tebuto_save_settings', 'tebuto_nonce'); ?>
                            <input type="hidden" name="tebuto_save_settings" value="1">

                                <!-- Theme Presets -->
                                <div class="tebuto-form-section">
                                    <h3 class="tebuto-form-section-title"><?php esc_html_e('Farbvorlagen', 'tebuto-online-terminbuchung'); ?></h3>
                                    <div class="tebuto-theme-presets">
                                        <?php foreach ($presets as $index => $preset) : ?>
                                            <button type="button" class="tebuto-preset-btn" data-preset="<?php echo esc_attr($index); ?>"
                                                data-primary="<?php echo esc_attr($preset['primaryColor']); ?>"
                                                data-background="<?php echo esc_attr($preset['backgroundColor']); ?>"
                                                data-text-primary="<?php echo esc_attr($preset['textPrimary']); ?>"
                                                data-text-secondary="<?php echo esc_attr($preset['textSecondary']); ?>"
                                                data-border-color="<?php echo esc_attr($preset['borderColor']); ?>">
                                                <span class="tebuto-preset-color" style="background: <?php echo esc_attr($preset['primaryColor']); ?>"></span>
                                                <span class="tebuto-preset-name"><?php echo esc_html($preset['description']); ?></span>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Colors Section -->
                                <div class="tebuto-form-section">
                                    <h3 class="tebuto-form-section-title"><?php esc_html_e('Eigene Farben festlegen', 'tebuto-online-terminbuchung'); ?></h3>
                                    
                                    <div class="tebuto-color-grid">
                                        <div class="tebuto-form-group">
                                            <label for="primary_color"><?php esc_html_e('Primärfarbe', 'tebuto-online-terminbuchung'); ?></label>
                                            <div class="tebuto-color-input">
                                                <input type="color" name="primary_color" id="primary_color" value="<?php echo esc_attr($primary_color); ?>">
                                                <input type="text" id="primary_color_hex" class="tebuto-color-hex" value="<?php echo esc_attr(strtoupper($primary_color)); ?>" maxlength="7">
                                            </div>
                                            <p class="description"><?php esc_html_e('Buttons und Akzente', 'tebuto-online-terminbuchung'); ?></p>
                                        </div>

                            <div class="tebuto-form-group">
                                            <label for="background_color"><?php esc_html_e('Hintergrund', 'tebuto-online-terminbuchung'); ?></label>
                                <div class="tebuto-color-input">
                                    <input type="color" name="background_color" id="background_color" value="<?php echo esc_attr($background_color); ?>">
                                    <input type="text" id="background_color_hex" class="tebuto-color-hex" value="<?php echo esc_attr(strtoupper($background_color)); ?>" maxlength="7">
                                </div>
                                            <p class="description"><?php esc_html_e('Widget-Hintergrund', 'tebuto-online-terminbuchung'); ?></p>
                                        </div>

                                        <div class="tebuto-form-group">
                                            <label for="text_primary"><?php esc_html_e('Textfarbe', 'tebuto-online-terminbuchung'); ?></label>
                                            <div class="tebuto-color-input">
                                                <input type="color" name="text_primary" id="text_primary" value="<?php echo esc_attr($text_primary); ?>">
                                                <input type="text" id="text_primary_hex" class="tebuto-color-hex" value="<?php echo esc_attr(strtoupper($text_primary)); ?>" maxlength="7">
                                            </div>
                                            <p class="description"><?php esc_html_e('Haupttext', 'tebuto-online-terminbuchung'); ?></p>
                                        </div>

                                        <div class="tebuto-form-group">
                                            <label for="text_secondary"><?php esc_html_e('Sekundärtext', 'tebuto-online-terminbuchung'); ?></label>
                                            <div class="tebuto-color-input">
                                                <input type="color" name="text_secondary" id="text_secondary" value="<?php echo esc_attr($text_secondary); ?>">
                                                <input type="text" id="text_secondary_hex" class="tebuto-color-hex" value="<?php echo esc_attr(strtoupper($text_secondary)); ?>" maxlength="7">
                                            </div>
                                            <p class="description"><?php esc_html_e('Beschreibungen', 'tebuto-online-terminbuchung'); ?></p>
                                        </div>

                                        <div class="tebuto-form-group">
                                            <label for="border_color"><?php esc_html_e('Rahmenfarbe', 'tebuto-online-terminbuchung'); ?></label>
                                            <div class="tebuto-color-input">
                                                <input type="color" name="border_color" id="border_color" value="<?php echo esc_attr($border_color); ?>">
                                                <input type="text" id="border_color_hex" class="tebuto-color-hex" value="<?php echo esc_attr(strtoupper($border_color)); ?>" maxlength="7">
                                            </div>
                                            <p class="description"><?php esc_html_e('Rahmen und Trennlinien', 'tebuto-online-terminbuchung'); ?></p>
                                        </div>
                                    </div>
                            </div>

                                <!-- Display Options -->
                                <div class="tebuto-form-section">
                                    <h3 class="tebuto-form-section-title"><?php esc_html_e('Anzeige-Optionen', 'tebuto-online-terminbuchung'); ?></h3>
                                    
                                    <div class="tebuto-switch-option">
                                        <div class="tebuto-switch-option-text">
                                            <span class="tebuto-switch-option-label"><?php esc_html_e('Rahmen anzeigen', 'tebuto-online-terminbuchung'); ?></span>
                                            <span class="tebuto-switch-option-desc"><?php esc_html_e('Zeigt einen Rahmen um das Widget', 'tebuto-online-terminbuchung'); ?></span>
                                        </div>
                                        <label class="tebuto-switch">
                                    <input type="checkbox" name="border" id="border" value="true" <?php checked($border, 'true'); ?>>
                                            <span class="tebuto-switch-slider"></span>
                                        </label>
                                    </div>

                                    <div class="tebuto-switch-option">
                                        <div class="tebuto-switch-option-text">
                                            <span class="tebuto-switch-option-label"><?php esc_html_e('Schriftart übernehmen', 'tebuto-online-terminbuchung'); ?></span>
                                            <span class="tebuto-switch-option-desc"><?php esc_html_e('Verwendet die Schriftart deiner Website anstelle der Tebuto-Schrift', 'tebuto-online-terminbuchung'); ?></span>
                                        </div>
                                        <label class="tebuto-switch">
                                            <input type="checkbox" name="inherit_font" id="inherit_font" value="true" <?php checked($inherit_font, 'true'); ?>>
                                            <span class="tebuto-switch-slider"></span>
                                        </label>
                                    </div>

                                    <?php if ($is_multi_user) : ?>
                                    <div class="tebuto-switch-option">
                                        <div class="tebuto-switch-option-text">
                                            <span class="tebuto-switch-option-label"><?php esc_html_e('Schnellfilter anzeigen', 'tebuto-online-terminbuchung'); ?></span>
                                            <span class="tebuto-switch-option-desc"><?php esc_html_e('Zeigt Schnellfilter für Termine (heute, morgen, etc.)', 'tebuto-online-terminbuchung'); ?></span>
                                        </div>
                                        <label class="tebuto-switch">
                                            <input type="checkbox" name="show_quick_filters" id="show_quick_filters" value="true" <?php checked($show_quick_filters, 'true'); ?>>
                                            <span class="tebuto-switch-slider"></span>
                                </label>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Categories Section -->
                                <div class="tebuto-form-section">
                                    <h3 class="tebuto-form-section-title"><?php esc_html_e('Kategorien', 'tebuto-online-terminbuchung'); ?></h3>
                                    
                                    <div class="tebuto-form-group">
                                        <label for="categories"><?php esc_html_e('Angezeigte Kategorien', 'tebuto-online-terminbuchung'); ?></label>
                                        <div class="tebuto-categories-multiselect" id="tebuto-categories-container">
                                            <div class="tebuto-loading-categories">
                                                <span class="spinner is-active"></span>
                                                <?php esc_html_e('Kategorien werden geladen...', 'tebuto-online-terminbuchung'); ?>
                                            </div>
                                        </div>
                                        <p class="description"><?php esc_html_e('Wähle die Kategorien aus, die im Widget angezeigt werden sollen. Keine Auswahl = alle Kategorien.', 'tebuto-online-terminbuchung'); ?></p>
                                        <!-- Hidden input to store selected category IDs -->
                                        <input type="hidden" name="categories_json" id="categories_json" value="<?php echo esc_attr(wp_json_encode($selected_categories)); ?>">
                                    </div>
                                </div>

                                <!-- Custom CSS Section -->
                                <div class="tebuto-form-section">
                                    <h3 class="tebuto-form-section-title"><?php esc_html_e('Custom CSS', 'tebuto-online-terminbuchung'); ?></h3>
                                    
                                    <div class="tebuto-form-group">
                                        <label for="custom_css"><?php esc_html_e('Eigenes CSS', 'tebuto-online-terminbuchung'); ?></label>
                                        <textarea name="custom_css" id="custom_css" rows="6" class="large-text code" placeholder="<?php esc_attr_e('/* Dein CSS hier */', 'tebuto-online-terminbuchung'); ?>"><?php echo esc_textarea($custom_css); ?></textarea>
                                        <p class="description"><?php esc_html_e('Füge eigenes CSS hinzu, um das Widget-Design anzupassen. Verwende #tebuto-booking-widget als Selektor-Präfix.', 'tebuto-online-terminbuchung'); ?></p>
                                    </div>
                            </div>

                            <div class="tebuto-form-actions">
                                    <button type="submit" class="button button-primary button-hero">
                                        <span class="dashicons dashicons-saved"></span>
                                    <?php esc_html_e('Einstellungen speichern', 'tebuto-online-terminbuchung'); ?>
                                </button>
                            </div>
                        </form>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Live Preview -->
                <div class="tebuto-widget-preview-column">
                    <div class="tebuto-preview-header">
                        <h2><?php esc_html_e('Live-Vorschau', 'tebuto-online-terminbuchung'); ?></h2>
                    </div>
                    <div id="tebuto-widget-preview-container">
                        <div id="tebuto-booking-widget"></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($therapist_uuid)) : ?>
    <script>
    jQuery(document).ready(function($) {
        const selectedCategories = <?php echo wp_json_encode($selected_categories); ?>;
        const ajaxNonce = '<?php echo esc_js(wp_create_nonce('tebuto_admin')); ?>';

        // Copy shortcode function
        function tebuto_copyShortcode() {
            const shortcode = document.getElementById('tebuto-shortcode').innerText;
            navigator.clipboard.writeText(shortcode).then(function() {
                const btn = document.querySelector('.tebuto-copy-btn');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<span class="dashicons dashicons-yes"></span> <?php echo esc_js(__('Kopiert!', 'tebuto-online-terminbuchung')); ?>';
                btn.classList.add('tebuto-copied');
                setTimeout(function() {
                    btn.innerHTML = originalHtml;
                    btn.classList.remove('tebuto-copied');
                }, 2000);
            });
        }
        window.tebuto_copyShortcode = tebuto_copyShortcode;

        // Color picker sync
        const colorFields = ['primary_color', 'background_color', 'text_primary', 'text_secondary', 'border_color'];
        
        colorFields.forEach(function(field) {
            const picker = $('#' + field);
            const hex = $('#' + field + '_hex');
            
            picker.on('input change', function() {
                hex.val($(this).val().toUpperCase());
                tebuto_updatePreview();
            });
            
            hex.on('input', function() {
            let val = $(this).val();
            if (!val.startsWith('#')) val = '#' + val;
            if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                    picker.val(val);
                    hex.val(val.toUpperCase());
                    tebuto_updatePreview();
                }
            });
        });

        // Toggle switches update preview
        $('input[type="checkbox"]').on('change', function() {
            tebuto_updatePreview();
        });

        // Theme presets
        $('.tebuto-preset-btn').on('click', function() {
            const $btn = $(this);
            
            $('#primary_color').val($btn.data('primary'));
            $('#primary_color_hex').val($btn.data('primary').toUpperCase());
            
            $('#background_color').val($btn.data('background'));
            $('#background_color_hex').val($btn.data('background').toUpperCase());
            
            $('#text_primary').val($btn.data('text-primary'));
            $('#text_primary_hex').val($btn.data('text-primary').toUpperCase());
            
            $('#text_secondary').val($btn.data('text-secondary'));
            $('#text_secondary_hex').val($btn.data('text-secondary').toUpperCase());
            
            $('#border_color').val($btn.data('border-color'));
            $('#border_color_hex').val($btn.data('border-color').toUpperCase());

            // Visual feedback
            $('.tebuto-preset-btn').removeClass('active');
            $btn.addClass('active');
            
            tebuto_updatePreview();
        });

        // Debounce helper
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Load categories for multiselect
        function loadCategories() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tebuto_get_categories',
                    nonce: ajaxNonce
                },
                success: function(response) {
                    if (response.success) {
                        renderCategoriesMultiselect(response.data);
                    } else {
                        $('#tebuto-categories-container').html(
                            '<p class="tebuto-error"><?php echo esc_js(__('Kategorien konnten nicht geladen werden.', 'tebuto-online-terminbuchung')); ?></p>'
                        );
                    }
                },
                error: function() {
                    $('#tebuto-categories-container').html(
                        '<p class="tebuto-error"><?php echo esc_js(__('Verbindungsfehler beim Laden der Kategorien.', 'tebuto-online-terminbuchung')); ?></p>'
                    );
                }
            });
        }

        // Render categories as checkbox list
        function renderCategoriesMultiselect(categories) {
            const container = $('#tebuto-categories-container');
            
            if (!categories || categories.length === 0) {
                container.html('<p class="tebuto-empty"><?php echo esc_js(__('Keine Kategorien vorhanden.', 'tebuto-online-terminbuchung')); ?></p>');
                return;
            }

            let html = '<div class="tebuto-category-checkboxes">';
            
            categories.forEach(function(cat) {
                const checked = selectedCategories.includes(cat.id) ? 'checked' : '';
                html += `
                    <label class="tebuto-category-checkbox">
                        <input type="checkbox" name="categories[]" value="${cat.id}" ${checked}>
                        <span class="tebuto-category-color-dot" style="background: ${cat.color}"></span>
                        <span class="tebuto-category-name">${escapeHtml(cat.name)}</span>
                    </label>
                `;
            });
            
            html += '</div>';
            container.html(html);

            // Update preview when category selection changes
            container.find('input[type="checkbox"]').on('change', function() {
                tebuto_updatePreview();
            });
        }

        // Escape HTML helper
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Get selected category IDs
        function getSelectedCategories() {
            const selected = [];
            $('#tebuto-categories-container input[type="checkbox"]:checked').each(function() {
                selected.push($(this).val());
            });
            return selected.join(',');
        }

        // Get current form values
        function getWidgetConfig() {
            return {
                therapistUuid: '<?php echo esc_js($therapist_uuid); ?>',
                primaryColor: $('#primary_color').val(),
                backgroundColor: $('#background_color').val(),
                textPrimary: $('#text_primary').val(),
                textSecondary: $('#text_secondary').val(),
                borderColor: $('#border_color').val(),
                border: $('#border').is(':checked'),
                inheritFont: $('#inherit_font').is(':checked'),
                showQuickFilters: $('#show_quick_filters').is(':checked'),
                categories: getSelectedCategories()
            };
        }

        // Load widget script
        let widgetScript = null;

        function loadWidget() {
            const container = document.getElementById('tebuto-widget-preview-container');
            if (!container) return;

            const config = getWidgetConfig();
            
            // Clear container
            container.innerHTML = '<div id="tebuto-booking-widget"></div>';
            
            // Remove old script if exists
            if (widgetScript) {
                widgetScript.remove();
            }
            
            // Create new script with data attributes
            widgetScript = document.createElement('script');
            widgetScript.src = '<?php echo esc_url(TEBUTO_WIDGET_URL); ?>';
            widgetScript.dataset.therapistUuid = config.therapistUuid;
            widgetScript.dataset.primaryColor = config.primaryColor;
            widgetScript.dataset.backgroundColor = config.backgroundColor;
            widgetScript.dataset.textPrimary = config.textPrimary;
            widgetScript.dataset.textSecondary = config.textSecondary;
            widgetScript.dataset.borderColor = config.borderColor;
            widgetScript.dataset.border = config.border ? 'true' : 'false';
            widgetScript.dataset.inheritFont = config.inheritFont ? 'true' : 'false';
            widgetScript.dataset.showQuickFilters = config.showQuickFilters ? 'true' : 'false';
            
            if (config.categories) {
                widgetScript.dataset.categories = config.categories;
            }
            
            widgetScript.async = true;
            container.appendChild(widgetScript);
        }

        // Update preview - reload widget with new config
        const tebuto_updatePreview = debounce(function() {
            loadWidget();
        }, 300);
        window.tebuto_updatePreview = tebuto_updatePreview;

        // Manual refresh
        function tebuto_refreshPreview() {
            loadWidget();
        }
        window.tebuto_refreshPreview = tebuto_refreshPreview;

        // Custom CSS preview update
        $('#custom_css').on('input', debounce(function() {
            // For custom CSS, we'd need to inject it - for now just mark preview as potentially stale
        }, 500));

        // Initial load
        loadCategories();
        loadWidget();
    });
    </script>
    <style>
    .tebuto-widget-editor-layout {
        display: grid;
        grid-template-columns: minmax(380px, 480px) 1fr;
        gap: 24px;
        align-items: start;
    }

    @media (max-width: 1200px) {
        .tebuto-widget-editor-layout {
            grid-template-columns: 1fr;
        }
        
        .tebuto-widget-preview-column {
            order: -1;
        }
    }

    .tebuto-widget-settings-column {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .tebuto-widget-preview-column {
        position: sticky;
        top: 32px;
    }

    .tebuto-preview-header {
        margin-bottom: 16px;
    }

    .tebuto-preview-header h2 {
        margin: 0;
        font-size: 14px;
        font-weight: 600;
        color: var(--tebuto-text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    #tebuto-widget-preview-container {
        min-height: 500px;
    }

    #tebuto-widget-preview-container #tebuto-booking-widget {
        max-width: 100%;
    }

    /* Form Sections */
    .tebuto-form-section {
        margin-bottom: 28px;
        padding-bottom: 28px;
        border-bottom: 1px solid var(--tebuto-border-light);
    }

    .tebuto-form-section:last-of-type {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .tebuto-form-section-title {
        margin: 0 0 16px 0;
        font-size: 14px;
        font-weight: 600;
        color: var(--tebuto-text);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Theme Presets */
    .tebuto-theme-presets {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .tebuto-preset-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        background: var(--tebuto-bg);
        border: 2px solid var(--tebuto-border);
        border-radius: var(--tebuto-radius-sm);
        cursor: pointer;
        transition: all var(--tebuto-transition);
    }

    .tebuto-preset-btn:hover {
        border-color: var(--tebuto-primary);
        background: var(--tebuto-white);
    }

    .tebuto-preset-btn.active {
        border-color: var(--tebuto-primary);
        background: var(--tebuto-primary-subtle);
    }

    .tebuto-preset-color {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        flex-shrink: 0;
        box-shadow: 0 0 0 2px rgba(0,0,0,0.1);
    }

    .tebuto-preset-name {
        font-size: 13px;
        font-weight: 500;
        color: var(--tebuto-text);
    }

    /* Color Grid */
    .tebuto-color-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    @media (max-width: 500px) {
        .tebuto-color-grid {
            grid-template-columns: 1fr;
        }
    }

    .tebuto-color-grid .tebuto-form-group {
        margin-bottom: 0;
    }

    /* Hero Button */
    .button-hero {
        display: inline-flex !important;
        align-items: center;
        gap: 8px;
        padding: 12px 28px !important;
        font-size: 15px !important;
        height: auto !important;
    }

    .button-hero .dashicons {
        font-size: 18px;
        width: 18px;
        height: 18px;
    }

    /* Categories Multiselect */
    .tebuto-categories-multiselect {
        max-height: 250px;
        overflow-y: auto;
        border: 1px solid var(--tebuto-border);
        border-radius: var(--tebuto-radius-sm);
        background: var(--tebuto-white);
    }

    .tebuto-loading-categories {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 16px;
        color: var(--tebuto-text-secondary);
    }

    .tebuto-loading-categories .spinner {
        float: none;
        margin: 0;
    }

    .tebuto-category-checkboxes {
        padding: 8px 0;
    }

    .tebuto-category-checkbox {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        cursor: pointer;
        transition: background var(--tebuto-transition);
    }

    .tebuto-category-checkbox:hover {
        background: var(--tebuto-bg);
    }

    .tebuto-category-checkbox input[type="checkbox"] {
        width: 18px;
        height: 18px;
        margin: 0;
        flex-shrink: 0;
    }

    .tebuto-category-color-dot {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        flex-shrink: 0;
        box-shadow: 0 0 0 2px rgba(0,0,0,0.05);
    }

    .tebuto-category-name {
        font-size: 14px;
        color: var(--tebuto-text);
    }

    .tebuto-empty,
    .tebuto-error {
        padding: 16px;
        margin: 0;
        color: var(--tebuto-text-secondary);
    }

    .tebuto-error {
        color: var(--tebuto-danger);
    }

    /* Custom CSS textarea */
    #custom_css {
        font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
        font-size: 13px;
        line-height: 1.5;
        resize: vertical;
    }
    </style>
    <?php endif; ?>
    <?php
}
