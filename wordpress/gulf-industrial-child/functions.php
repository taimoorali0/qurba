<?php
defined('ABSPATH') || exit;

define('GULF_VERSION', '2.0.0');
define('GULF_PHONE', '+966 56 735 5540');
define('GULF_PHONE_2', '+966 59 606 0624');
define('GULF_EMAIL', 'info@gulfindustrialsols.com');
define('GULF_WHATSAPP', 'https://wa.me/966567355540');

require_once get_stylesheet_directory() . '/inc/images.php';
require_once get_stylesheet_directory() . '/inc/enquiry.php';

/* ---------------------------------------------------------------- Language */
function gulf_lang() {
    $lang = isset($_GET['lang']) && is_string($_GET['lang']) ? sanitize_key(wp_unslash($_GET['lang'])) : 'en';
    return $lang === 'ar' ? 'ar' : 'en';
}
function gulf_other_lang() { return gulf_lang() === 'ar' ? 'en' : 'ar'; }
function gulf_text($key) {
    static $catalogues = array();
    $lang = gulf_lang();
    if (!isset($catalogues[$lang])) {
        $catalogues[$lang] = json_decode(file_get_contents(get_stylesheet_directory() . '/languages/' . $lang . '.json'), true) ?: array();
    }
    return isset($catalogues[$lang][$key]) ? $catalogues[$lang][$key] : $key;
}
function gulf_t($key) { echo esc_html(gulf_text($key)); }
function gulf_asset($file) { return get_stylesheet_directory_uri() . '/assets/' . $file; }
function gulf_url($lang, $url = null) {
    return add_query_arg('lang', $lang, $url ? $url : get_permalink());
}

/* ---------------------------------------------------------------- Pages */
function gulf_page_index() {
    static $index = null;
    if ($index === null) {
        $index = json_decode(file_get_contents(get_stylesheet_directory() . '/languages/pages/index.json'), true) ?: array();
    }
    return $index;
}
function gulf_page_data($key) {
    static $cache = array();
    if (!isset($cache[$key])) {
        $file = get_stylesheet_directory() . '/languages/pages/' . sanitize_file_name($key) . '.json';
        $cache[$key] = is_readable($file) ? (json_decode(file_get_contents($file), true) ?: array()) : array();
    }
    return $cache[$key];
}
function gulf_page_id($key) {
    $ids = get_option('gulf_page_ids', array());
    if (!empty($ids[$key]) && get_post_status($ids[$key])) { return (int) $ids[$key]; }
    $found = get_posts(array('post_type' => 'page', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids',
        'meta_key' => '_gulf_page', 'meta_value' => $key));
    return $found ? (int) $found[0] : 0;
}
/** Link to one of the site's pages ('home' or a key from index.json), in the current language. */
function gulf_link($key, $anchor = '') {
    if ($key === 'home') {
        $url = home_url('/');
    } else {
        $id = gulf_page_id($key);
        $index = gulf_page_index();
        $url = $id ? get_permalink($id) : home_url('/' . (isset($index[$key]) ? $index[$key]['slug'] : $key) . '/');
    }
    return add_query_arg('lang', gulf_lang(), $url) . ($anchor ? '#' . $anchor : '');
}
function gulf_page_name($key) {
    $index = gulf_page_index();
    return isset($index[$key]) ? $index[$key][gulf_lang()] : $key;
}
function gulf_current_key() {
    return is_singular('page') ? (string) get_post_meta(get_queried_object_id(), '_gulf_page', true) : '';
}
function gulf_enabled() { return is_page_template('page-gulf.php') || is_page_template('page-gulf-inner.php'); }

