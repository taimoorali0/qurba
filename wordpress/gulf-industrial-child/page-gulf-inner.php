<?php
/* Template Name: Gulf Inner Page */
defined('ABSPATH') || exit;
get_template_part('parts/header');

$gulf_key = gulf_current_key();
$gulf_page = $gulf_key ? gulf_page_data($gulf_key) : array();
$lang = gulf_lang();

if (!$gulf_page) {
    while (have_posts()) { the_post(); echo '<div class="gulf-wrap gulf-section">'; the_content(); echo '</div>'; }
    get_template_part('parts/footer');
    return;
}
$hero = $gulf_page[$lang];

$paras = function ($items) { foreach ((array) $items as $p) { echo '<p>' . esc_html($p) . '</p>'; } };
$tags = function ($items) {
    if (!$items) { return; }
    echo '<ul class="gulf-tags">';
    foreach ($items as $t) { echo '<li>' . esc_html($t) . '</li>'; }
    echo '</ul>';
};
$list = function ($items) {
    if (!$items) { return; }
    echo '<ul class="gulf-check-list">';
    foreach ($items as $t) { echo '<li>' . esc_html($t) . '</li>'; }
    echo '</ul>';
};
$heading = function ($c) {
    if (!empty($c['eyebrow'])) { echo '<p class="gulf-eyebrow">' . esc_html($c['eyebrow']) . '</p>'; }
    if (!empty($c['title'])) { echo '<h2>' . esc_html($c['title']) . '</h2>'; }
};
?>
<section class="gulf-page-hero">
  <?php gulf_image($gulf_page['hero_image'], $hero['title'], 'gulf-page-hero-image', '', true); ?>
  <div class="gulf-wrap gulf-page-hero-content">
    <nav class="gulf-crumbs" aria-label="Breadcrumb"><a href="<?php echo esc_url(gulf_link('home')); ?>"><?php gulf_t('nav_home'); ?></a> <span aria-hidden="true">/</span> <span><?php echo esc_html($hero['nav']); ?></span></nav>
    <p class="gulf-eyebrow"><?php echo esc_html($hero['eyebrow']); ?></p>
    <h1><?php echo esc_html($hero['title']); ?></h1>
    <p class="gulf-hero-line"><?php echo esc_html($hero['line']); ?></p>
    <?php if (!empty($hero['text'])): ?><p class="gulf-hero-description"><?php echo esc_html($hero['text']); ?></p><?php endif; ?>
    <div class="gulf-actions"><a class="gulf-button gulf-orange" href="<?php echo esc_url(gulf_link('contact', 'enquiry')); ?>"><?php gulf_t('discuss'); ?> <span aria-hidden="true">↗</span></a><a class="gulf-text-link" href="tel:+966567355540"><?php gulf_t('contact_team'); ?> <span aria-hidden="true">↗</span></a></div>
  </div>
</section>
<?php
foreach ($gulf_page['sections'] as $i => $s):
    $c = $s[$lang];
    $tone = isset($s['tone']) ? ' gulf-tone-' . $s['tone'] : '';
    $type = $s['type'];

    if ($type === 'cta'): ?>
<section class="gulf-contact"><div class="gulf-wrap gulf-contact-grid"><div><p class="gulf-eyebrow"><?php gulf_t('contact_eyebrow'); ?></p><h2><?php echo esc_html($c['title']); ?></h2><?php $paras($c['body']); ?></div>
<div class="gulf-contact-actions"><a class="gulf-button gulf-orange" href="<?php echo esc_url(gulf_link('contact', 'enquiry')); ?>"><?php gulf_t('submit_enquiry'); ?> <span aria-hidden="true">↗</span></a><a class="gulf-button gulf-ghost" href="<?php echo esc_url(GULF_WHATSAPP); ?>" rel="noopener"><?php gulf_t('contact_team'); ?> <span aria-hidden="true">↗</span></a><a class="gulf-contact-phone" href="tel:+966567355540" dir="ltr"><?php echo esc_html(GULF_PHONE); ?></a></div></div></section>
<?php continue; endif;

    if ($type === 'form'): $status = isset($_GET['gulf_sent']) ? sanitize_key($_GET['gulf_sent']) : ''; ?>
