<?php defined('ABSPATH') || exit; ?>
</main>
<footer class="gulf-footer"><div class="gulf-wrap gulf-footer-grid">
<div><img class="gulf-footer-logo" src="<?php echo esc_url(gulf_asset('logo.png')); ?>" width="866" height="444" alt=""><p class="gulf-footer-brand"><?php gulf_t('brand'); ?></p><p><?php gulf_t('tagline'); ?></p></div>
<div><strong><?php gulf_t('nav_solutions'); ?></strong><?php foreach (array('engineering', 'pulp-paper', 'corrugated', 'water', 'rental') as $s): ?><a href="<?php echo esc_url(gulf_link($s)); ?>"><?php echo esc_html(gulf_page_name($s)); ?></a><?php endforeach; ?></div>
<div><strong><?php gulf_t('company'); ?></strong><a href="<?php echo esc_url(gulf_link('about')); ?>"><?php echo esc_html(gulf_page_name('about')); ?></a><a href="<?php echo esc_url(gulf_link('contact')); ?>"><?php echo esc_html(gulf_page_name('contact')); ?></a><a href="<?php echo esc_url(gulf_link('contact', 'enquiry')); ?>"><?php gulf_t('submit_enquiry'); ?></a><a href="<?php echo esc_url(gulf_url(gulf_other_lang())); ?>" data-language="<?php echo esc_attr(gulf_other_lang()); ?>"><?php echo gulf_lang() === 'ar' ? 'English' : 'العربية'; ?></a></div>
<div><strong><?php gulf_t('contact_ksa'); ?></strong><a href="tel:+966567355540" dir="ltr"><?php echo esc_html(GULF_PHONE); ?></a><a href="tel:+966596060624" dir="ltr"><?php echo esc_html(GULF_PHONE_2); ?></a><a href="<?php echo esc_url(GULF_WHATSAPP); ?>" rel="noopener"><?php gulf_t('whatsapp'); ?></a><a href="mailto:<?php echo esc_attr(GULF_EMAIL); ?>"><?php echo esc_html(GULF_EMAIL); ?></a></div>
</div><div class="gulf-wrap gulf-footer-bottom"><span>&copy; <?php echo esc_html(date('Y')); ?> <?php gulf_t('brand'); ?></span><span><?php gulf_t('footer_note'); ?></span></div></footer>
<?php wp_footer(); ?></body></html>
