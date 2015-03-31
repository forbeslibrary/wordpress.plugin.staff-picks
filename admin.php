<?php
/**
 * Admin interface for the Forbes staff_picks plugin.
 */

/**
 * Outputs the html for the staff_pick metadata box on the staff_picks edit page.
 */
function staff_picks_editbox_metadata(){
  global $post;
  if ($post->post_type !== 'staff_picks') {
    return;
  }
  $custom = get_post_custom($post->ID);
  $staff_pick_metadata = maybe_unserialize(
    $custom["staff_pick_metadata"][0]
  );
  ?>
  <label>
    <span class="staff-picks-metadata-label">Author</span>
    <input
      name="staff_pick_metadata[author]"
      class="staff-picks-metadata-input"
      value="<?php echo $staff_pick_metadata['author']; ?>"
    />
  </label>
  <label>
    <span class="staff-picks-metadata-label">Catalog URL</span>
    <input
      name="staff_pick_metadata[catalog_url]"
      class="staff-picks-metadata-input"
      value="<?php echo $staff_pick_metadata['catalog_url']; ?>"
    />
  </label><?php
}