<section class="gulf-section" id="enquiry"><div class="gulf-wrap gulf-form-grid">
  <div><?php $heading($c); $paras($c['body']); ?>
    <?php if ($status): ?><p class="gulf-notice gulf-notice-<?php echo $status === 'ok' ? 'ok' : 'error'; ?>" role="status"><?php gulf_t('form_' . (in_array($status, array('ok', 'missing', 'email', 'wait', 'expired'), true) ? $status : 'expired')); ?></p><?php endif; ?>
    <form class="gulf-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="gulf_enquiry"><?php wp_nonce_field('gulf_enquiry', 'gulf_nonce', false); ?>
      <input type="hidden" name="gulf_back" value="<?php echo esc_url(gulf_link('contact')); ?>">
      <p class="gulf-hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>
      <?php foreach (gulf_enquiry_fields() as $field => $required):
          $label = gulf_text('field_' . $field) . ($required ? ' *' : ''); ?>
        <label class="gulf-field<?php echo $field === 'message' ? ' gulf-field-wide' : ''; ?>"><span><?php echo esc_html($label); ?></span>
        <?php if ($field === 'message'): ?><textarea name="message" rows="6" maxlength="5000"<?php echo $required ? ' required' : ''; ?>></textarea>
        <?php elseif ($field === 'service'): ?><select name="service"><option value=""><?php gulf_t('field_choose'); ?></option><?php foreach (array('engineering', 'pulp-paper', 'corrugated', 'water', 'rental') as $o): ?><option><?php echo esc_html(gulf_page_name($o)); ?></option><?php endforeach; ?><option><?php gulf_t('field_other'); ?></option></select>
        <?php else: ?><input type="<?php echo $field === 'email' ? 'email' : ($field === 'phone' ? 'tel' : 'text'); ?>" name="<?php echo esc_attr($field); ?>" maxlength="200"<?php echo $required ? ' required' : ''; ?><?php echo $field === 'email' || $field === 'phone' ? ' dir="ltr"' : ''; ?> autocomplete="<?php echo esc_attr(array('name' => 'name', 'company' => 'organization', 'email' => 'email', 'phone' => 'tel', 'country' => 'country-name', 'industry' => 'off')[$field]); ?>">
        <?php endif; ?></label>
      <?php endforeach; ?>
      <button class="gulf-button gulf-orange" type="submit"><?php gulf_t('submit_enquiry'); ?> <span aria-hidden="true">↗</span></button>
    </form>
  </div>
  <aside class="gulf-contact-card">
    <h3><?php echo esc_html($c['ksa_title']); ?></h3><p><?php echo esc_html($c['ksa_text']); ?></p>
    <dl><dt><?php gulf_t('whatsapp_call'); ?></dt><dd><a href="tel:+966567355540" dir="ltr"><?php echo esc_html(GULF_PHONE); ?></a></dd>
    <dt><?php gulf_t('phone'); ?></dt><dd><a href="tel:+966596060624" dir="ltr"><?php echo esc_html(GULF_PHONE_2); ?></a></dd>
    <dt><?php gulf_t('field_email'); ?></dt><dd><a href="mailto:<?php echo esc_attr(GULF_EMAIL); ?>"><?php echo esc_html(GULF_EMAIL); ?></a></dd></dl>
    <hr><h3><?php echo esc_html($c['help_title']); ?></h3><p><?php echo esc_html($c['help_text']); ?></p>
    <a class="gulf-button gulf-orange" href="<?php echo esc_url(GULF_WHATSAPP); ?>" rel="noopener"><?php gulf_t('whatsapp'); ?> <span aria-hidden="true">↗</span></a>
  </aside>
</div></section>
<?php continue; endif;

    if ($type === 'offices'): ?>
<section class="gulf-section<?php echo esc_attr($tone); ?>"><div class="gulf-wrap"><?php $heading($c); ?>
  <div class="gulf-offices"><?php foreach ($c['offices'] as $o): ?><article class="gulf-card"><p class="gulf-card-kicker"><?php echo esc_html($o[0]); ?></p><h3 dir="auto"><?php echo esc_html($o[1]); ?></h3><p dir="auto" class="gulf-address"><?php echo esc_html($o[2]); ?></p><?php if ($o[3]): ?><p dir="ltr"><?php echo esc_html($o[3]); ?></p><?php endif; ?><a href="mailto:<?php echo esc_attr(GULF_EMAIL); ?>"><?php echo esc_html(GULF_EMAIL); ?></a></article><?php endforeach; ?></div>
  <?php if (!empty($s['image'])): ?><div class="gulf-wide-media"><?php gulf_image($s['image'], $c['title']); ?></div><?php endif; ?>
</div></section>
<?php continue; endif;

    if ($type === 'ceo'): ?>
