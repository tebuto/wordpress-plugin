<?php
/**
 * Shared Tebuto admin UI component helpers.
 *
 * @package Tebuto
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin page slugs that use the full-height shell.
 *
 * Must be resolved in admin_body_class (before the page callback runs).
 *
 * @return array<int, string>
 */
function tebuto_ui_fullheight_pages(): array {
	return array(
		'tebuto-main',
		'tebuto-bookings',
		'tebuto-categories',
		'tebuto-seminars',
		'tebuto-shortcode',
	);
}

/**
 * Whether the current admin screen should use the full-height shell.
 *
 * @return bool
 */
function tebuto_ui_is_fullheight_screen(): bool {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page slug.
	$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	return $page !== '' && in_array( $page, tebuto_ui_fullheight_pages(), true );
}

/**
 * Append tebuto-fullheight to the admin body class on opted-in Tebuto pages.
 *
 * Runs before the page callback, so opt-in is by page slug — not a late flag.
 *
 * @param string $classes Space-separated body classes.
 * @return string
 */
function tebuto_ui_admin_body_class( string $classes ): string {
	if ( tebuto_ui_is_fullheight_screen() ) {
		$classes .= ' tebuto-fullheight';
	}
	return $classes;
}
add_filter( 'admin_body_class', 'tebuto_ui_admin_body_class' );

/**
 * Open a Tebuto admin page shell.
 *
 * @param array{title?: string, title_meta_html?: string, page_class?: string, fullheight?: bool, actions_html?: string} $args Page args.
 * @return void
 */
function tebuto_ui_page_open( array $args = array() ): void {
	$title      = isset( $args['title'] ) ? (string) $args['title'] : '';
	$title_meta = isset( $args['title_meta_html'] ) ? (string) $args['title_meta_html'] : '';
	$page_class = isset( $args['page_class'] ) ? (string) $args['page_class'] : '';
	$actions    = isset( $args['actions_html'] ) ? (string) $args['actions_html'] : '';

	$wrap_classes = array( 'wrap', 'tebuto-admin-wrap' );
	if ( $page_class !== '' ) {
		$wrap_classes[] = $page_class;
	}

	echo '<div class="' . esc_attr( implode( ' ', $wrap_classes ) ) . '">';
	echo '<div class="tebuto-header">';
	if ( $title !== '' || $title_meta !== '' ) {
		echo '<div class="tebuto-header-title">';
		if ( $title !== '' ) {
			echo '<h1>' . esc_html( $title ) . '</h1>';
		}
		if ( $title_meta !== '' ) {
			echo $title_meta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller must escape.
		}
		echo '</div>';
	}
	if ( $actions !== '' ) {
		echo '<div class="tebuto-header-actions">' . $actions . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller must escape.
	}
	echo '</div>';
}

/**
 * Close a Tebuto admin page shell.
 *
 * @return void
 */
function tebuto_ui_page_close(): void {
	echo '</div>';
}

/**
 * Build CSS class list for a Tebuto UI button.
 *
 * @param string $variant Button variant.
 * @param string $color   Button color.
 * @param string $size    Optional size.
 * @param string $extra   Extra class string.
 * @return array<int, string>
 */
function tebuto_ui_button_class_list( string $variant, string $color, string $size, string $extra ): array {
	$classes = array(
		'button',
		'tebuto-btn',
		'tebuto-btn--' . sanitize_html_class( $variant ),
		'tebuto-btn--' . sanitize_html_class( $color ),
	);
	if ( $size !== '' ) {
		$classes[] = 'tebuto-btn--' . sanitize_html_class( $size );
	}
	if ( $variant === 'solid' && $color === 'primary' ) {
		$classes[] = 'button-primary';
	}
	if ( $extra !== '' ) {
		$classes[] = $extra;
	}

	return $classes;
}

/**
 * Build HTML attribute string for a Tebuto UI button.
 *
 * @param array<string, string> $attrs   Extra attributes.
 * @param string                $onclick Legacy onclick handler.
 * @return string
 */
function tebuto_ui_button_attrs_html( array $attrs, string $onclick ): string {
	$attr_html = '';
	foreach ( $attrs as $key => $value ) {
		$attr_html .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
	}
	if ( $onclick !== '' ) {
		$attr_html .= ' onclick="' . esc_attr( $onclick ) . '"';
	}

	return $attr_html;
}

/**
 * Build inner HTML (icon + label) for a Tebuto UI button.
 *
 * @param string $icon  Optional dashicon class.
 * @param string $label Button label.
 * @return string
 */
