<?php

add_action('admin_init', 'tf_functions_css');

function tf_functions_css() {
	//wp_enqueue_style('tf-functions-css', get_bloginfo('stylesheet_directory') . '/css/tf-functions.css');
}

add_action( 'init', 'create_event_postype' );


function create_event_postype() {
  
  $labels = array(
      'name' => _x('Events', 'post type general name'),
      'singular_name' => _x('Event', 'post type singular name'),
      'add_new' => _x('Add New', 'events'),
      'add_new_item' => __('Add New Event'),
      'edit_item' => __('Edit Event'),
      'new_item' => __('New Event'),
      'view_item' => __('View Event'),
      'search_items' => __('Search Events'),
      'not_found' =>  __('No events found'),
      'not_found_in_trash' => __('No events found in Trash'),
      'parent_item_colon' => '',
  );
  
  $args = array(
      'label' => __('Events'),
      'labels' => $labels,
      'public' => true,
      'can_export' => true,
      'show_ui' => true,
      '_builtin' => false,
      '_edit_link' => 'post.php?post=%d', // ?
      'capability_type' => 'post',
      'menu_icon' => plugins_url('cal.png', __FILE__),
      'hierarchical' => false,
      'rewrite' => array( "slug" => "events" ),
      'supports'=> array('title', 'thumbnail', 'excerpt', 'editor','custom-fields') ,
      'show_in_nav_menus' => true,
      'taxonomies' => array( 're_regions')
  );
    
  register_post_type( 'tf_events', $args);

}


// 2. Custom Taxonomy Registration (Event Types)

/*

Not using Event Categories

function create_eventcategory_taxonomy() {

    $labels = array(
        'name' => _x( 'Categories', 'taxonomy general name' ),
        'singular_name' => _x( 'Category', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search Categories' ),
        'popular_itqems' => __( 'Popular Categories' ),
        'all_items' => __( 'All Categories' ),
        'parent_item' => null,
        'parent_item_colon' => null,
        'edit_item' => __( 'Edit Category' ),
        'update_item' => __( 'Update Category' ),
        'add_new_item' => __( 'Add New Category' ),
        'new_item_name' => __( 'New Category Name' ),
        'separate_items_with_commas' => __( 'Separate categories with commas' ),
        'add_or_remove_items' => __( 'Add or remove categories' ),
        'choose_from_most_used' => __( 'Choose from the most used categories' ),
    );

    register_taxonomy('tf_eventcategory','tf_events', array(
        'label' => __('Event Category'),
        'labels' => $labels,
        'hierarchical' => true,
        'show_ui' => true,
        'query_var' => true,
        'rewrite' => array( 'slug' => 'event-category' ),
    ));

}

add_action( 'init', 'create_eventcategory_taxonomy', 0 );
*/



add_action( 'admin_init', 'tf_events_create' );

function tf_events_create() {
    add_meta_box('tf_events_meta', 'Events', 'tf_events_meta', 'tf_events', 'side', 'high');
}

function tf_events_meta () {

    // - grab data -

    global $post;
    $custom = get_post_custom($post->ID);
    $meta_sd = $custom["tf_events_startdate"][0];
    $meta_ed = $custom["tf_events_enddate"][0];
    $meta_st = $meta_sd;
    $meta_et = $meta_ed;
    $meta_tz = !empty($custom["tf_events_tz"][0]) ? $custom["tf_events_tz"][0] : "-5.0";
    $selected = "selected=\"selected\"";
    

    $tfvenue = $custom["tf_events_venue"][0];

    // - grab wp time format -

    $date_format = get_option('date_format'); // Not required in my code
    $time_format = get_option('time_format');

    // - populate today if empty, 00:00 for time -

    if ($meta_sd == null) { $meta_sd = time(); $meta_ed = $meta_sd; $meta_st = 0; $meta_et = 0;}

    // - convert to pretty formats -

    $clean_sd = date("D, M d, Y", $meta_sd);
    $clean_ed = date("D, M d, Y", $meta_ed);
    $clean_st = date($time_format, $meta_st);
    $clean_et = date($time_format, $meta_et);

    // - security -

    echo '<input type="hidden" name="tf-events-nonce" id="tf-events-nonce" value="' .
    wp_create_nonce( 'tf-events-nonce' ) . '" />';

    // - output -

    ?>
    <div class="tf-meta">
        <ul>
            <li><label>Start Date</label><input name="tf_events_startdate" class="tfdate" value="<?php echo $clean_sd; ?>" /></li>
            <li><label>Start Time</label><input name="tf_events_starttime" class="tftime" value="<?php echo $clean_st; ?>" /><em>Use 12H format (7pm = 07:00pm)</em></li>
            <li><label>End Date</label><input name="tf_events_enddate" class="tfdate" value="<?php echo $clean_ed; ?>" /></li>
            <li><label>End Time</label><input name="tf_events_endtime" class="tftime" value="<?php echo $clean_et; ?>" /><em>Use 12H format (7pm = 07:00pm)</em></li>
            <li>
                <label>Time Zone</label>
                <select name="tf_events_tz" id="tf_events_tz">
                  <option value="-8.0" <?php print $meta_tz == "-8.0" ? $selected : "" ?>>(GMT -8:00) Pacific Time</option>
                  <option value="-7.0" <?php print $meta_tz == "-7.0" ? $selected : "" ?>>(GMT -7:00) Mountain Time</option>
                  <option value="-6.0" <?php print $meta_tz == "-6.0" ? $selected : "" ?>>(GMT -6:00) Central Time</option>
                  <option value="-5.0" <?php print $meta_tz == "-5.0" ? $selected : "" ?>>(GMT -5:00) Eastern Time</option>
                  <option value="-4.0" <?php print $meta_tz == "-4.0" ? $selected : "" ?>>(GMT -4:00) Atlantic Time</option>
                  <option value="-3.5" <?php print $meta_tz == "-3.5" ? $selected : "" ?>>(GMT -3:30) Newfoundland</option>
                </select>
            </li>
        </ul>
    </div>
    <div class="tf-meta location">
        <ul>
            <li><label>Venue</label><input name="tf_events_venue" class="tfvenue" value="<?php echo $tfvenue; ?>" /></li>
        </ul>
    </div>
    <?php
}

