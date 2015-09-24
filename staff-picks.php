<?php
/**
 * Plugin Name: Staff Picks
 * Plugin URI: https://github.com/forbeslibrary/wordpress.plugin.staff-picks
 * Description: Adds a custom post type 'Staff Picks'.
 * Version: 1.1.1
 * Author: Benjamin Kalish
 * Author URI: https://github.com/bkalish
 * License: GNU General Public License v2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

class Staff_Picks_Plugin {
  // The post type (plural), in Upper_Case_With_Underscores
  const POST_TYPE_UPPER = 'Staff_Picks';

  // The post type (plural), in lower_case_with_underscores
  const POST_TYPE = 'staff_picks';

  // The post type (singular), in lower_case_with_underscores
  const POST_TYPE_SINGULAR = 'staff_pick';

  public function __construct() {
    $data_file = file_get_contents(dirname( __FILE__ ) . '/post-type-data.json');
    $this->data = json_decode($data_file, true);

    $this->load_dependencies();
    $this->add_hooks();
  }

  /**
   * Requires/loads other files used by this plugin
   */
  public function load_dependencies() {
    require_once(dirname( __FILE__ ) . '/helpers.php');
    require_once(dirname( __FILE__ ) . '/widget.php');
    if ( is_admin() ) {
      require_once(dirname( __FILE__ ) . '/admin.php');
    }
  }

  /**
   * Adds/registers hooks for this plugin
   */
  public function add_hooks() {
    // activation hooks
    register_activation_hook(__FILE__, array($this, 'flush_rewrites'));

    // action hooks
    add_action('init', array($this, 'init'));
    add_action('widgets_init', array($this, 'register_widgets'));
    add_action('wp_head', array($this, 'output_public_css'));

    // filter hooks
    add_filter('body_class', array($this, 'filter_body_class_names'));
    add_filter('archive_template', array($this, 'filter_archive_template'));
    add_filter('single_template', array($this, 'filter_single_template'));
    add_filter('wp_title', array($this, 'filter_page_title'));
  }

  /**
   * Flush rewrite rules on plugin activation
   *
   * This is registered with register_activation_hook for this file
   */
  function flush_rewrites() {
    $this->init();
    flush_rewrite_rules();
  }


  /**
   * Registers the custom post type (self::POST_TYPE) and the custom taxonomies.
   *
   * @wp-hook init
   */
  function init() {
    register_post_type( $this->data['post_type'], $this->data['post_type_data'] );

    $taxonomy_defaults = array(
      'show_ui' => True,
      'rewrite' => array('with_front' => False),
      'capabilities' => array(
        'manage_terms' => 'manage_options', //by default only admin
        'edit_terms' => 'manage_options',
        'delete_terms' => 'manage_options',
        'assign_terms' => 'edit_posts'  // means administrator', 'editor', 'author', 'contributor'
      )
    );

    foreach( $this->data['taxonomies'] as $taxonomy ) {
      register_taxonomy(
        $taxonomy['taxonomy_name'],
        $this->data['post_type'],
        array_merge($taxonomy_defaults, $taxonomy['taxonomy_data'])
      );
    }
  }

  /**
   * Ouputs css to be used on public pages for this plugin
   *
   * @wp-hook wp_head
   */
  function output_public_css() {
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
      .staff_picks_widget_link {
        text-align: center;
        font-weight: bold;
        font-size: larger;
        padding: 0.5em;
      }
    </style>
    <?php
  }

  /**
   * Return the template file used to display a single post
   *
   * This is a filter. The current template is passed as an argument and is
   * modified if neccessary.
   *
   * @wp-hook single_template
   */
  function filter_single_template($template){
    global $post;

    if ($post->post_type == self::POST_TYPE) {
       $template = dirname( __FILE__ ) . '/single-staff-pick.php';
    }
    return $template;
  }

  /**
   * Return the template file used to display archive pages
   *
   * This is a filter. The current template is passed as an argument and is
   * modified if neccessary.
   *
   * @wp-hook archive_template
   */
  function filter_archive_template($template){
    global $post;

    if (is_post_type_archive(self::POST_TYPE)
      or is_tax(self::POST_TYPE_SINGULAR . '_categories')
      or is_tax(self::POST_TYPE_SINGULAR . '_audiences')
      or is_tax(self::POST_TYPE_SINGULAR . '_formats')
      or is_tax(self::POST_TYPE_SINGULAR . '_reviewers')
    ) {
       $template = dirname( __FILE__ ) . '/archive-staff-pick.php';
    }
    return $template;
  }

  /**
   * Modifies the page title
   *
   * This is a filter. The current title is passed as an argument and is
   * modified if neccesary.
   *
   * @wp-hook wp_title
   */
  function filter_page_title($title) {
    if (staff_picks_get_title()) {
      $title = staff_picks_get_title();
      // $blog_title = get_bloginfo( 'name' );
      return "$title";
    }
    return $title;
  }

  /**
   * Initializes widgets.
   *
   * @wp-hook widgets_init
   */
  function register_widgets() {
    register_widget( self::POST_TYPE_UPPER . '_Widget' );
  }

  /**
   * Returns an array of strings to be used as classes for the body tag of the current page
   *
   * This is a filter. The current array of class names is passed as an argument.
   * This method adds the audience term, if applicable, for easier css styling.
   *
   * wp-hook body-class
   */
  function filter_body_class_names( $classes ) {
    global $wp_query;

    if (is_single()) {
      $post = $wp_query->get_queried_object();
      $audiences = wp_get_post_terms(
        $post->ID,
        'staff_pick_audiences',
        array('fields' => 'names')
      );
      $classes = array_merge($classes, array_map('strtolower', $audiences));
    }

    if (is_tax()) {
      $term = $wp_query->get_queried_object();
      if ( $term->taxonomy == 'staff_pick_audiences' ) {
        $classes[] = strtolower( $term->name );
      }
    }
    return $classes;
  }
}

// create a plugin instance to load the plugin
new Staff_Picks_Plugin();