function tebuto_ui_button_inner_html( string $icon, string $label ): string {
	$inner = '';
	if ( $icon !== '' ) {
		$inner .= '<span class="dashicons ' . esc_attr( $icon ) . '"></span> ';
	}
	$inner .= esc_html( $label );

	return $inner;
}

/**
 * Render a button (or link styled as button).
 *
 * @param array{label: string, href?: string, type?: string, variant?: string, color?: string, size?: string, class?: string, attrs?: array<string, string>, icon?: string, onclick?: string} $args Button args.
 * @return string
 */
function tebuto_ui_button( array $args ): string {
	$label   = isset( $args['label'] ) ? (string) $args['label'] : '';
	$href    = isset( $args['href'] ) ? (string) $args['href'] : '';
	$type    = isset( $args['type'] ) ? (string) $args['type'] : 'button';
	$variant = isset( $args['variant'] ) ? (string) $args['variant'] : 'solid';
	$color   = isset( $args['color'] ) ? (string) $args['color'] : 'primary';
	$size    = isset( $args['size'] ) ? (string) $args['size'] : '';
	$extra   = isset( $args['class'] ) ? (string) $args['class'] : '';
	$icon    = isset( $args['icon'] ) ? (string) $args['icon'] : '';
	$onclick = isset( $args['onclick'] ) ? (string) $args['onclick'] : '';
	$attrs   = isset( $args['attrs'] ) && is_array( $args['attrs'] ) ? $args['attrs'] : array();

	$classes    = tebuto_ui_button_class_list( $variant, $color, $size, $extra );
	$attr_html  = tebuto_ui_button_attrs_html( $attrs, $onclick );
	$inner      = tebuto_ui_button_inner_html( $icon, $label );
	$class_attr = esc_attr( implode( ' ', $classes ) );

	if ( $href !== '' ) {
		return '<a href="' . esc_url( $href ) . '" class="' . $class_attr . '"' . $attr_html . '>' . $inner . '</a>';
	}

	return '<button type="' . esc_attr( $type ) . '" class="' . $class_attr . '"' . $attr_html . '>' . $inner . '</button>';
}

/**
 * Render a badge.
 *
 * @param string $label Badge label.
 * @param string $tone  success|warning|danger|info|default|primary.
 * @return string
 */
function tebuto_ui_badge( string $label, string $tone = 'default' ): string {
	$tone = sanitize_html_class( $tone );
	return '<span class="tebuto-badge tebuto-badge-' . esc_attr( $tone ) . '">' . esc_html( $label ) . '</span>';
}

/**
 * Render an admonition / notice card.
 *
 * @param array{title?: string, body?: string, tone?: string, icon?: string, actions_html?: string, class?: string} $args Admonition args.
 * @return string
 */
function tebuto_ui_admonition( array $args ): string {
	$title   = isset( $args['title'] ) ? (string) $args['title'] : '';
	$body    = isset( $args['body'] ) ? (string) $args['body'] : '';
	$tone    = isset( $args['tone'] ) ? (string) $args['tone'] : 'info';
	$icon    = isset( $args['icon'] ) ? (string) $args['icon'] : 'dashicons-info';
	$actions = isset( $args['actions_html'] ) ? (string) $args['actions_html'] : '';
	$extra   = isset( $args['class'] ) ? (string) $args['class'] : '';

	$classes = array(
		'tebuto-admonition',
		'tebuto-admonition--' . sanitize_html_class( $tone ),
		'tebuto-card',
		'tebuto-auth-notice',
	);
	if ( $tone === 'warning' ) {
		$classes[] = 'tebuto-card-warning';
	}
	if ( $extra !== '' ) {
		$classes[] = $extra;
	}

	$html  = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';
	$html .= '<div class="tebuto-card-icon"><span class="dashicons ' . esc_attr( $icon ) . '"></span></div>';
	$html .= '<div class="tebuto-card-content">';
	if ( $title !== '' ) {
		$html .= '<h2>' . esc_html( $title ) . '</h2>';
	}
	if ( $body !== '' ) {
		$html .= '<p>' . esc_html( $body ) . '</p>';
	}
	if ( $actions !== '' ) {
		$html .= $actions; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller must escape.
	}
	$html .= '</div></div>';

	return $html;
}

/**
 * Render an empty state.
 *
 * @param array{icon?: string, title?: string, body?: string, actions_html?: string} $args Empty state args.
 * @return string
 */
