<?php
/**
 * Plugin Name: Staff Picks
 * Description: Adds a custom post type 'Staff Picks'.
 * Version: 0.1
 * Author: Benjamin Kalish
 */

require_once(dirname( __FILE__ ) . '/helpers.php');
require_once(dirname( __FILE__ ) . '/widget.php');
if ( is_admin() ) {
  require_once(dirname( __FILE__ ) . '/admin.php');
}

// activation hooks
register_activation_hook(__FILE__, 'staff_picks_flush_rewrites');

// action hooks
add_action('add_meta_boxes', 'staff_picks_modify_metaboxes');
add_action('admin_head', 'staff_picks_admin_css');
add_action('admin_notices', 'staff_picks_admin_notice');
add_filter('body_class', 'staff_picks_class_names');
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
 *
 * This is registered with register_activation_hook for this file
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
    .book-jacket, #content .book-jacket-caption .book-jacket {
      max-width: 200px;
    }
    .book-jacket-caption {
      clear: left;
      float: left;
      margin-bottom: 1em;
      margin-right: 1em;
    }
    @media (max-width: 600px) {
      .book-jacket-caption, #content .book-jacket-caption .book-jacket-caption {
        clear: both;
        float: none;
        display: block;
        margin-bottom: 1em;
        margin-right: 1em;
      }
      .book-jacket, #content .book-jacket-caption .book-jacket {
        float: none;
        clear: both;
        margin: 0 auto 1em;
      }
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
 * Modifies the title.
 *
 * @wp-hook wp_title
 */
function staff_picks_modify_title($title) {
  if (staff_picks_get_title()) {
    $title = staff_picks_get_title();
    $blog_title = get_bloginfo( 'name' );
    return "$title";
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

/**
 * Addes a classname to the body by audience, for easier css styling
 * wp-hook body-class
 */
function staff_picks_class_names( $classes ) {
  global $wp_query;

  if (is_single()) {
    $post = $wp_query->get_queried_object();
    $audiences = wp_get_post_terms(
      $post->ID,
      'staff_pick_audiences',
      array('fields' => 'names')
    );
    $classes = array_merge($classes, array_map(strtolower, $audiences));
  }

  if (is_tax()) {
    $term = $wp_query->get_queried_object();
    if ( $term->taxonomy == 'staff_pick_audiences' ) {
      $classes[] = strtolower( $term->name );
    }
  }
  return $classes;
}
