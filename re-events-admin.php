<?php


add_action('admin_init', 'tf_functions_css');

function tf_functions_css() {
	//wp_enqueue_style('tf-functions-css', get_bloginfo('stylesheet_directory') . '/css/tf-functions.css');
}

add_action( 'init', 'create_event_postype' );


function create_event_postype() {
  
  $labels = array(
      'name' => _x('Concerts', 'post type general name'),
      'singular_name' => _x('Concert', 'post type singular name'),
      'add_new' => _x('Add New', 'events'),
      'add_new_item' => __('Add New Concert'),
      'edit_item' => __('Edit Concert'),
      'new_item' => __('New Concert'),
      'view_item' => __('View Concert'),
      'search_items' => __('Search Concert'),
      'not_found' =>  __('No Concerts found'),
      'not_found_in_trash' => __('No Concerts found in Trash'),
      'parent_item_colon' => '',
  );
  
  $args = array(
      'label' => __('Concert'),
      'labels' => $labels,
      'public' => true,
      'can_export' => true,
      'show_ui' => true,
      '_builtin' => false,
      '_edit_link' => 'post.php?post=%d', // ?
      'capability_type' => 'post',
      'menu_icon' => plugins_url('cal.png', __FILE__),
      'hierarchical' => false,
      'rewrite' => array( "slug" => "concert" ),
      'supports'=> array('title', 'thumbnail', 'excerpt', 'editor','custom-fields') ,
      'show_in_nav_menus' => true,
  );
    
  register_post_type( 'tf_events', $args);

}



add_action( 'admin_init', 'vrs_metabox_create' );

function vrs_metabox_create() {
    add_meta_box('tf_events_meta', 'Concert Details', 'tf_events_meta', 'tf_events', 'side', 'high');
    add_meta_box('page-vrs-secondary-events', __('Concert Details'), 'vrs_secondary_edit_meta_box', 'tf_events', 'normal', 'high');
}

function tf_events_meta () {

    // - grab data -

    global $post;
    $custom = get_post_custom($post->ID);
    $meta_sd = $custom["tf_events_startdate"][0];
    $instrument = $custom["tf_instrument"][0];
    $maplink = $custom["tf_map_link"][0];
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
            <li><label>Primary Instrument</label><input name="tf_instrument" class="tfinstr" value="<?php echo $instrument; ?>" /></li>
            <li><label>Start Date</label><input name="tf_events_startdate" class="tfdate" value="<?php echo $clean_sd; ?>" /></li>
            <li><label>Start Time</label><input name="tf_events_starttime" class="tftime" value="<?php echo $clean_st; ?>" /><em>Use 24h format (7pm = 19:00)</em></li>
        </ul>
    </div>
    <div class="tf-meta location">
        <ul>
            <li><label>Venue</label><input name="tf_events_venue" class="tfvenue" value="<?php echo $tfvenue; ?>" /></li>
            <li><label>Venue Map Link</label><input name="tf_map_link" class="tfmaplink" value="<?php echo $maplink; ?>" /></li>
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

        
    if(!isset($_POST["tf_events_venue"])):
        return $post;
    endif;
        update_post_meta($post->ID, "tf_events_venue", $_POST["tf_events_venue"] );

    if (isset($_POST['tf_instrument']))
        update_post_meta($post->ID, "tf_instrument", $_POST["tf_instrument"] );

    if (isset($_POST['tf_map_link']))
        update_post_meta($post->ID, "tf_map_link", $_POST["tf_map_link"] );        

	if (isset($_POST['vrs_secondary_edit']))
		update_post_meta($_POST['post_ID'], 'vrs_secondary_edit', $_POST['vrs_secondary_edit']);

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
    wp_enqueue_style('ui-timepicker', plugins_url('/css/timePicker.css', __FILE__) );
}

function events_scripts() {
    global $post_type;
    if( 'tf_events' != $post_type )
    return;
    wp_enqueue_script( 'js-jquery-ui-datepicker', plugins_url('/js/jquery-ui-1.8.16.datepicker.min.js', __FILE__), 'jQuery' );
    wp_enqueue_script( 'js-jquery-ui-timepicker', plugins_url('/js/jquery.timePicker.min.js', __FILE__), 'jQuery' );
    wp_enqueue_script( 're-events-init', plugins_url('/js/app.js', __FILE__), 'js-jquery-ui-datepicker');
}

add_action( 'admin_init', 'events_styles');
add_action( 'admin_enqueue_scripts', 'events_scripts');





/************************************************************************
     Second tinymce metabox 
*************************************************************************/

function vrs_secondary_edit_meta_box(){
	global $post;

  $vrs_secondary_edit = get_post_meta($post->ID, 'vrs_secondary_edit', true);	
  
  the_editor($vrs_secondary_edit, $id = 'vrs_secondary_edit', $prev_id = 'vrs_secondary_edit_buttons', $media_buttons = true, $tab_index = 2);

}


// For Events we have a seasons taxonomy

function create_season_taxonomy() {

    $labels = array(
        'name' => _x( 'Seasons', 'taxonomy general name' ),
        'singular_name' => _x( 'Season', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search Seasons' ),
        'popular_itqems' => __( 'Popular Seasons' ),
        'all_items' => __( 'All Seasons' ),
        'parent_item' => null,
        'parent_item_colon' => null,
        'edit_item' => __( 'Edit Season' ),
        'update_item' => __( 'Update Season' ),
        'add_new_item' => __( 'Add New Season' ),
        'new_item_name' => __( 'New Season Name' ),
        'separate_items_with_commas' => __( 'Separate season with commas' ),
        'add_or_remove_items' => __( 'Add or remove season' ),
        'choose_from_most_used' => __( 'Choose from the most used seasons' ),
    );

    register_taxonomy('tf_eventcategory','tf_events', array(
        'label' => __('Season'),
        'labels' => $labels,
        'hierarchical' => true,
        'show_ui' => true,
        'query_var' => true,
        'rewrite' => array( 'slug' => 'season' ),
    ));
    

}
add_action( 'init', 'create_season_taxonomy', 0 );

//Each event can sit in a subscription package

function create_package_taxonomy() {

    $labels = array(
        'name' => _x( 'Subscription Packages', 'taxonomy general name' ),
        'singular_name' => _x( 'Subscription Package', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search Subscription Packages' ),
        'popular_itqems' => __( 'Popular Subscription Packages' ),
        'all_items' => __( 'All Subscription Packages' ),
        'parent_item' => null,
        'parent_item_colon' => null,
        'edit_item' => __( 'Edit Subscription Package' ),
        'update_item' => __( 'Update Subscription Package' ),
        'add_new_item' => __( 'Add New Subscription Package' ),
        'new_item_name' => __( 'New Subscription Package Name' ),
        'separate_items_with_commas' => __( 'Separate Subscription Packages with commas' ),
        'add_or_remove_items' => __( 'Add or remove Subscription Package' ),
        'choose_from_most_used' => __( 'Choose from the most used Subscription Packages' ),
    );

    register_taxonomy('tf_subscription_package','tf_events', array(
        'label' => __('Subscription Package'),
        'labels' => $labels,
        'hierarchical' => true,
        'show_ui' => true,
        'query_var' => true,
        'rewrite' => array( 'slug' => 'season' ),
    ));

}
add_action( 'init', 'create_package_taxonomy', 0 );