function tebuto_ui_empty_state( array $args ): string {
	$icon    = isset( $args['icon'] ) ? (string) $args['icon'] : 'dashicons-info';
	$title   = isset( $args['title'] ) ? (string) $args['title'] : '';
	$body    = isset( $args['body'] ) ? (string) $args['body'] : '';
	$actions = isset( $args['actions_html'] ) ? (string) $args['actions_html'] : '';

	$html  = '<div class="tebuto-empty-state">';
	$html .= '<span class="dashicons ' . esc_attr( $icon ) . '"></span>';
	if ( $title !== '' ) {
		$html .= '<h3>' . esc_html( $title ) . '</h3>';
	}
	if ( $body !== '' ) {
		$html .= '<p>' . esc_html( $body ) . '</p>';
	}
	if ( $actions !== '' ) {
		$html .= $actions; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller must escape.
	}
	$html .= '</div>';

	return $html;
}

/**
 * Open a card.
 *
 * @param array{title?: string, class?: string, header_actions_html?: string} $args Card args.
 * @return void
 */
function tebuto_ui_card_open( array $args = array() ): void {
	$title  = isset( $args['title'] ) ? (string) $args['title'] : '';
	$extra  = isset( $args['class'] ) ? (string) $args['class'] : '';
	$header = isset( $args['header_actions_html'] ) ? (string) $args['header_actions_html'] : '';

	$classes = array( 'tebuto-card' );
	if ( $extra !== '' ) {
		$classes[] = $extra;
	}

	echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';
	if ( $title !== '' || $header !== '' ) {
		echo '<div class="tebuto-card-header">';
		if ( $title !== '' ) {
			echo '<h2>' . esc_html( $title ) . '</h2>';
		}
		if ( $header !== '' ) {
			echo $header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller must escape.
		}
		echo '</div>';
	}
	echo '<div class="tebuto-card-body">';
}

/**
 * Close a card.
 *
 * @return void
 */
function tebuto_ui_card_close(): void {
	echo '</div></div>';
}

/**
 * Render a switch row.
 *
 * @param array{name: string, id?: string, label: string, description?: string, checked?: bool, value?: string, disabled?: bool} $args Switch args.
 * @return string
 */
function tebuto_ui_switch_row( array $args ): string {
	$name        = isset( $args['name'] ) ? (string) $args['name'] : '';
	$id          = isset( $args['id'] ) ? (string) $args['id'] : $name;
	$label       = isset( $args['label'] ) ? (string) $args['label'] : '';
	$description = isset( $args['description'] ) ? (string) $args['description'] : '';
	$checked     = ! empty( $args['checked'] );
	$value       = isset( $args['value'] ) ? (string) $args['value'] : '1';
	$disabled    = ! empty( $args['disabled'] );

	$html  = '<div class="tebuto-switch-option">';
	$html .= '<div class="tebuto-switch-option-text">';
	$html .= '<span class="tebuto-switch-option-label">' . esc_html( $label ) . '</span>';
	if ( $description !== '' ) {
		$html .= '<span class="tebuto-switch-option-desc">' . esc_html( $description ) . '</span>';
	}
	$html .= '</div>';
	$html .= '<label class="tebuto-switch">';
	$html .= '<input type="checkbox" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" value="' . esc_attr( $value ) . '"' . checked( $checked, true, false ) . disabled( $disabled, true, false ) . '>';
	$html .= '<span class="tebuto-switch-slider"></span>';
	$html .= '</label></div>';

	return $html;
}

/**
 * Render a form field wrapper.
 *
 * @param array{label?: string, for?: string, description?: string, input_html: string, class?: string} $args Field args.
 * @return string
 */
function tebuto_ui_field( array $args ): string {
	$label       = isset( $args['label'] ) ? (string) $args['label'] : '';
	$for         = isset( $args['for'] ) ? (string) $args['for'] : '';
	$description = isset( $args['description'] ) ? (string) $args['description'] : '';
	$input_html  = isset( $args['input_html'] ) ? (string) $args['input_html'] : '';
	$extra       = isset( $args['class'] ) ? (string) $args['class'] : '';

	$classes = array( 'tebuto-form-group' );
	if ( $extra !== '' ) {
		$classes[] = $extra;
	}

	$html = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';
	if ( $label !== '' ) {
		$html .= '<label' . ( $for !== '' ? ' for="' . esc_attr( $for ) . '"' : '' ) . '>' . esc_html( $label ) . '</label>';
	}
	$html .= $input_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller must escape.
	if ( $description !== '' ) {
		$html .= '<p class="description">' . esc_html( $description ) . '</p>';
	}
	$html .= '</div>';

	return $html;
}

/**
 * Open a modal shell.
 *
 * @param array{id: string, title?: string, size?: string, hidden?: bool} $args Modal args.
 * @return void
 */