add_action( 'admin_init', 'add_events_metaboxes' );






function add_events_metaboxes() {
    add_meta_box('wpt_events_location', 'Event Location', 'wpt_events_location', 'tf_events', 'normal', 'default');
}

function wpt_events_location() {
    global $post;
 
    // Noncename needed to verify where the data originated
    echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
 
    // Get the location data if its already been entered
    $location = get_post_meta($post->ID, '_location', true);
 
    // Echo out the field 
    echo '<p>Google Map Link: <input type="text" name="_location" value="' . $location  . '" class="widefat" /></p>';
    echo '<p>Paste a link to Google Maps to create an embedded Map</p>';
 
}





add_action ('save_post', 'save_tf_events');

function save_tf_events(){

    global $post;

    // - still require nonce

    if ( !wp_verify_nonce( $_POST['tf-events-nonce'], 'tf-events-nonce' )) {
        return $post->ID;
    }

    if ( !current_user_can( 'edit_post', $post->ID ))
        return $post->ID;

    // - convert back to unix & update post

    if(!isset($_POST["tf_events_startdate"])):
        return $post;
    endif;
        $updatestartd = strtotime ( $_POST["tf_events_startdate"] . $_POST["tf_events_starttime"] );
        update_post_meta($post->ID, "tf_events_startdate", $updatestartd );

    if(!isset($_POST["tf_events_enddate"])):
        return $post;
    endif;
        $updateendd = strtotime ( $_POST["tf_events_enddate"] . $_POST["tf_events_endtime"]);
        update_post_meta($post->ID, "tf_events_enddate", $updateendd );


    if(!isset($_POST["tf_events_tz"])):
        return $post;
    endif;
        update_post_meta($post->ID, "tf_events_tz", $_POST["tf_events_tz"] );
        
    if(!isset($_POST["tf_events_venue"])):
        return $post;
    endif;
        update_post_meta($post->ID, "tf_events_venue", $_POST["tf_events_venue"] );

}


function wpt_save_events_meta($post_id, $post) {
 
    // verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times
    if ( !wp_verify_nonce( $_POST['eventmeta_noncename'], plugin_basename(__FILE__) )) {
    return $post->ID;
    }
 
    // Is the user allowed to edit the post or page?
    if ( !current_user_can( 'edit_post', $post->ID ))
        return $post->ID;
 
    // OK, we're authenticated: we need to find and save the data
    // We'll put it into an array to make it easier to loop though.
 
    $events_meta['_location'] = $_POST['_location'];
 
    // Add values of $events_meta as custom fields
 
    foreach ($events_meta as $key => $value) { // Cycle through the $events_meta array!
        if( $post->post_type == 'revision' ) return; // Don't store custom data twice
        $value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
        if(get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value
            update_post_meta($post->ID, $key, $value);
        } else { // If the custom field doesn't have a value
            add_post_meta($post->ID, $key, $value);
        }
        if(!$value) delete_post_meta($post->ID, $key); // Delete if blank
    }
 
}
 
add_action('save_post', 'wpt_save_events_meta', 1, 2); // save the custom fields





// 7. JS Datepicker UI

function events_styles() {
    wp_enqueue_style('ui-datepicker', plugins_url('/css/ui-lightness/jquery-ui-1.8.16.custom.css', __FILE__) );
    //wp_enqueue_style('ui-timepicker', plugins_url('/css/timePicker.css', __FILE__) );
}

function events_scripts() {
    global $post_type;
    if( 'tf_events' != $post_type )
    return;
    wp_enqueue_script( 'js-jquery-ui-datepicker', plugins_url('/js/jquery-ui-1.8.16.datepicker.min.js', __FILE__), 'jQuery' );
    //wp_enqueue_script( 'js-jquery-ui-timepicker', plugins_url('/js/jquery.timePicker.min.js', __FILE__), 'jQuery' );
    wp_enqueue_script( 're-events-init', plugins_url('/js/app.js', __FILE__), 'js-jquery-ui-datepicker');
}

add_action( 'admin_init', 'events_styles');
add_action( 'admin_enqueue_scripts', 'events_scripts');


