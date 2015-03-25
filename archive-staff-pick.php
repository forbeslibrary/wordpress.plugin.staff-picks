<?php
/*
Template Name: Archive Staff Pick
*/
$post = get_post();

get_header();
?>
<div id="content">test
<?php while ( have_posts() ): ?>
  <?php
  the_post();
  echo staff_picks_display(get_post());
  ?>
<?php endwhile; ?>
</div>
<?php
get_footer();