function tebuto_ui_modal_open( array $args ): void {
	$id     = isset( $args['id'] ) ? (string) $args['id'] : 'tebuto-modal';
	$title  = isset( $args['title'] ) ? (string) $args['title'] : '';
	$size   = isset( $args['size'] ) ? (string) $args['size'] : '';
	$hidden = ! isset( $args['hidden'] ) || $args['hidden'];

	$content_class = 'tebuto-modal-content';
	if ( $size === 'lg' ) {
		$content_class .= ' tebuto-modal-lg';
	}

	$style = $hidden ? ' style="display: none;"' : '';

	echo '<div class="tebuto-modal" id="' . esc_attr( $id ) . '"' . $style . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static style attribute.
	echo '<div class="' . esc_attr( $content_class ) . '">';
	echo '<div class="tebuto-modal-header">';
	echo '<h2>' . esc_html( $title ) . '</h2>';
	echo '<button type="button" class="tebuto-modal-close" aria-label="' . esc_attr__( 'Schließen', 'tebuto-online-terminbuchung' ) . '">&times;</button>';
	echo '</div><div class="tebuto-modal-body">';
}

/**
 * Close a modal shell (body + content + modal). Optionally include footer HTML before close.
 *
 * @param string $footer_html Optional footer inner HTML.
 * @return void
 */
function tebuto_ui_modal_close( string $footer_html = '' ): void {
	echo '</div>';
	if ( $footer_html !== '' ) {
		echo '<div class="tebuto-modal-footer">' . $footer_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller must escape.
	}
	echo '</div></div>';
}

/**
 * Render a dashboard stat card.
 *
 * @param array{number: string|int, label: string, icon?: string, tone?: string} $args Stat args.
 * @return string
 */
function tebuto_ui_stat_card( array $args ): string {
	$number = isset( $args['number'] ) ? (string) $args['number'] : '0';
	$label  = isset( $args['label'] ) ? (string) $args['label'] : '';
	$icon   = isset( $args['icon'] ) ? (string) $args['icon'] : 'dashicons-chart-bar';
	$tone   = isset( $args['tone'] ) ? (string) $args['tone'] : 'primary';

	$html  = '<div class="tebuto-stat-card">';
	$html .= '<div class="tebuto-stat-icon tebuto-stat-icon-' . esc_attr( sanitize_html_class( $tone ) ) . '">';
	$html .= '<span class="dashicons ' . esc_attr( $icon ) . '"></span></div>';
	$html .= '<div class="tebuto-stat-content">';
	$html .= '<span class="tebuto-stat-number">' . esc_html( $number ) . '</span>';
	$html .= '<span class="tebuto-stat-label">' . esc_html( $label ) . '</span>';
	$html .= '</div></div>';

	return $html;
}

/**
 * Open a responsive table wrapper + table.
 *
 * @param array{headers: array<int, string>, class?: string} $args Table args.
 * @return void
 */
function tebuto_ui_table_open( array $args ): void {
	$headers = isset( $args['headers'] ) && is_array( $args['headers'] ) ? $args['headers'] : array();
	$extra   = isset( $args['class'] ) ? (string) $args['class'] : '';

	$table_class = 'tebuto-table';
	if ( $extra !== '' ) {
		$table_class .= ' ' . $extra;
	}

	echo '<div class="tebuto-table-responsive"><table class="' . esc_attr( $table_class ) . '"><thead><tr>';
	foreach ( $headers as $header ) {
		echo '<th>' . esc_html( (string) $header ) . '</th>';
	}
	echo '</tr></thead><tbody>';
}

/**
 * Close a table.
 *
 * @return void
 */
function tebuto_ui_table_close(): void {
	echo '</tbody></table></div>';
}

/**
 * Render a color dot.
 *
 * @param string $color       Hex color.
 * @param string $extra_class Extra class.
 * @return string
 */
function tebuto_ui_color_dot( string $color, string $extra_class = 'tebuto-category-color-dot' ): string {
	return '<span class="' . esc_attr( $extra_class ) . '" style="background:' . esc_attr( $color ) . '"></span>';
}

/**
 * Queue a WordPress admin notice for the next page load / current request.
 *
 * @param string $message Notice message.
 * @param string $type    success|error|warning|info.
 * @return void
 */
function tebuto_admin_notice( string $message, string $type = 'info' ): void {
	add_action(
		'admin_notices',
		static function () use ( $message, $type ): void {
			$allowed = array( 'success', 'error', 'warning', 'info' );
			$type    = in_array( $type, $allowed, true ) ? $type : 'info';
			printf(
				'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
				esc_attr( $type ),
				esc_html( $message )
			);
		}
	);
}