<section class="gulf-section<?php echo esc_attr($tone); ?>"><div class="gulf-wrap gulf-ceo-grid">
  <div class="gulf-ceo-photo"><?php gulf_image($s['image'], $c['note']); ?></div>
  <div><?php $heading($c); ?><blockquote class="gulf-quote">“<?php echo esc_html($c['quote']); ?>”</blockquote><?php $paras($c['body']); ?><p class="gulf-signature"><?php echo esc_html($c['note']); ?></p></div>
</div></section>
<?php continue; endif;

    if ($type === 'fleet'): ?>
<section class="gulf-section<?php echo esc_attr($tone); ?>"><div class="gulf-wrap"><?php $heading($c); ?>
  <div class="gulf-fleet"><?php foreach ($c['fleet'] as $f): ?><article class="gulf-card gulf-fleet-card"><?php gulf_image($f[2], $f[0]); ?><div><h3><?php echo esc_html($f[0]); ?></h3><p dir="ltr"><?php echo esc_html($f[1]); ?></p></div></article><?php endforeach; ?></div>
  <?php if (!empty($c['after'])): ?><p class="gulf-footnote"><?php echo esc_html($c['after'][0]); ?></p><?php endif; ?>
</div></section>
<?php continue; endif;

    if ($type === 'logos'): ?>
<section class="gulf-section<?php echo esc_attr($tone); ?>"><div class="gulf-wrap"><div class="gulf-section-heading"><div><?php $heading($c); ?></div><?php $paras($c['body']); ?></div>
  <div class="gulf-logos"><?php for ($l = 1; $l <= $c['logos']; $l++) { gulf_image('group-logo-' . $l, ''); } ?></div>
</div></section>
<?php continue; endif;

    if ($type === 'cards'): ?>
<section class="gulf-section<?php echo esc_attr($tone); ?>"><div class="gulf-wrap">
  <div class="gulf-section-heading"><div><?php $heading($c); ?></div><?php $paras(isset($c['body']) ? $c['body'] : array()); ?></div>
  <?php if (!empty($s['image'])): ?><div class="gulf-wide-media"><?php gulf_image($s['image'], $c['title']); ?></div><?php endif; ?>
  <div class="gulf-cards gulf-cards-<?php echo count($c['cards']) % 3 === 0 || count($c['cards']) === 5 ? '3' : '2'; ?>">
  <?php foreach ($c['cards'] as $n => $card): ?><article class="gulf-card"><span class="gulf-card-num"><?php echo esc_html(sprintf('%02d', $n + 1)); ?></span><h3><?php echo esc_html($card[0]); ?></h3><p><?php echo esc_html($card[1]); ?></p></article><?php endforeach; ?>
  </div>
  <?php if (!empty($c['after'])): ?><p class="gulf-footnote"><?php echo esc_html($c['after'][0]); ?></p><?php endif; ?>
</div></section>
<?php continue; endif;

    // text and list sections
    $has_image = !empty($s['image']);
    $classes = 'gulf-section' . $tone . ($type === 'list' ? ' gulf-list-section' : '');
    ?>
<section class="<?php echo esc_attr($classes); ?>"><div class="gulf-wrap <?php echo $has_image ? 'gulf-split' . (!empty($s['flip']) ? ' gulf-split-flip' : '') : ($type === 'list' ? 'gulf-list-grid' : 'gulf-narrow'); ?>">
  <?php if ($has_image): ?><div class="gulf-split-media"><?php gulf_image($s['image'], $c['title']); ?></div><?php endif; ?>
  <div class="gulf-split-text">
    <?php if ($type === 'list' && !$has_image): ?><div><?php $heading($c); $paras(isset($c['body']) ? $c['body'] : array()); ?></div><div><?php else: $heading($c); $paras(isset($c['body']) ? $c['body'] : array()); endif; ?>
    <?php $tags(isset($c['tags']) ? $c['tags'] : array()); ?>
    <?php $paras(isset($c['lead']) ? $c['lead'] : array()); $list(isset($c['list']) ? $c['list'] : array()); $paras(isset($c['after']) ? $c['after'] : array()); ?>
    <?php if (!empty($c['note'])): ?><p class="gulf-note"><?php echo esc_html($c['note']); ?></p><?php endif; ?>
    <?php if ($type === 'list' && !$has_image): ?></div><?php endif; ?>
  </div>
</div></section>
<?php endforeach;

get_template_part('parts/footer');
