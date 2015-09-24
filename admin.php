<?php
/**
 * Admin interface for the Forbes staff_picks plugin.
 */
class Staff_Picks_Admin {
  // The post type (plural), in Upper_Case_With_Underscores
  const POST_TYPE_UPPER = 'Staff_Picks';

  // The post type (plural), in lower_case_with_underscores
  const POST_TYPE = 'staff_picks';

  // The post type (singular), in lower_case_with_underscores
  const POST_TYPE_SINGULAR = 'staff_pick';

  public function __construct() {
    $data_file = file_get_contents(dirname( __FILE__ ) . '/post-type-data.json');
    $this->data = json_decode($data_file, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      trigger_error('Could not parse invalid JSON');
    }

    $this->add_hooks();
  }

  // admin only action hooks
  public function add_hooks() {
    add_action('admin_head', array($this, 'admin_css'));
    add_action('admin_notices', array($this, 'admin_notice'));
    add_action('dashboard_glance_items', array($this, 'add_glance_items'));
    add_action('edit_form_after_title', array($this, 'editbox_metadata'));
    add_action("manage_{$this->data['post_type']}_posts_custom_column", array($this, 'custom_columns'));
    add_action('pre_insert_term', array($this, 'restrict_insert_taxonomy_terms'));
    add_action('save_post', array($this, 'validate_and_save'));
    add_action('add_meta_boxes', array($this, 'modify_metaboxes'));

    add_filter("manage_{$this->data['post_type']}_posts_columns", array($this, 'manage_columns'));
    add_filter('redirect_post_location', array($this, 'fix_status_message'));
  }

  /**
  * Save custom fields from {$post_type} edit page.
  *
  * @wp-hook save_post
  */
  public function validate_and_save( $post_id ){
    $post =  get_post( $post_id );

    if ( $post->post_type != $this->data['post_type'] ) {
     return;
    }

    // Update custom field
    if (isset($_POST[$this->data['custom_field_name']])) {
      update_post_meta($post->ID, $this->data['custom_field_name'], $_POST[$this->data['custom_field_name']]);
    }

    // Stop interfering if this is a draft or the post is being deleted
    if ( in_array(
      get_post_status( $post->ID ),
      array('draft', 'auto-draft', 'trash')
    )) {
       return;
    }

    // Validation
    $errors = array();

    if (isset($_POST[$this->data['custom_field_name']])) {
      $metadata = $_POST[$this->data['custom_field_name']];

      if (isset($metadata['catalog_url'])) {
        if (trim($metadata['catalog_url']) == false) {
          $errors[] = __('The catalog url field may not be blank');
        }
      } else {
        $errors[] = __('The catalog url field was missing');
      }
    }

    foreach( $this->data['taxonomies'] as $taxonomy ) {
      $singular_name = $taxonomy['taxonomy_data']['labels']['singular_label'];
      $terms = get_the_terms( $post->ID, $taxonomy['taxonomy_name'] );
      if ($taxonomy['required'] and !$terms) {
        $errors[] = __("{$singular_name} is required");
      }
      if ($taxonomy['allow_multiple'] == false and count($terms) > 1) {
        $errors[] = __("You may only choose one {$singular_name}");
      }
    }

    if ( !has_post_thumbnail( $post->ID ) ) {
      $errors[] = __('You must set a cover image');
    }

    if ($errors) {
      // Save the errors using the transients api
      set_transient( $this->data['post_type'] . "_errors_{$post->ID}", $errors, 120 );

      // we must remove this action or it will loop for ever
      remove_action('save_post', $this->data['post_type'] . '_validate_and_save');

      // Change post from published to draft
      $post->post_status = 'draft';

      // update the post
      wp_update_post( $post );

      // we must add back this action
      add_action('save_post', $this->data['post_type'] . '_validate_and_save');
    }

  }

