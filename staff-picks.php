<?php
/**
 * Plugin Name: Staff Picks
 * Plugin URI: https://github.com/forbeslibrary/wordpress.plugin.staff-picks
 * Description: Adds a custom post type 'Staff Picks'.
 * Version: 1.3.1
 * Author: Benjamin Kalish
 * Author URI: https://github.com/bkalish
 * License: GNU General Public License v2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

class Staff_Picks_Plugin {
  public function __construct() {
    $data_file = file_get_contents(dirname( __FILE__ ) . '/post-type-data.json');
    $this->data = json_decode($data_file, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      trigger_error('Could not parse invalid JSON');
    }

    $this->load_dependencies();
    $this->add_hooks();
  }

  /**
   * Requires/loads other files used by this plugin
   */
  public function load_dependencies() {
    require_once(dirname( __FILE__ ) . '/helpers.php');
    $this->helper = new Staff_Picks_Helper();
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
   * Registers the custom post type {post_type} and the custom taxonomies.
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
    echo '<style>';
    readfile(dirname( __FILE__ ) . '/css/public.css');
    echo '</style>';
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

    if ($post->post_type == $this->data['post_type']) {
       $template = dirname( __FILE__ ) . "/templates/single-{$this->data['post_type']}.php";
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

    $use_custom_template = is_post_type_archive($this->data['post_type']);

    foreach($this->data['taxonomies'] as $taxonomy) {
      $use_custom_template = ($use_custom_template or is_tax($taxonomy['taxonomy_name']));
    }

    if ($use_custom_template) {
       $template = dirname( __FILE__ ) . "/templates/archive-{$this->data['post_type']}.php";
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
    if ($this->helper->get_title()) {
      $title = $this->helper->get_title();
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
    register_widget( $this->data['post_type_upper'] . '_Widget' );
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
