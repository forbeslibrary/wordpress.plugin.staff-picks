<?php
/**
 * Staff Picks widget.
 */

class Staff_Picks_Widget extends WP_Widget {

  /**
   * Sets up the widgets name etc
   */
  public function __construct() {
    parent::__construct(
      'staff_picks_widget', // Base ID
      __( 'Staff Picks Widget', 'text_domain' ), // Name
      array( 'description' => __( 'Displays recent staff picks.', 'text_domain' ), ) // Args
    );
  }

  /**
   * Outputs the content of the widget
   *
   * @param array $args     Widget arguments.
   * @param array $instance Saved values from database.
   */
  public function widget( $args, $instance ) {
    echo $args['before_widget'];
    if ( ! empty( $instance['title'] ) ) {
      echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
    }
    $my_query = new WP_Query( array(
      'post_type' => 'staff_picks',
      'order' => 'DESC',
      'orderby' => 'date',
      'posts_per_page' => 6,
    ) );
    while ( $my_query->have_posts() ) {
       $my_query->the_post();
       if ( has_post_thumbnail() ) : ?>
         <a href="<?php the_permalink(); ?>">
           <?php the_post_thumbnail( 'thumbnail', array('class' => 'staff_picks_widget_image') ); ?>
         </a>
       <?php endif;
    }
    echo $args['after_widget'];
  }

  /**
   * Outputs the options form on admin
   *
   * @param array $instance The widget options
   */
  public function form( $instance ) {
    $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'New title', 'text_domain' );
    ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
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

    return $instance;
  }
}
