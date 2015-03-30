<?php
/*
Template Name: Archive Staff Pick
*/
$post = get_post();

get_header();
?>
<div id="content">
  <h1>
    <?php if (is_post_type_archive('staff_picks')): ?>
      <?php echo __('Staff Picks'); ?>
    <?php elseif (is_tax('staff_pick_categories')): ?>
      <?php single_term_title(__('Staff Picks Category: ')); ?>
    <?php elseif (is_tax('staff_pick_audiences')): ?>
      <?php single_term_title('Staff Picks for '); ?>
    <?php elseif (is_tax('staff_pick_formats')): ?>
      <?php single_term_title(__('Staff Picks Format: ')); ?>
    <?php elseif (is_tax('staff_pick_reviewers')): ?>
      <?php single_term_title(__('Staff Picks by ')); ?>
    <?php endif; ?>
  </h1>
  <?php while ( have_posts() ): ?>
    <?php
    the_post();
    echo staff_picks_display(get_post());
    ?>
  <?php endwhile; ?>
</div>
<?php
get_footer();
