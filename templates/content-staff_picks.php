<?php
/*
Template Name: Content Staff Picks
Description: A partial tempalte used to display staff picks content.
*/
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
      'medium',
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
