<?php
/**
 * Plugin Name: Staff Picks
 * Description: Adds a custom post type 'Staff Picks'.
 * Version: 0.1
 * Author: Benjamin Kalish
 */

require_once(dirname( __FILE__ ) . '/helpers.php');
require_once(dirname( __FILE__ ) . '/shortcodes.php');
require_once(dirname( __FILE__ ) . '/admin.php');
require_once(dirname( __FILE__ ) . '/widget.php');

// activation hooks
register_activation_hook(__FILE__, 'staff_picks_flush_rewrites');

// action hooks
add_action('add_meta_boxes', 'staff_picks_modify_metaboxes');
add_action('admin_head', 'staff_picks_admin_css');
add_action('admin_notices', 'staff_picks_admin_notice');
add_action('dashboard_glance_items', 'staff_picks_add_glance_items');
add_action('edit_form_after_title', 'staff_picks_editbox_metadata');
add_action('init', 'staff_picks_init');
add_action('manage_staff_picks_posts_custom_column', 'staff_picks_custom_columns');
add_action('pre_insert_term', 'staff_picks_restrict_insert_taxonomy_terms');
add_action('save_post', 'staff_picks_validate_and_save');
add_action('widgets_init', 'staff_picks_register_widgets');
add_action('wp_head', 'staff_picks_public_css');

// filter hooks
add_filter('archive_template', 'staff_picks_archive_template');
add_filter('manage_staff_picks_posts_columns', 'staff_picks_manage_columns');
add_filter('redirect_post_location','staff_picks_fix_status_message');
add_filter('single_template', 'staff_picks_single_template');
add_filter('wp_title', 'staff_picks_modify_title');

// shortcode hooks
add_shortcode('staff_picks_list', 'staff_picks_list_shortcode_handler');

/**
 * Flush rewrite rules on plugin activation
 */
function staff_picks_flush_rewrites() {
  staff_picks_init();
  flush_rewrite_rules();
}


/**
 * Registers the custom post type staff_picks and the custom taxonomies.
 *
 * @wp-hook init
 */
function staff_picks_init() {
  $labels = array(
    'name' => _x('Staff Picks', 'post type general name'),
    'singular_name' => _x('Staff Pick', 'post type singular name'),
    'add_new' => _x('Add New', 'portfolio item'),
    'add_new_item' => __('Add New Staff Pick'),
    'edit_item' => __('Edit Staff Pick'),
    'new_item' => __('New Staff Pick'),
    'view_item' => __('View Staff Pick'),
    'search_items' => __('Search Staff Picks'),
    'not_found' =>  __('Nothing found'),
    'not_found_in_trash' => __('Nothing found in Trash'),
    'parent_item_colon' => ''
  );

  $args = array(
    'labels' => $labels,
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true,
    'query_var' => true,
    'rewrite' =>  array('with_front' => False),
    'capability_type' => 'post',
    'has_archive' => true,
    'hierarchical' => false,
    'menu_icon' => 'dashicons-book-alt',
    'menu_position' => 5, // admin menu appears after Posts but before Media
    'supports' => array('title','editor','revisions','thumbnail')
  );

  register_post_type( 'staff_picks' , $args );

  // We will register several taxonomies with the same capabilities
  $taxonomy_capabilities = array(
    'manage_terms' => 'manage_options', //by default only admin
    'edit_terms' => 'manage_options',
    'delete_terms' => 'manage_options',
    'assign_terms' => 'edit_posts'  // means administrator', 'editor', 'author', 'contributor'
  );

  register_taxonomy(
    'staff_pick_audiences',
    'staff_picks',
    array(
      'label' => 'Audiences',
      'labels' => array(
        'singular_label' => 'Audience',
        'add_new_item' => 'Add New Audience',
        'edit_item' => 'Edit Audience',
      ),
      'hierarchical' => True,
      'show_ui' => True,
      'rewrite' => array('with_front' => False),
      'capabilities' => $taxonomy_capabilities
    )
  );

  register_taxonomy(
    'staff_pick_formats',
    'staff_picks',
    array(
      'label' => 'Formats',
      'labels' => array(
        'singular_label' => 'Format',
        'add_new_item' => 'Add New Format',
        'edit_item' => 'Edit Format',
      ),
      'hierarchical' => True,
      'show_ui' => True,
      'rewrite' => array('with_front' => False),
      'capabilities' => $taxonomy_capabilities
    )
  );

  register_taxonomy(
    'staff_pick_reviewers',
    'staff_picks',
    array(
      'label' => 'Reviewers',
      'labels' => array(
        'singular_label' => 'Reviewer',
        'add_new_item' => 'Add New Reviewer',
        'edit_item' => 'Edit Reviewer',
      ),
      'hierarchical' => True,
      'show_ui' => True,
      'rewrite' => array('with_front' => False),
      'capabilities' => $taxonomy_capabilities
    )
  );

  register_taxonomy(
    'staff_pick_categories',
    'staff_picks',
    array(
      'label' => 'Categories',
      'labels' => array(
        'singular_label' => 'Category',
        'add_new_item' => 'Add New Category',
        'edit_item' => 'Edit Category',
      ),
      'hierarchical' => False,
      'show_ui' => True,
      'rewrite' => array('with_front' => False),
      'capabilities' => $taxonomy_capabilities
    )
  );
}

