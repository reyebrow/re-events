<?php

/**
 * Foo_Widget Class
 */
class Events_Widget extends WP_Widget {
	/** constructor */
	function __construct() {
		parent::WP_Widget( /* Base ID */'Events_Widget', /* Name */'Events_Widget', array( 'description' => 'Upcoming Events' ) );
	}

	/** @see WP_Widget::widget */
	function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );

        $args = array( 
          'post_type' => 'tf_events', 
          'posts_per_page' => 0,
          'order' => 'ASC',
          'meta_key' => 'tf_events_startdate',
          'orderby' => 'meta_value',
          'meta_query' => array(
            array(
              'key' => 'tf_events_startdate',
              'value' => strtotime("now"),
              'compare' => '>='
              )
          )
        );

        $loop = new WP_Query( $args ); ?>

         <?php if ( $loop->have_posts() ) {
         //print_r($loop);
    		echo $before_widget;
    		if ( $title )
    			echo $before_title . $title . $after_title;

        ?>
        <div class=""><?php
          while ( $loop->have_posts() ) {
            
            $loop->the_post();  //set up $post variable
            $post = $loop->post;
            $start_date = get_post_meta($post->ID, 'tf_events_startdate', true);
            ?>
              <div class="row event-row collapse">
                <div class="twelve columns">
                    
                  <h3 class="event-date"><?php tf_events_date($start_date, "F, j"); ?></h3>
                  <h3 class="event-title"><?php the_title(); ?></h3>
                  <div class="time-date"><?php tf_events_time($start_date);?> <?php tf_events_date($start_date, "l F j, Y"); ?></div>
                  <div class="venue"><?php print str_replace("\n", "</br>", get_post_meta($post->ID, 'tf_events_venue', true)); ?></div>
                  <div class="event-body"><?php the_content(); ?></div>
                </div>
              </div>
              
          <?php
          }//while
          ?>
          </div>
          <?php
        echo $after_widget;
          
      }//if ?>


		<?php }

	/** @see WP_Widget::update */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
	}

	/** @see WP_Widget::form */
	function form( $instance ) {
		if ( $instance ) {
			$title = esc_attr( $instance[ 'title' ] );
		}
		else {
			$title = __( 'New title', 'text_domain' );
		}?>
				<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>

		<?php

	}

} // class Foo_Widget
add_action( 'widgets_init', create_function( '', 'register_widget("Events_Widget");' ) );
