<?php
/**
 * Staff Picks widget.
 */

class Staff_Picks_Widget extends WP_Widget {

  const DEFAULT_COUNT = 6;

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

    $count = ( ! empty( $instance['count'] ) ? $instance['count'] : self::DEFAULT_COUNT );

    if ($instance['audience']==-1) {
      // show all staff picks
      $my_query = new WP_Query( array(
        'post_type' => 'staff_picks',
        'order' => 'DESC',
        'orderby' => 'date',
        'posts_per_page' => $count,
      ) );
    } else {
      $my_query = new WP_Query( array(
        'post_type' => 'staff_picks',
        'tax_query' => array( array (
          'taxonomy' => 'staff_pick_audiences',
          'field' => 'term_id',
          'terms' => intval($instance['audience'])
        ) ),
        'order' => 'DESC',
        'orderby' => 'date',
        'posts_per_page' => $count,
      ) );
    }

    while ( $my_query->have_posts() ) {
       $my_query->the_post();
       if ( has_post_thumbnail() ) : ?>
         <a href="<?php the_permalink(); ?>">
           <?php the_post_thumbnail( 'thumbnail', array('class' => 'staff_picks_widget_image') ); ?>
         </a>
       <?php endif;
    }
    wp_reset_postdata();
    if ($instance['show_link']): ?>
      <p class="staff_picks_widget_link">
        <?php if ($instance['audience']==-1): ?>
          <a href="<?php echo get_post_type_archive_link('staff_picks'); ?>">
        <?php else: ?>
          <a href="<?php echo get_term_link(intval($instance['audience']), 'staff_pick_audiences'); ?>">
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
    $link_text = ! empty( $instance['link_text'] ) ? $instance['link_text'] : __('More Staff Picks');
    $audience = ! empty( $instance['audience'] ) ? $instance['audience'] : -1;
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
        <?php _e( 'Number of staff picks to show in widget:' ); ?>
        <input type="number"
          id=<?php echo $this->get_field_id( 'count' ); ?>
          name="<?php echo $this->get_field_name( 'count' ); ?>"
          value="<?php echo esc_attr( $count ); ?>"
          >
      </label>
    </p>
    <p>
      <label>
        <?php _e('Which staff picks to show:'); ?></br>
        <select
          id="<?php echo $this->get_field_id( 'audience' ); ?>"
          name="<?php echo $this->get_field_name( 'audience' ); ?>"
          >
          <option value="-1" <?php selected($audience, -1); ?>>
            <?php _e('All'); ?>
          </option>
          <?php foreach( get_terms('staff_pick_audiences', array('hide_empty' => false)) as $term ): ?>
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
        <?php _e('Show link to staff picks page?'); ?>
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
    $instance['audience'] = ( ! empty( $new_instance['audience'] ) ) ? strip_tags( intval( $new_instance['audience'] ) ) : -1;

    return $instance;
  }
}
