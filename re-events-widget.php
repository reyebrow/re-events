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
		$limit = is_numeric($instance['limit']) ? $instance['limit'] : 5;
		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title; ?>

      <?php       
        // - grab date barrier -
        $today6am = strtotime('today 6:00');
      
        // - query -
        global $wpdb;
        $querystr = "
            SELECT *
            FROM $wpdb->posts wposts, $wpdb->postmeta metastart, $wpdb->postmeta metaend
            WHERE (wposts.ID = metastart.post_id AND wposts.ID = metaend.post_id)
            AND (metaend.meta_key = 'tf_events_startdate' AND metaend.meta_value > $today6am )
            AND metastart.meta_key = 'tf_events_startdate'
            AND wposts.post_type = 'tf_events'
            AND wposts.post_status = 'publish'
            ORDER BY metastart.meta_value ASC LIMIT $limit
         ";
        
        $events = $wpdb->get_results($querystr, OBJECT);
        
        // - loop -
        if ($events):
        global $post;


        foreach ($events as $post):
          setup_postdata($post);


        ?><div class="row concert-widget">

                <div class="five columns">
                  <a href="<?php print get_permalink( $post->ID );?>">    
                    <?php if ( has_post_thumbnail() ) { // check if the post has a Post Thumbnail assigned to it.
                    the_post_thumbnail('concert_thumb');
                    } ?>
                  </a>
                </div>
                <div class="seven columns">
                  <?php $start_date = get_post_meta($post->ID, 'tf_events_startdate', true);
                  ?>
                  <h3 class="concert-title"><a href="<?php print get_permalink( $post->ID );?>"><?php the_title(); ?>, <?php print get_post_meta($post->ID, 'tf_instrument', true); ?></a></h3>
               		<?php $title2 = get_post_meta($post->ID, 'tf_title2', true);
              		if (isset($title2) && $title2 != "") :?>
              		<h3 class="concert-title"><a href="<?php print get_permalink( $post->ID );?>"><?php print get_post_meta($post->ID, 'tf_title2', true); ?>, <?php print get_post_meta($post->ID, 'tf_instrument2', true); ?></a></h3>
              		<?php endif;?>
                  <?php concert_date($start_date);?> | <?php concert_time($start_date);?>
                  <div><?php print get_post_meta($post->ID, 'tf_events_venue', true); ?></div>
                </div> <?php // 10 columns ?>

              </div><?php // ROW ?>
              
          <?php
          endforeach;
          ?>
          <?php endif;//if ?>

		<?php echo $after_widget;
	}

	/** @see WP_Widget::update */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['limit'] = strip_tags($new_instance['limit']);
		return $instance;
	}

	/** @see WP_Widget::form */
	function form( $instance ) {
		if ( $instance ) {
			$title = esc_attr( $instance[ 'title' ] );
			$limit = is_numeric(esc_attr( $instance[ 'limit' ] )) ? esc_attr( $instance[ 'limit' ] ) : 5;
		}
		else {
			$title = __( 'New title', 'text_domain' );
			$limit = 5;
		}?>
				<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		<label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('Number of cocnerts to show:'); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="text" value="<?php echo $limit; ?>" />

		</p>

		<?php

	}

} // class Foo_Widget
add_action( 'widgets_init', create_function( '', 'register_widget("Concerts_Widget");' ) );
