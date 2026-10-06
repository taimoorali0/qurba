<?php
/**
 * Image areas. Every photo on the site has a named slot with a recommended size.
 * Upload photos in Appearance > Customize > Gulf Website Images. Until a photo is
 * uploaded, a marked placeholder shows the slot name and the exact size to prepare.
 */
defined('ABSPATH') || exit;

function gulf_image_size($slot) {
    if ($slot === 'home-hero') { return array(1920, 1080); }
    if (substr($slot, -5) === '-hero') { return array(1920, 800); }
    if ($slot === 'about-ceo') { return array(800, 1000); }
    if ($slot === 'home-industries') { return array(1000, 1000); }
    if (strpos($slot, 'logo-') !== false) { return array(400, 200); }
    if (in_array($slot, array('rental-backhoe', 'rental-telehandler', 'rental-scissor', 'rental-forklift'), true)) { return array(800, 600); }
    return array(1200, 800);
}
function gulf_image_label($slot) {
    return ucwords(str_replace('-', ' ', $slot));
}

/** All slots, grouped by page, for the Customizer and the image guide. */
function gulf_image_slots() {
    $groups = array('home' => array('home-hero', 'home-industries'));
    for ($i = 1; $i <= 6; $i++) { $groups['home'][] = 'partner-logo-' . $i; }
    foreach (gulf_page_index() as $key => $page) {
        $data = gulf_page_data($key);
        $slots = array($data['hero_image']);
        foreach ($data['sections'] as $s) {
            if (!empty($s['image'])) { $slots[] = $s['image']; }
            if (!empty($s['en']['fleet'])) { foreach ($s['en']['fleet'] as $f) { $slots[] = $f[2]; } }
            if (!empty($s['en']['logos'])) { for ($i = 1; $i <= $s['en']['logos']; $i++) { $slots[] = 'group-logo-' . $i; } }
        }
        $groups[$key] = array_values(array_unique($slots));
    }
    return $groups;
}

/**
 * Print the image for a slot: uploaded photo > bundled fallback file > marked placeholder.
 */
function gulf_image($slot, $alt = '', $class = '', $fallback = '', $eager = false) {
    list($w, $h) = gulf_image_size($slot);
    $id = absint(get_theme_mod('gulf_img_' . str_replace('-', '_', $slot)));
    $attrs = array('class' => trim('gulf-img ' . $class), 'alt' => $alt, 'loading' => $eager ? 'eager' : 'lazy');
    if ($eager) { $attrs['fetchpriority'] = 'high'; }
    if ($id && wp_attachment_is_image($id)) {
        echo wp_get_attachment_image($id, $w >= 1600 ? 'full' : 'large', false, $attrs);
        return;
    }
    if ($fallback) {
        printf('<img class="%s" src="%s" alt="%s" width="%d" height="%d" loading="%s"%s>', esc_attr($attrs['class']), esc_url(gulf_asset($fallback)),
            esc_attr($alt), $w, $h, $eager ? 'eager' : 'lazy', $eager ? ' fetchpriority="high"' : '');
        return;
    }
    printf('<div class="gulf-placeholder %1$s" style="aspect-ratio:%2$d/%3$d" role="img" aria-label="%4$s"><span class="gulf-placeholder-tag">%5$s</span><strong dir="ltr">%2$d &times; %3$d px</strong><span dir="ltr">%6$s</span></div>',
        esc_attr($class), $w, $h, esc_attr($alt), esc_html(gulf_lang() === 'ar' ? 'مساحة صورة' : 'Image area'), esc_html(gulf_image_label($slot)));
}

add_action('customize_register', function ($wp_customize) {
    $wp_customize->add_panel('gulf_images', array('title' => 'Gulf Website Images', 'priority' => 30,
        'description' => 'Upload a photo for each image area. Use the recommended size (JPG or WebP, under 400 KB). Empty areas show a marked placeholder on the site.'));
    $wp_customize->add_section('gulf_settings', array('title' => 'Enquiry form', 'panel' => 'gulf_images', 'priority' => 1));
    $wp_customize->add_setting('gulf_enquiry_email', array('default' => GULF_EMAIL, 'sanitize_callback' => 'sanitize_email'));
    $wp_customize->add_control('gulf_enquiry_email', array('label' => 'Send enquiries to', 'section' => 'gulf_settings', 'type' => 'email'));
    $titles = array('home' => 'Homepage');
    foreach (gulf_page_index() as $key => $page) { $titles[$key] = $page['en']; }
    foreach (gulf_image_slots() as $group => $slots) {
        $wp_customize->add_section('gulf_img_' . $group, array('title' => $titles[$group] . ' images', 'panel' => 'gulf_images'));
        foreach ($slots as $slot) {
            list($w, $h) = gulf_image_size($slot);
            $setting = 'gulf_img_' . str_replace('-', '_', $slot);
            $wp_customize->add_setting($setting, array('sanitize_callback' => 'absint'));
            $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, $setting, array(
                'label' => gulf_image_label($slot), 'mime_type' => 'image', 'section' => 'gulf_img_' . $group,
                'description' => sprintf('Recommended: %d × %d px', $w, $h))));
        }
    }
});
