<?php
/* Template Name: Gulf Corporate */
defined('ABSPATH') || exit;
get_template_part('parts/header');
while (have_posts()) {
    the_post();
    if (trim(get_the_content()) || get_post_meta(get_the_ID(), '_elementor_edit_mode', true) === 'builder') {
        the_content();
    } else {
        foreach (gulf_home_sections() as $section) { echo do_shortcode('[gulf_section name="' . $section . '"]'); }
    }
}
get_template_part('parts/footer');