/* Create the inner pages once (published, Gulf Inner Page template). Existing pages are never modified. */
add_action('admin_init', function () {
    if (get_option('gulf_pages_version') === GULF_VERSION || !current_user_can('publish_pages')) { return; }
    $ids = get_option('gulf_page_ids', array());
    foreach (gulf_page_index() as $key => $page) {
        if (gulf_page_id($key)) { $ids[$key] = gulf_page_id($key); continue; }
        $id = wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_title' => $page['en'],
            'post_name' => $page['slug'], 'post_content' => ''), true);
        if (is_wp_error($id)) { continue; }
        update_post_meta($id, '_gulf_page', $key);
        update_post_meta($id, '_wp_page_template', 'page-gulf-inner.php');
        $ids[$key] = $id;
    }
    update_option('gulf_page_ids', $ids);
    update_option('gulf_pages_version', GULF_VERSION);
    set_transient('gulf_pages_notice', 1, 300);
});
add_action('admin_notices', function () {
    if (!get_transient('gulf_pages_notice')) { return; }
    delete_transient('gulf_pages_notice');
    echo '<div class="notice notice-success is-dismissible"><p><strong>Gulf Industrial:</strong> website pages are ready under Pages (About Us, Engineering Services, Pulp &amp; Paper, Corrugated Packaging, Water &amp; Wastewater, Rental Services, Contact Us). Upload photos in Appearance &rarr; Customize &rarr; Gulf Website Images.</p></div>';
});

/* ---------------------------------------------------------------- Head, assets */
add_filter('language_attributes', function ($attrs) {
    return gulf_enabled() ? 'lang="' . gulf_lang() . '" dir="' . (gulf_lang() === 'ar' ? 'rtl' : 'ltr') . '"' : $attrs;
});
add_filter('document_title_parts', function ($parts) {
    if (!gulf_enabled()) { return $parts; }
    $key = gulf_current_key();
    if ($key) {
        $parts['title'] = gulf_page_name($key);
        $parts['site'] = gulf_text('brand');
    } else {
        $parts['title'] = gulf_text('brand');
    }
    return $parts;
});
add_action('wp_enqueue_scripts', function () {
    if (!gulf_enabled()) { return; }
    $deps = wp_style_is('hello-elementor', 'registered') ? array('hello-elementor') : array();
    wp_enqueue_style('gulf-corporate', gulf_asset('site.css'), $deps, GULF_VERSION);
    wp_enqueue_script('gulf-corporate', gulf_asset('site.js'), array(), GULF_VERSION, true);
    wp_localize_script('gulf-corporate', 'gulfConfig', array(
        'endpoint' => rest_url('gulf/v1/language'),
        'explicit' => isset($_GET['lang']) && is_string($_GET['lang']) && in_array($_GET['lang'], array('en', 'ar'), true),
        'editor' => is_user_logged_in() || isset($_GET['elementor-preview']),
        'lang' => gulf_lang(),
    ));
}, 20);
add_action('rest_api_init', function () {
    register_rest_route('gulf/v1', '/language', array(
        'methods' => 'GET', 'permission_callback' => '__return_true',
        'callback' => function () {
            // Enable only after the origin is restricted to trusted Cloudflare traffic.
            $country = defined('GULF_TRUST_CF_COUNTRY') && GULF_TRUST_CF_COUNTRY && isset($_SERVER['HTTP_CF_IPCOUNTRY'])
                ? strtoupper(sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_IPCOUNTRY']))) : '';
            $response = new WP_REST_Response(array('lang' => in_array($country, array('SA','AE','QA','KW','BH','OM'), true) ? 'ar' : 'en'));
            $response->header('Cache-Control', 'private, no-store, max-age=0');
            return $response;
        },
    ));
});
add_shortcode('gulf_section', function ($atts) {
    $atts = shortcode_atts(array('name' => ''), $atts);
    if (!in_array($atts['name'], gulf_home_sections(), true)) { return ''; }
    ob_start();
    $section = $atts['name'];
    include get_stylesheet_directory() . '/sections.php';
    return ob_get_clean();
});
function gulf_home_sections() {
    return array('hero', 'intro', 'vision', 'why', 'solutions', 'industries', 'partners', 'process', 'contact');
}
add_action('wp_head', function () {
    if (!gulf_enabled()) { return; }
    foreach (array('en', 'ar') as $lang) {
        echo '<link rel="alternate" hreflang="' . esc_attr($lang) . '" href="' . esc_url(gulf_url($lang)) . '">' . "\n";
    }
    echo '<link rel="alternate" hreflang="x-default" href="' . esc_url(get_permalink()) . '">' . "\n";
});
add_filter('get_canonical_url', function ($url) {
    return gulf_enabled() ? gulf_url(gulf_lang()) : $url;
});
