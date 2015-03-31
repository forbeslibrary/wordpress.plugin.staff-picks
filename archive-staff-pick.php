<?php
/*
Template Name: Archive Staff Pick
*/
$post = get_post();

get_header();
?>
<div id="container_wrap" class="container-pagewithposts equal_height right-1-col">
  <div id="content">
    <h1 class="entry-title">
      <?php echo staff_picks_get_title(); ?>
    </h1>
    <?php while ( have_posts() ): ?>
      <?php
      the_post();
      echo staff_picks_display(get_post());
      ?>
    <?php endwhile; ?>
  </div>
</div>
<div id="sidebar_wrap_right" class="right-1-col equal_height">
  <div id="sidebar_primary" class="widget-area" role="complementary">
    <h3 class="widget-title">Tags</h3>
    <?php
    $categories = '';
    if (is_tax()) {
      global $wp_query;
      $term = $wp_query->get_queried_object();
      $categories = staff_picks_get_category_ids( array(
        'taxonomy' => $term->taxonomy,
        'term_id' => $term->term_id
      ));
    }
    wp_tag_cloud( array(
      'taxonomy' => 'staff_pick_categories',
      'include' => implode(' ', $categories)
    ));
    ?>
  </div>
</div>
<?php
get_footer();