/**
 * The Weaver II theme adds a giant meta box that isn't much help with custom
 * post types. This code removes that box from staff pick edit pages and changes
 * the featured image box name and placement.
 *
 * @wp-hook add_meta_boxes
 */
function staff_picks_modify_metaboxes() {
  remove_meta_box('wii_post-box2', 'staff_picks', 'normal');
  remove_meta_box( 'postimagediv', 'staff_picks', 'side' );
  add_meta_box( 'postimagediv', __('Cover Image'), 'post_thumbnail_meta_box', 'staff_picks', 'side', 'high' );
}


/**
 * Displays admin notices such as validation errors
 *
 * @wp-hook admin_notices
 */
function staff_picks_admin_notice() {
  global $post;

  $errors = get_transient( "staff_picks_{$post->ID}" );
  foreach ($errors as $error): ?>
    <div class="error">
      <p><?php echo $error; ?></p>
    </div>
    <?php
  endforeach;
  delete_transient( "staff_picks_{$post->ID}" );
}

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

    if (isset($metadata['author'])) {
        if (trim($metadata['author']) == false) {
        array_push($errors, __('The author field may not be blank'));
      }
    } else {
      array_push($errors, __('The author field was missing'));
    }

    if (isset($metadata['catalog_url'])) {
        if (trim($metadata['catalog_url']) == false) {
        array_push($errors, __('The catalog url field may not be blank'));
      }
    } else {
      array_push($errors, __('The catalog url field was missing'));
    }
  }

  if ( !get_the_terms( $post->ID, 'staff_pick_reviewers' ) ) {
   array_push($errors, __('You must choose a reviewer'));
  } elseif ( count(get_the_terms( $post->ID, 'staff_pick_reviewers' )) > 1 ) {
    array_push($errors, __('You may only choose one reviewer'));
  }

  if ( !get_the_terms( $post->ID, 'staff_pick_audiences' ) ) {
   array_push($errors, __('You must choose at least one audience'));
  }

  if ( !get_the_terms( $post->ID, 'staff_pick_formats' ) ) {
   array_push($errors, __('You must choose at least one format'));
  }

  if ( !has_post_thumbnail( $post->ID ) ) {
    array_push($errors, __('You must set a cover image'));
  }

  if ($errors) {
    // Save the errors using the transients api
    set_transient( "staff_picks_{$post->ID}", $errors, 120 );

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
 * we have changed the status to draft during validation. This fixes that.
 *
 * @wp-hook redirect_post_location
 */
function staff_picks_fix_status_message($location, $post_id){
    //If post was published...
    if (isset($_POST['publish'])){
        //obtain current post status
        $status = get_post_status( $post_id );

        //The post was 'published', but if it is still a draft, display draft message (10).
        if($status=='draft')
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
    #dashboard_right_now .staff_picks-count span:before  {
      content: "\f331";
    }
  </style>
  <?php
}

/**
 * Adds custom CSS to public pages.
 *
 * @wp-hook wp_head
 */
function staff_picks_public_css() {
  ?>
  <style>
    .staff_picks {
      clear: both;
    }
    .staff_picks_format {
      font-size:smaller;
    }
    .staff_picks_format a {
      color: inherit;
    }
    .staff_picks_byline {
      font-style: italic;
    }
    .staff_picks_byline:before {
      content: " — ";
    }
    .book-jacket, #content .wp-caption .book-jacket {
      max-width: 200px;
    }
    .staff_picks_widget_image {
      border-radius: 1em;
      width: 48%;
      vertical-align: middle;
      margin: 1px 1% 1px 0;
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
 * Use a special template for showing a single staff pick on a page.
 *
 * @wp-hook single_template
 */
function staff_picks_single_template($template){
  global $post;

  if ($post->post_type == 'staff_picks') {
     $template = dirname( __FILE__ ) . '/single-staff-pick.php';
  }
  return $template;
}

/**
 * Use a special template for showing a staff pick archive pages
 *
 * @wp-hook single_template
 */
function staff_picks_archive_template($template){
  global $post;

  if (is_post_type_archive('staff_picks')
    or is_tax('staff_pick_categories')
    or is_tax('staff_pick_audiences')
    or is_tax('staff_pick_formats')
    or is_tax('staff_pick_reviewers')
  ) {
     $template = dirname( __FILE__ ) . '/archive-staff-pick.php';
  }
  return $template;
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
 * Modifies the title.
 *
 * @wp-hook wp_title
 */
function staff_picks_modify_title($title, $sep) {
  if (staff_picks_get_title()) {
    if (!$sep) {
      $sep = '|';
    }
    $title = staff_picks_get_title();
    $blog_title = get_bloginfo( 'name' );
    return "$title $sep $blog_title";
  }
  return $title;
}

/**
 * Initializes widgets.
 *
 * @wp-hook widgets_init
 */
function staff_picks_register_widgets() {
  register_widget( 'Staff_Picks_Widget' );
}
