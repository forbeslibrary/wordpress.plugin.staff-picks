<?php
/*
Template Name: Archive Staff Pick
*/
$post = get_post();

get_header();
?>
<div id="content">
  <h1>
    <?php echo staff_picks_get_title(); ?>
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
