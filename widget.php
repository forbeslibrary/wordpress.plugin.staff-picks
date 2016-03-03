<?php
/**
 * Staff Picks widget.
 */

class Staff_Picks_Widget extends WP_Widget {

  /**
   * How many items to show in the widget by default.
   */
  const DEFAULT_COUNT = 6;
  /**
   * Allow widget to be filtered by a term from this taxonomy.
   */
  const TAXONOMY_FOR_FILTER = 'staff_pick_audiences';

  /**
   * Sets up the widgets name etc
   */
  public function __construct() {
    $data_file = file_get_contents(dirname( __FILE__ ) . '/post-type-data.json');
    $this->data = json_decode($data_file, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      trigger_error('Could not parse invalid JSON');
    }

    $name = $this->data['post_type_data']['labels']['name'];

    parent::__construct(
      $this->data['post_type'] . '_widget', // Base ID
      "$name Widget", // Name
      array( 'description' => __( "Displays recent $name" ), ) // Args
    );
  }

  /**
   * Outputs the content of the widget
   *
   * @param array $args     Widget arguments.
   * @param array $instance Saved values from database.
   */
  public function widget( $args, $instance ) {
    if ( !isset($instance[self::TAXONOMY_FOR_FILTER])) {
      $error_message = 'The settings for the ' . $this->data['post_type'] .
        ' widget are invalid on page ' . $_SERVER['REQUEST_URI'] .
        '. Please update the widget settings.';
      error_log($error_message);
      echo "<div><strong>$error_message</strong></div>";
      return;
    }
    echo $args['before_widget'];
    if ( ! empty( $instance['title'] ) ) {
      echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
    }

    $count = ( ! empty( $instance['count'] ) ? $instance['count'] : self::DEFAULT_COUNT );

    if ($instance[self::TAXONOMY_FOR_FILTER]==-1) {
      // show all
      $my_query = new WP_Query( array(
        'post_type' => $this->data['post_type'],
        'order' => 'DESC',
        'orderby' => 'date',
        'posts_per_page' => $count,
      ) );
    } else {
      $my_query = new WP_Query( array(
        'post_type' => $this->data['post_type'],
        'tax_query' => array( array (
          'taxonomy' => self::TAXONOMY_FOR_FILTER,
          'field' => 'term_id',
          'terms' => intval($instance[self::TAXONOMY_FOR_FILTER])
        ) ),
        'order' => 'DESC',
        'orderby' => 'date',
        'posts_per_page' => $count,
      ) );
    }

    if ($my_query->have_posts()) {
      while ( $my_query->have_posts() ) {
        $my_query->the_post();
        if ( has_post_thumbnail() ) : ?>
          <a href="<?php the_permalink(); ?>">
            <?php the_post_thumbnail( 'thumbnail', array('class' => "{$this->data['post_type']}_widget_image") ); ?>
          </a>
        <?php endif;
      }
    } else {
      echo __("No matching {$this->data['post_data']['labels']['name']} to show.");
    }
    wp_reset_postdata();
    if ($instance['show_link']): ?>
      <p class="<?php echo $this->data['post_type']; ?>_widget_link">
        <?php if ($instance[self::TAXONOMY_FOR_FILTER]==-1): ?>
          <a href="<?php echo get_post_type_archive_link($this->data['post_type']); ?>">
        <?php else: ?>
          <a href="<?php echo get_term_link(intval($instance[self::TAXONOMY_FOR_FILTER]), self::TAXONOMY_FOR_FILTER); ?>">
        <?php endif; ?>
          <?php echo $instance['link_text']; ?>
        </a>
      </p>
    <?php endif;
    echo $args['after_widget'];
  }

  /**
   * Outputs the options form on admin
   *
   * @param array $instance The widget options
   */
  public function form( $instance ) {
    $title = ! empty( $instance['title'] ) ? $instance['title'] : __('New title');
    $count = ! empty( $instance['count'] ) ? $instance['count'] : self::DEFAULT_COUNT;
    $show_link = isset( $instance['show_link'] ) ? $instance['show_link'] : False;
    $link_text = ! empty( $instance['link_text'] ) ? $instance['link_text'] : __("More {$this->data['post_type_data']['labels']['name']}");
    $audience = ! empty( $instance[self::TAXONOMY_FOR_FILTER] ) ? $instance[self::TAXONOMY_FOR_FILTER] : -1;
    ?>
    <p>
      <label>
        <?php _e( 'Title:' ); ?>
        <input type="text"
          class="widefat"
          id="<?php echo $this->get_field_id( 'title' ); ?>"
          name="<?php echo $this->get_field_name( 'title' ); ?>"
          value="<?php echo esc_attr( $title ); ?>"
          >
      </label>
    </p>
    <p>
      <label>
        <?php _e( 'Number of posts to show in widget:' ); ?>
        <input type="number"
          id=<?php echo $this->get_field_id( 'count' ); ?>
          name="<?php echo $this->get_field_name( 'count' ); ?>"
          value="<?php echo esc_attr( $count ); ?>"
          >
      </label>
    </p>
    <p>
      <label>
        <?php _e('Which posts to show:'); ?></br>
        <select
          id="<?php echo $this->get_field_id( 'audience' ); ?>"
          name="<?php echo $this->get_field_name( 'audience' ); ?>"
          >
          <option value="-1" <?php selected($audience, -1); ?>>
            <?php _e('All'); ?>
          </option>
          <?php foreach( get_terms(self::TAXONOMY_FOR_FILTER, array('hide_empty' => false)) as $term ): ?>
            <option value="<?php echo $term->term_id; ?>" <?php selected($audience, $term->term_id); ?>>
              <?php echo $term->name; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
    </p>
    <p>
      <label>
        <input type="checkbox"
          id="<?php echo $this->get_field_id( 'show_link' ); ?>"
          name="<?php echo $this->get_field_name( 'show_link' ); ?>"
          <?php if ($show_link): ?>checked="checked"<?php endif; ?>
          >
        <?php _e('Show link to archive page?'); ?>
      </label>
    </p>
    <p>
      <label>
        Link text
        <input type="text"
          class="widefat"
          id="<?php echo $this->get_field_id( 'link_text' ); ?>"
          name="<?php echo $this->get_field_name( 'link_text' ); ?>"
          value="<?php echo esc_attr( $link_text ); ?>"
          >
      </label>
    </p>
    <?php
  }

  /**
   * Processing widget options on save
   *
   * @param array $new_instance The new options
   * @param array $old_instance The previous options
   */
  public function update( $new_instance, $old_instance ) {
    $instance = array();
    $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
    $instance['count'] = ( ! empty( $new_instance['count'] ) ) ? strip_tags( intval( $new_instance['count'] ) ) : self::DEFAULT_COUNT;
    $instance['show_link'] = ! empty( $new_instance['show_link'] );
    $instance['link_text'] = ( ! empty( $new_instance['link_text'] ) ) ? strip_tags( $new_instance['link_text'] ) : '';
    $instance[self::TAXONOMY_FOR_FILTER] = ( ! empty( $new_instance['audience'] ) ) ? strip_tags( intval( $new_instance['audience'] ) ) : -1;

    return $instance;
  }
}
