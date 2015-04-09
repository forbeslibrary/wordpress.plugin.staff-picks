<?php
/**
 * Admin interface for the Forbes staff_picks plugin.
 */

/**
* Save custom fields from staff_picks edit page.
*
* @wp-hook save_post
*/
function staff_picks_validate_and_save( $post_id ){
  $post =  get_post( $post_id );

  if ( $post->post_type != 'staff_picks' ) {
   return;
  }

  // Update custom field
  update_post_meta($post->ID, 'staff_pick_metadata', $_POST['staff_pick_metadata']);

  // Stop interfering if this is a draft or the post is being deleted
  if ( in_array(
    get_post_status( $post->ID ),
    array('draft', 'auto-draft', 'trash')
  )) {
     return;
  }

  // Validation
  $errors = array();

  if (isset($_POST['staff_pick_metadata'])) {
    $metadata = $_POST['staff_pick_metadata'];

    if (isset($metadata['catalog_url'])) {
      if (trim($metadata['catalog_url']) == false) {
        $errors[] = __('The catalog url field may not be blank');
      }
    } else {
      $errors[] = __('The catalog url field was missing');
    }
  }

  if ( !get_the_terms( $post->ID, 'staff_pick_reviewers' ) ) {
    $errors[] = __('You must choose a reviewer');
  } elseif ( count(get_the_terms( $post->ID, 'staff_pick_reviewers' )) > 1 ) {
    $errors[] = __('You may only choose one reviewer');
  }

  if ( !get_the_terms( $post->ID, 'staff_pick_audiences' ) ) {
    $errors[] = __('You must choose at least one audience');
  }

  if ( !get_the_terms( $post->ID, 'staff_pick_formats' ) ) {
    $errors[] = __('You must choose at least one format');
  }

  if ( !has_post_thumbnail( $post->ID ) ) {
    $errors[] = __('You must set a cover image');
  }

  if ($errors) {
    // Save the errors using the transients api
    set_transient( "staff_picks_errors_{$post->ID}", $errors, 120 );

    // we must remove this action or it will loop for ever
    remove_action('save_post', 'staff_picks_validate_and_save');

    // Change post from published to draft
    $post->post_status = 'draft';

    // update the post
    wp_update_post( $post );

    // we must add back this action
    add_action('save_post', 'staff_picks_validate_and_save');
  }

}

/**
*  Fix status message when user tries to publish an invalid staff pick.
*
* If the user hits the publish button the publish message will display even if
* we have changed the status to draft during validation. This fixes that by
* modifying the message if any errors have been queued.
*
* @wp-hook redirect_post_location
*/
function staff_picks_fix_status_message($location, $post_id) {
  //If any staff pick errors have been queued...
  if (get_transient( "staff_picks_errors_{$post->ID}" )){
    $status = get_post_status( $post_id );
    $location = add_query_arg('message', 10, $location);
  }

  return $location;
}

/**
* Adds custom CSS to admin pages.
*
* @wp-hook admin_head
*/
function staff_picks_admin_css() {
  ?>
  <style>
    .staff-picks-metadata-label {
      display: block;
    }
    .staff-picks-metadata-input {
      display: block;
      width: 100%;
    }
    #dashboard_right_now .staff_picks-count a:before,
    #dashboard_right_now .staff_picks-count span:before {
      content: "\f331";
    }
  </style>
  <?php
}

/**
 * Outputs the contents of each custom column on the staff_picks admin page.
 *
 * @wp-hook manage_staff_picks_posts_custom_column
 */
function staff_picks_custom_columns($column){
  global $post;
  $custom = get_post_custom($post->ID);
  $metadata = $staff_pick_metadata = maybe_unserialize(
    $custom['staff_pick_metadata'][0]
  );

  switch ($column) {
    case 'staff-picks-author':
      echo $metadata['author'];
      break;
    case 'staff-picks-formats':
      echo implode(', ', wp_get_post_terms($post->ID, 'staff_pick_formats', array('fields' => 'names')));
      break;
    case 'staff-picks-reviewers':
      echo implode(', ', wp_get_post_terms($post->ID, 'staff_pick_reviewers', array('fields' => 'names')));
      break;
    case 'staff-picks-audiences':
      echo implode(', ', wp_get_post_terms($post->ID, 'staff_pick_audiences', array('fields' => 'names')));
      break;
    case 'staff-picks-categories':
      echo implode(', ', wp_get_post_terms($post->ID, 'staff_pick_categories', array('fields' => 'names')));
      break;
  }
}

/**
 * Customizes the columns on the staff_picks admin page.
 *
 * @wp-hook manage_staff_picks_posts_columns
 */
function staff_picks_manage_columns($columns){
  $columns = array_merge( $columns, array(
    'title' => 'Title',
    'staff-picks-author' => 'Author',
    'staff-picks-reviewers' => 'Reviewer',
    'staff-picks-formats' => 'Format',
    'staff-picks-audiences' => 'Audience',
    'staff-picks-categories' => 'Categories',
  ));

  return $columns;
}

/**
 * Add information about staff_picks to the glance items.
 *
 * @wp-hook dashboard_glance_items
 */
function staff_picks_add_glance_items() {
  $pt_info = get_post_type_object('staff_picks');
  $num_posts = wp_count_posts('staff_picks');
  $num = number_format_i18n($num_posts->publish);
  $text = _n( $pt_info->labels->singular_name, $pt_info->labels->name, intval($num_posts->publish) ); // singular/plural text label
  echo '<li class="page-count '.$pt_info->name.'-count"><a href="edit.php?post_type=staff_picks">'.$num.' '.$text.'</li>';
}

/**
 * Restrict the addition of new taxonomy terms.
 *
 * @wp-hook pre_insert_term
 */
function staff_picks_restrict_insert_taxonomy_terms($term, $taxonomy) {
  if (
    in_array($taxonomy, array(
      'staff_pick_categories',
      'staff_pick_audiences',
      'staff_pick_formats',
      'staff_pick_reviewers'
    ))
    and !current_user_can('manage_options')
  ) {
    return new WP_Error( 'term_addition_blocked', __( 'You cannot add terms to this taxonomy' ) );
  }
  return $term;
}

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
