<?php
/**
 * Enquiry form: no plugin. Each enquiry is saved in WordPress (Enquiries menu) and emailed.
 * Email delivery depends on the host's mail setup; saved enquiries are kept either way.
 */
defined('ABSPATH') || exit;

function gulf_enquiry_fields() {
    return array('name' => true, 'company' => false, 'email' => true, 'phone' => false, 'country' => false,
        'industry' => false, 'service' => false, 'message' => true);
}

add_action('init', function () {
    register_post_type('gulf_enquiry', array(
        'labels' => array('name' => 'Enquiries', 'singular_name' => 'Enquiry'),
        'public' => false, 'show_ui' => true, 'menu_icon' => 'dashicons-email-alt', 'supports' => array('title', 'editor'),
        'capability_type' => 'post', 'capabilities' => array('create_posts' => 'do_not_allow'), 'map_meta_cap' => true,
    ));
});

function gulf_enquiry_handle() {
    $back = isset($_POST['gulf_back']) ? esc_url_raw(wp_unslash($_POST['gulf_back'])) : home_url('/');
    $back = wp_validate_redirect($back, home_url('/'));
    $fail = function ($code) use ($back) {
        wp_safe_redirect(add_query_arg('gulf_sent', $code, $back) . '#enquiry');
        exit;
    };
    if (!isset($_POST['gulf_nonce']) || !wp_verify_nonce(sanitize_key($_POST['gulf_nonce']), 'gulf_enquiry')) { $fail('expired'); }
    if (!empty($_POST['website'])) { $fail('ok'); } // Honeypot: pretend success to bots.
    $ip_key = 'gulf_enq_' . md5(isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '');
    if (get_transient($ip_key)) { $fail('wait'); }

    $data = array();
    foreach (gulf_enquiry_fields() as $field => $required) {
        $raw = isset($_POST[$field]) ? wp_unslash($_POST[$field]) : '';
        $value = $field === 'message' ? sanitize_textarea_field($raw) : sanitize_text_field($raw);
        $data[$field] = mb_substr($value, 0, $field === 'message' ? 5000 : 200);
        if ($required && $data[$field] === '') { $fail('missing'); }
    }
    if (!is_email($data['email'])) { $fail('email'); }
    set_transient($ip_key, 1, 30);

    $lines = array();
    foreach ($data as $field => $value) { $lines[] = ucfirst($field) . ': ' . $value; }
    $body = implode("\n", $lines);
    wp_insert_post(array('post_type' => 'gulf_enquiry', 'post_status' => 'private',
        'post_title' => $data['name'] . ($data['company'] ? ' — ' . $data['company'] : ''), 'post_content' => $body));
    $to = get_theme_mod('gulf_enquiry_email', GULF_EMAIL);
    wp_mail(is_email($to) ? $to : GULF_EMAIL, 'Website enquiry: ' . $data['name'], $body,
        array('Reply-To: ' . str_replace(array("\r", "\n"), '', $data['name']) . ' <' . $data['email'] . '>'));
    $fail('ok');
}
add_action('admin_post_gulf_enquiry', 'gulf_enquiry_handle');
add_action('admin_post_nopriv_gulf_enquiry', 'gulf_enquiry_handle');