  /**
  *  Fix status message when user tries to publish an invalid staff pick.
  *
  * If the user hits the publish button the publish message will display even if
  * we have changed the status to draft during validation. This fixes that by
  * modifying the message if any errors have been queued.
  *
  * FIX ME. Currently broken!
  *
  * @wp-hook redirect_post_location
  */
  public function fix_status_message($location, $post_id) {
    //If any staff pick errors have been queued...
    if (get_transient( $this->data['post_type'] . "_errors_{$post->ID}" )){
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
  public function admin_css() {
    echo '<style>';
    readfile(dirname( __FILE__ ) . '/css/admin.css');
    echo '</style>';
  }

  /**
   * The Weaver II theme adds a giant meta box that isn't much help with custom
   * post types. This code removes that box from staff pick edit pages and changes
   * the featured image box name and placement.
   *
   * @wp-hook add_meta_boxes
   */
  public function modify_metaboxes() {
    remove_meta_box('wii_post-box2', $this->data['post_type'], 'normal');
    remove_meta_box( 'postimagediv', $this->data['post_type'] , 'side' );
    add_meta_box( 'postimagediv', __('Cover Image'), 'post_thumbnail_meta_box', $this->data['post_type'], 'side', 'high' );
  }


  /**
   * Displays admin notices such as validation errors
   *
   * @wp-hook admin_notices
   */
  public function admin_notice() {
    global $post;

    if (!isset($post)) {
      return;
    }

    $errors = get_transient( $this->data['post_type'] . "_errors_{$post->ID}" );
    if ($errors) {
      foreach ($errors as $error): ?>
        <div class="error">
          <p><?php echo $error; ?></p>
        </div>
        <?php
      endforeach;
    }
    delete_transient( $this->data['post_type'] . "_errors_{$post->ID}" );
  }

  /**
   * Outputs the contents of each custom column on the admin page.
   *
   * @wp-hook manage_{$post_type}_posts_custom_column
   */
  public function custom_columns($column){
    global $post;
    $custom = get_post_custom($post->ID);
    if (isset($custom[self::POST_TYPE_SINGULAR . '_metadata'])) {
      $metadata = maybe_unserialize(
        $custom[self::POST_TYPE_SINGULAR . '_metadata'][0]
      );
    } else {
      $metadata = array();
    }

    switch ($column) {
      case $this->data['post_type'] . '_author':
        if (isset($metadata['author'])) {
          echo $metadata['author'];
        }
        break;
    }

    foreach( $this->data['taxonomies'] as $taxonomy ) {
      if ($column == $taxonomy['taxonomy_name']) {
        echo implode(', ', wp_get_post_terms($post->ID, $taxonomy['taxonomy_name'], array('fields' => 'names')));
      }
    }
  }

  /**
   * Customizes the columns on the {$post_type} admin page.
   *
   * @wp-hook manage_{$post_type}_posts_columns
   */
  public function manage_columns($columns){
    $custom_columns = array(
      'title' => 'Title',
      $this->data['post_type'] . '_author' => 'Author',
    );

    foreach( $this->data['taxonomies'] as $taxonomy ) {
      $custom_columns[$taxonomy['taxonomy_name']] = $taxonomy['taxonomy_data']['labels']['singular_label'];
    }

    $columns = array_merge( $columns, $custom_columns);

    return $columns;
  }

  /**
   * Add information about {$post_type} to the glance items.
   *
   * @wp-hook dashboard_glance_items
   */
  public function add_glance_items() {
    $pt_info = get_post_type_object($this->data['post_type']);
    $num_posts = wp_count_posts($this->data['post_type']);
    $num = number_format_i18n($num_posts->publish);
    $text = _n( $pt_info->labels->singular_name, $pt_info->labels->name, intval($num_posts->publish) ); // singular/plural text label
    echo '<li class="page-count ' . $pt_info->name . '-count"><a href="edit.php?post_type=' . $this->data['post_type'] . '">' . $num . ' ' . $text . '</li>';
  }

  /**
   * Restrict the addition of new taxonomy terms.
   *
   * @wp-hook pre_insert_term
   */
  public function restrict_insert_taxonomy_terms($term, $taxonomy=null) {
    if (current_user_can('manage_options')) {
      return $term;
    }
    foreach( $this->data['taxonomies'] as $t ) {
      if ($taxonomy['taxonomy_name'] == $t) {
        return new WP_Error( 'term_addition_blocked', __( 'You cannot add terms to this taxonomy' ) );
      }
    }
  }

  /**
   * Outputs the html for the  metadata box on the {post_type} edit page.
   */
  public function editbox_metadata(){
    global $post;
    if ($post->post_type !== $this->data['post_type']) {
      return;
    }
    $custom = get_post_custom($post->ID);
    if (isset($custom[$this->data['custom_field_name']])) {
      $metadata = maybe_unserialize(
        $custom[$this->data['custom_field_name']][0]
      );
    } else {
      $metadata['author'] = '';
      $metadata['catalog_url'] = '';
    }
    ?>
    <label>
      <span class="<?php echo $this->data['post_type']; ?>-metadata-label">Author</span>
      <input
        name="<?php echo $this->data['custom_field_name']; ?>[author]"
        class="<?php echo $this->data['custom_field_name']; ?>-input"
        value="<?php echo $metadata['author']; ?>"
      />
    </label>
    <label>
      <span class="<?php echo $this->data['custom_field_name']; ?>-label">Catalog URL</span>
      <input
        name="<?php echo $this->data['custom_field_name']; ?>[catalog_url]"
        class="<?php echo $this->data['custom_field_name']; ?>-input"
        value="<?php echo $metadata['catalog_url']; ?>"
      />
    </label><?php
  }
}

// create an instance to load the code
new Staff_Picks_Admin();
