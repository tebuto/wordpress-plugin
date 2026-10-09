<?php
/** Runs only via wp-cli against the isolated local preview. */
if (!defined('WP_CLI') || !WP_CLI || TEBUTO_API_URL !== 'http://tebuto-mock:8001') {
    throw new RuntimeException('Refusing to seed outside the isolated local preview.');
}
$credentialsPath = '/local-preview/credentials.json';
$credentials = json_decode(file_get_contents($credentialsPath), true);
foreach (['admin' => 'administrator', 'disconnected' => 'administrator', 'subscriber' => 'subscriber', 'editor' => 'editor'] as $key => $role) {
    $user = get_user_by('login', $credentials[$key]['username']);
    if (!$user) {
        $id = wp_insert_user(['user_login' => $credentials[$key]['username'], 'user_pass' => $credentials[$key]['password'], 'user_email' => $key . '@example.test', 'role' => $role]);
        if (is_wp_error($id)) WP_CLI::error($id->get_error_message());
    } else { $id = $user->ID; }
    $credentials[$key]['id'] = $id;
    update_user_meta($id, 'show_welcome_panel', '0');
    $preferencesKey = $GLOBALS['wpdb']->get_blog_prefix() . 'persisted_preferences';
    $preferences = get_user_meta($id, $preferencesKey, true) ?: [];
    $preferences['core/edit-post']['welcomeGuide'] = false;
    $preferences['core/edit-post']['welcomeGuideTemplate'] = false;
    $preferences['_modified'] = gmdate('c');
    update_user_meta($id, $preferencesKey, $preferences);
}
$id = $credentials['admin']['id'];
foreach (['access_token' => 'local-preview-access', 'refresh_token' => 'local-preview-refresh', 'therapist_id' => 1, 'tebuto_user_id' => 1, 'therapist_uuid' => '11111111-1111-4111-8111-111111111111', 'therapist_name' => 'Lokale Testpraxis', 'feature_seminars_access' => '1', 'feature_seminars_access_checked_at' => time()] as $key => $value) {
    tebuto_update_user_meta($id, $key, $value);
}
$pages = [
 'shortcode' => ['tebuto-buchung', 'Terminbuchung – lokale Vorschau', '<!-- wp:paragraph --><p>Echtes Tebuto-Widget mit lokalen Beispieldaten. Es werden keine echten Termine gebucht.</p><!-- /wp:paragraph --><!-- wp:shortcode -->[tebuto_online_terminbuchung_widget]<!-- /wp:shortcode -->'],
 'seminars' => ['tebuto-seminare', 'Seminare – lokale Vorschau', '<!-- wp:shortcode -->[tebuto_seminare_widget]<!-- /wp:shortcode -->'],
 'blocks' => ['tebuto-block-editor', 'Gutenberg – lokale Vorschau', '<!-- wp:paragraph --><p>Lokale Testseite für den echten Gutenberg-Editor.</p><!-- /wp:paragraph -->'],
];
foreach ($pages as $key => [$slug, $title, $content]) {
    $page = get_page_by_path($slug);
    $pageId = $page ? $page->ID : wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_content' => $content, 'post_author' => $id]);
    $credentials['pages'][$key] = ['id' => $pageId, 'url' => get_permalink($pageId), 'editURL' => admin_url('post.php?post=' . $pageId . '&action=edit')];
}
update_option('blog_public', '0');
update_option('show_avatars', '0');
file_put_contents($credentialsPath, json_encode($credentials, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
chmod($credentialsPath, 0600);
WP_CLI::success('Local preview users, connected account, and sample pages are ready.');
