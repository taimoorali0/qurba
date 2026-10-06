<?php defined('ABSPATH') || exit; $gulf_key = gulf_current_key(); $gulf_services = array('engineering', 'pulp-paper', 'corrugated', 'water', 'rental'); ?><!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class('gulf-site'); ?>>
<?php wp_body_open(); ?>
<a class="gulf-skip" href="#gulf-main"><?php gulf_t('skip'); ?></a>
<div class="gulf-top"><div class="gulf-wrap"><span><?php gulf_t('top'); ?></span><span class="gulf-top-links"><a href="mailto:<?php echo esc_attr(GULF_EMAIL); ?>"><?php echo esc_html(GULF_EMAIL); ?></a><a href="tel:+966567355540" dir="ltr"><?php echo esc_html(GULF_PHONE); ?></a></span></div></div>
<header class="gulf-header"><div class="gulf-wrap gulf-header-inner">
<a class="gulf-brand" href="<?php echo esc_url(gulf_link('home')); ?>" aria-label="<?php echo esc_attr(gulf_text('brand')); ?>"><img src="<?php echo esc_url(gulf_asset('logo.png')); ?>" width="866" height="444" alt="<?php echo esc_attr(gulf_text('brand')); ?>"></a>
<button class="gulf-menu-toggle" type="button" aria-expanded="false" aria-controls="gulf-nav"><?php gulf_t('menu'); ?> <span aria-hidden="true">☰</span></button>
<nav id="gulf-nav" class="gulf-nav" aria-label="<?php echo esc_attr(gulf_text('navigation')); ?>">
<a href="<?php echo esc_url(gulf_link('home')); ?>"<?php echo is_page_template('page-gulf.php') ? ' aria-current="page"' : ''; ?>><?php gulf_t('nav_home'); ?></a>
<a href="<?php echo esc_url(gulf_link('about')); ?>"<?php echo $gulf_key === 'about' ? ' aria-current="page"' : ''; ?>><?php echo esc_html(gulf_page_name('about')); ?></a>
<div class="gulf-dropdown<?php echo in_array($gulf_key, $gulf_services, true) ? ' is-current' : ''; ?>"><button type="button" class="gulf-dropdown-toggle" aria-expanded="false"><?php gulf_t('nav_solutions'); ?> <span aria-hidden="true">▾</span></button><div class="gulf-dropdown-menu">
<?php foreach ($gulf_services as $s): ?><a href="<?php echo esc_url(gulf_link($s)); ?>"<?php echo $gulf_key === $s ? ' aria-current="page"' : ''; ?>><?php echo esc_html(gulf_page_name($s)); ?></a><?php endforeach; ?>
</div></div>
<a href="<?php echo esc_url(gulf_link('contact')); ?>"<?php echo $gulf_key === 'contact' ? ' aria-current="page"' : ''; ?>><?php echo esc_html(gulf_page_name('contact')); ?></a>
</nav>
<a class="gulf-language" data-language="<?php echo esc_attr(gulf_other_lang()); ?>" href="<?php echo esc_url(gulf_url(gulf_other_lang())); ?>" lang="<?php echo esc_attr(gulf_other_lang()); ?>"><?php echo gulf_lang() === 'ar' ? 'English' : 'العربية'; ?></a>
<a class="gulf-button gulf-header-cta" href="<?php echo esc_url(gulf_link('contact', 'enquiry')); ?>"><?php gulf_t('enquire'); ?> <span aria-hidden="true">↗</span></a>
</div></header>
<main id="gulf-main" class="gulf-main">
