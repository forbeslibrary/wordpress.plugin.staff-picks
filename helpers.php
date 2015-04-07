<?php
/**
 * Helper functions for the Staff Picks plugin.
 */

/**
 * Returns a suitable title for an staff picks archive or taxonomy page.
 */
function staff_picks_get_title() {
  if (is_post_type_archive('staff_picks')) {
    return __('Staff Picks');
  }
  if (is_tax('staff_pick_categories')) {
    return single_term_title(__('Staff Picks Category: '), False);
  }
  if (is_tax('staff_pick_audiences')) {
    return single_term_title('Staff Picks for ', False);
  }
  if (is_tax('staff_pick_formats')) {
    return single_term_title(__('Staff Picks Format: '), False);
  }
  if (is_tax('staff_pick_reviewers')) {
    return single_term_title(__('Staff Picks by '), False);
  }
  return Null;
}

/**
 * Returns a simple HTML rendering of the staff_pick.
 */
function staff_picks_display($post) {
  $custom = get_post_custom($post->ID);
  $metadata = maybe_unserialize(
    $custom["staff_pick_metadata"][0]
  );

  ob_start();?>
  <article id="post-<?php $post->ID ?>" class="staff_picks post hentry">
  <div class="entry-content">
    <h2>
      <?php the_title(); ?>
      <?php if (!empty($metadata['author'])): ?>
        by <?php echo $metadata['author']; ?>
      <?php endif; ?>    <span class="staff_picks_format">
        [<?php the_terms( $post->ID, 'staff_pick_formats') ?>]
      </span>
    </h2>
    <a href="<?php echo $metadata['catalog_url']; ?>"
      class="wp-caption book-jacket-caption">
      <?php
      echo get_the_post_thumbnail(
        $post->ID,
        array(100,100),
        array(
          'alt' => 'book-jacket',
          'class' => 'book-jacket'
        ))
        ?>
        <p class="wp-caption-text">view/request in library catalog</p>
    </a>
    <?php echo apply_filters('the_content', $post->post_content); ?>
  </div>
  <p class="staff_picks_byline"><?php the_terms( $post->ID, 'staff_pick_reviewers', 'Reviewed by ') ?></p>
  <p><?php the_terms( $post->ID, 'staff_pick_categories', 'Tagged: ') ?></p>
  <?php if (is_user_logged_in()): ?>
    <footer class="entry-utility"><span class="edit-link"><?php edit_post_link('Edit Staff Pick'); ?></span></footer>
  <?php endif; ?>
  </article><?php
  return ob_get_clean();
}

/**
 * Returns a wp_query object for the passed shortcode attributes.
 */
function staff_picks_query($atts) {
  extract( shortcode_atts( array(
    'staff_pick_audience' => null,
  ), $atts ) );

  $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

  $query_args = array(
    'post_type' => 'staff_picks',
    'orderby' => 'title',
    'order' => 'ASC',
    'paged' => $paged,
    );

  if ($staff_pick_audience) {
    $query_args['tax_query'] = array( array('taxonomy' => 'staff-pick-category', 'field'=>'slug', 'include_children'=>FALSE, 'terms' => $staff_pick_category) );
  }

  $the_query = new WP_Query( $query_args );

  return $the_query;
}

/**
 * Get staff_pick_categories term ids associated with a specified taxonomy term
 */
function staff_picks_get_category_ids($args) {
	global $wpdb;
  $query = "
		SELECT DISTINCT terms2.term_id as tag_id
		FROM
			wp_posts as p1
			LEFT JOIN wp_term_relationships as r1 ON p1.ID = r1.object_ID
			LEFT JOIN wp_term_taxonomy as t1 ON r1.term_taxonomy_id = t1.term_taxonomy_id
			LEFT JOIN wp_terms as terms1 ON t1.term_id = terms1.term_id,

			wp_posts as p2
			LEFT JOIN wp_term_relationships as r2 ON p2.ID = r2.object_ID
			LEFT JOIN wp_term_taxonomy as t2 ON r2.term_taxonomy_id = t2.term_taxonomy_id
			LEFT JOIN wp_terms as terms2 ON t2.term_id = terms2.term_id
		WHERE
			t1.taxonomy = '".$args['taxonomy']."' AND p1.post_status = 'publish' AND terms1.term_id = '".$args['term_id']."' AND
			t2.taxonomy = 'staff_pick_categories' AND p2.post_status = 'publish'
			AND p1.ID = p2.ID
	";
	$category_ids = $wpdb->get_col($query);
	return $category_ids;
}
