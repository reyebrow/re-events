<?php 
/*
Plugin Name: Raised Eyebrow VRS Concerts
Description: Event Listings
Version: 0.1
License: GPL
Author: Matt Reimer
Author URI: http://www.raisedeyebrow.com/
*/


include_once('re-events-admin.php');
include_once('re-events-widget.php');

// 3. Show Columns

add_filter ("manage_edit-tf_events_columns", "tf_events_edit_columns");
add_action ("manage_posts_custom_column", "tf_events_custom_columns");

function tf_events_edit_columns($columns) {

    $columns = array(
        "title" => "Event",
        "cb" => "<input type=\"checkbox\" />",
        "tf_col_ev_cat" => "Category",
        "tf_col_ev_date" => "Dates",
        "tf_col_ev_times" => "Times",
        );

    return $columns;

}

function tf_events_custom_columns($column) {

    global $post;
    $custom = get_post_custom();
    switch ($column)

        {
            case "tf_col_ev_cat":
                // - show taxonomy terms -
                $eventcats = get_the_terms($post->ID, "tf_eventcategory");
                $eventcats_html = array();
                if ($eventcats) {
                    foreach ($eventcats as $eventcat)
                    array_push($eventcats_html, $eventcat->name);
                    echo implode($eventcats_html, ", ");
                } else {
                _e('None', 'themeforce');;
                }
            break;
            case "tf_col_ev_date":
                // - show dates -
                $startd = $custom["tf_events_startdate"][0];
                $endd = $custom["tf_events_enddate"][0];
                if (isset($startd) && $startd != ""){
                  $startdate = date("F j, Y", $startd);
                  $enddate = date("F j, Y", $endd);
                  echo $startdate . '<br /><em>' . $enddate . '</em>';
                }
                else {
                  echo "NOT SET";
                }
            break;
            case "tf_col_ev_times":
                // - show times -
                $startt = $custom["tf_events_startdate"][0];
                $endt = $custom["tf_events_enddate"][0];
                $time_format = get_option('time_format');
                if (isset($startt) && $startt != ""){
                  $starttime = date($time_format, $startt);
                  $endtime = date($time_format, $endt);
                  echo $starttime . ' - ' .$endtime;
                }
                else {
                  echo "NOT SET";
                }
            break;
            case "tf_col_ev_thumb":
                // - show thumb -
                $post_image_id = get_post_thumbnail_id(get_the_ID());
                if ($post_image_id) {
                    $thumbnail = wp_get_attachment_image_src( $post_image_id, 'post-thumbnail', false);
                    if ($thumbnail) (string)$thumbnail = $thumbnail[0];
                    echo '<img src="';
                    echo bloginfo('stylesheet_directory');
                    echo '/timthumb/timthumb.php?src=';
                    echo $thumbnail;
                    echo '&h=60&w=60&zc=1" alt="" />';
                }
            break;
            case "tf_col_ev_desc";
                the_excerpt();
            break;

        }
}


 


// Customize Update Messages



function events_updated_messages( $messages ) {

  global $post, $post_ID;

  $messages['tf_events'] = array(
    0 => '', // Unused. Messages start at index 1.
    1 => sprintf( __('Event updated. <a href="%s">View item</a>'), esc_url( get_permalink($post_ID) ) ),
    2 => __('Custom field updated.'),
    3 => __('Custom field deleted.'),
    4 => __('Event updated.'),
    /* translators: %s: date and time of the revision */
    5 => isset($_GET['revision']) ? sprintf( __('Event restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    6 => sprintf( __('Event published. <a href="%s">View event</a>'), esc_url( get_permalink($post_ID) ) ),
    7 => __('Event saved.'),
    8 => sprintf( __('Event submitted. <a target="_blank" href="%s">Preview event</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
    9 => sprintf( __('Event scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview event</a>'),
      // translators: Publish box date format, see http://php.net/date
      date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
    10 => sprintf( __('Event draft updated. <a target="_blank" href="%s">Preview event</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
  );

  return $messages;
}
add_filter('post_updated_messages', 'events_updated_messages');



// iCal functions
  
function tf_events_ical() {
  
  // - start collecting output -
  ob_start();
  
  // - file header -
  header('Content-type: text/calendar');
  header('Content-Disposition: attachment; filename="ical.ics"');
  
  // - content header -
  ?>
  BEGIN:VCALENDAR
  VERSION:2.0
  PRODID:-//<?php the_title(); ?>//NONSGML Events //EN
  X-WR-CALNAME:<?php the_title(); _e(' - Events','twentyten'); ?>
  X-ORIGINAL-URL:<?php echo the_permalink(); ?>
  X-WR-CALDESC:<?php the_title(); _e(' - Events','twentyten'); ?>
  CALSCALE:GREGORIAN
  
  <?php
  
  // - grab date barrier -
  $today6am = strtotime('today 6:00') + ( get_option( 'gmt_offset' ) * 3600 );
  $limit = get_option('pubforce_rss_limit');
  
  // - query -
  global $wpdb;
  $querystr = "
      SELECT *
      FROM $wpdb->posts wposts, $wpdb->postmeta metastart, $wpdb->postmeta metaend
      WHERE (wposts.ID = metastart.post_id AND wposts.ID = metaend.post_id)
      AND (metaend.meta_key = 'tf_events_enddate' AND metaend.meta_value > $today6am )
      AND metastart.meta_key = 'tf_events_enddate'
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
  
  // - custom variables -
  $custom = get_post_custom(get_the_ID());
  $sd = $custom["tf_events_startdate"][0];
  $ed = $custom["tf_events_enddate"][0];
  
  // - grab gmt for start -
  $gmts = date('Y-m-d H:i:s', $sd);
  $gmts = get_gmt_from_date($gmts); // this function requires Y-m-d H:i:s, hence the back & forth.
  $gmts = strtotime($gmts);
  
  // - grab gmt for end -
  $gmte = date('Y-m-d H:i:s', $ed);
  $gmte = get_gmt_from_date($gmte); // this function requires Y-m-d H:i:s, hence the back & forth.
  $gmte = strtotime($gmte);
  
  // - Set to UTC ICAL FORMAT -
  $stime = date('Ymd\THis\Z', $gmts);
  $etime = date('Ymd\THis\Z', $gmte);
  
  // - item output -
  ?>
  BEGIN:VEVENT
  DTSTART:<?php echo $stime; ?>
  DTEND:<?php echo $etime; ?>
  SUMMARY:<?php echo the_title(); ?>
  DESCRIPTION:<?php the_excerpt_rss('', TRUE, '', 50); ?>
  END:VEVENT
  <?php
  endforeach;
  else :
  endif;
  ?>
  END:VCALENDAR
  <?php
  // - full output -
  $tfeventsical = ob_get_contents();
  ob_end_clean();
  echo $tfeventsical;
  }
  
  function add_tf_events_ical_feed () {
      // - add it to WP RSS feeds -
      add_feed('tf-events-ical', 'tf_events_ical');
  }
  
  add_action('init','add_tf_events_ical_feed');
  


?>
