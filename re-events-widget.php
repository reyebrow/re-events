<?php

/**
 * Foo_Widget Class
 */
class Concerts_Widget extends WP_Widget {
	/** constructor */
	function __construct() {
		parent::WP_Widget( /* Base ID */'Concerts_Widget', /* Name */'Concerts_Widget', array( 'description' => 'Upcoming Concerts' ) );
	}

	/** @see WP_Widget::widget */
	function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title; ?>


      <?php $args = array( 
          'post_type' => 'tf_events', 
          'posts_per_page' => 0,
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
        ?><div class="row">
        <div class="twelve columns"><?php
          while ( $loop->have_posts() ) {
            
            $loop->the_post();  //set up $post variable
            $post = $loop->post;

            ?>
              <div class="row">
                <div class="two columns">
                  <a href="<?php print get_permalink( $post->ID );?>">    
                    <?php if ( has_post_thumbnail() ) { // check if the post has a Post Thumbnail assigned to it.
                    the_post_thumbnail( array(85,85) );
                    } ?>
                  </a>
                </div>
                <div class="ten columns">
                  <?php $start_date = get_post_meta($post->ID, 'tf_events_startdate', true);
                  ?>
                  <?php concert_date($start_date);?> | <?php concert_time($start_date);?> | <?php print get_post_meta($post->ID, 'tf_events_venue', true); ?>
                  <h3><a href="<?php print get_permalink( $post->ID );?>"><?php the_title(); ?>, <?php print get_post_meta($post->ID, 'tf_instrument', true); ?></a></h3>
                </div>
              </div>
              
          <?php
          }//while
          ?>
          </div>
          </div>
          <?php
      }//if ?>


		<?php echo $after_widget;
	}

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
add_action( 'widgets_init', create_function( '', 'register_widget("Concerts_Widget");' ) );
