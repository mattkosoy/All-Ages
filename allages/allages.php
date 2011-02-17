<?php
/**
 * @package All Ages Video Support
 * @version 1.0.2	
 */
/*
Plugin Name: All Ages
Plugin URI: https://github.com/mattkosoy/All-Ages
Description: This will create a page, and a thumbnail for each video that a particular user has uploaded.   The public side displays a grid of thumbnails that link internally.  This allows for a high level of customization for branding & redisplaying video content.
Author: Matt Kosoy
Version: 1.0.2
Author URI: http://mattkosoy.com/
*/


/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
/* define vars & add plugin actions */

// define paths to plugin dir & URL
define ('AA_DIR',WP_PLUGIN_DIR . '/allages');
define ('AA_URL',WP_PLUGIN_URL . '/allages');

add_action('init', 'AA_Videos');
add_action( 'init', 'AA_Video_taxonomy' );
add_action("template_redirect", 'AA_redirect');
#add_action('wp_login', 'AA_update_videos');


// hook into wp and add in the administration menus
if ( is_admin() ){ // admin actions
	add_action('admin_menu', 'AA_create_menu');
	add_action('admin_head', 'AA_update_videos_js');
    add_action('admin_init', 'AA_register_settings' );
	add_action('wp_ajax_AA_update_videos', 'AA_update_videos');
	add_action('right_now_content_table_end', 'AA_dashboard');
	add_action( 'contextual_help', 'AA_add_help_text', 10, 3 );
	add_action('restrict_manage_posts','AA_restrict_manage_posts');
	add_action( 'request', 'AA_video_sort_request' );
	add_filter('post_updated_messages', 'AA_Video_messages');
}
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
/* register custom taxonomy for videos */
function AA_Video_taxonomy() {
 $labels = array(
    'name' => _x( 'Groupings', 'taxonomy general name' ),
    'singular_name' => _x( 'Group', 'taxonomy singular name' ),
    'search_items' =>  __( 'Search Groupings' ),
    'all_items' => __( 'All Groupings' ),
    'parent_item' => __( 'Parent Grouping' ),
    'parent_item_colon' => __( 'Parent Grouping:' ),
    'edit_item' => __( 'Edit Grouping' ),
    'update_item' => __( 'Update Grouping' ),
    'add_new_item' => __( 'Add New Grouping' ),
    'new_item_name' => __( 'New Grouping Name' ),
  ); 	

  register_taxonomy('grouping','Video',array(
    'hierarchical' => true,
    'labels' => $labels
  ));
}




/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
/* adding custom post type for Videos */
function AA_Videos() {
	$labels = array(
		'name' => _x('Videos', ''),
		'singular_name' => _x('Video', ' '),
		'add_new' => _x('Add New', 'Video'),
		'add_new_item' => __('Add New Video'),
		'edit_item' => __('Edit Video'),
		'new_item' => __('New Video'),
		'view_item' => __('View Video'),
		'search_items' => __('Search Video'),
		'not_found' =>  __('No Videos found'),
		'not_found_in_trash' => __('No Videos found in Trash'), 
		'parent_item_colon' => '',
		'menu_name' => 'Videos'
	);
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true, 
		'show_in_menu' => true, 
		'query_var' => 'Video',
		'capability_type' => 'post',
		'has_archive' => true, 
		'hierarchical' => false,
		'menu_position' =>5,
		'taxonomies'=>array('grouping'),
		'supports' => array('title','editor','author','thumbnail','excerpt', 'custom-fields', 'revisions'),
		'_builtin' => false, // It's a custom post type, not built in
		'_edit_link' => 'post.php?post=%d',
		'rewrite' => array('slug'=>'video')  // the rewrite rule for Video objects is set to "Video".  
	); 
	register_post_type( 'Video' , $args );
}
/* */
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
// Clean up Messages for custom post type 'Videos'
function AA_Video_messages( $messages ) {
  global $post, $post_ID;
  $messages['Video'] = array(
    0 => '', // Unused. Messages start at index 1.
    1 => sprintf( __('Video updated. <a href="%s">View Video</a>'), esc_url( get_permalink($post_ID) ) ),
    2 => __('Custom field updated.'),
    3 => __('Custom field deleted.'),
    4 => __('Video updated.'),
    /* translators: %s: date and time of the revision */
    5 => isset($_GET['revision']) ? sprintf( __('Video restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    6 => sprintf( __('Video published. <a href="%s">View Video</a>'), esc_url( get_permalink($post_ID) ) ),
    7 => __('Video saved.'),
    8 => sprintf( __('Video submitted. <a target="_blank" href="%s">Preview Video</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
    9 => sprintf( __('Video scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Video</a>'),
      // translators: Publish box date format, see http://php.net/date
      date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
    10 => sprintf( __('Video draft updated. <a target="_blank" href="%s">Preview Video</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
  );
  return $messages;
}
/* */
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
//display contextual help
function AA_add_help_text($contextual_help, $screen_id, $screen) { 
  //$contextual_help .= var_dump($screen); // use this to help determine $screen->id
  switch($screen->id){ 
  	case 'Video':
	case 'edit-Video':
	 $contextual_help =
      '<p>' . __('Things to remember when adding or editing a Video:') . '</p>' .
      '<ul>' .
      '<li>' . __('Specify a title.') . '</li>' .
      '<li>' . __('Choose a grouping.') . '</li>' .
      '<li>' . __('Be sure that the Video id is stored in the "id" custom field') . '</li>' .
      '</ul>' .
      '<p><strong>' . __('For more information:') . '</strong></p>' .
      '<p>' . __('<a href="mailto:matt.kosoy@gmail.com" target="_blank">Email Gonzo</a>') . '</p>' .
      '<p>' . __('<a href="http://codex.wordpress.org/Posts_Edit_SubPanel" target="_blank">Edit Posts Documentation</a>') . '</p>' .
      '<p>' . __('<a href="http://wordpress.org/support/" target="_blank">Wordpress Support Forums</a>') . '</p>' ;
  	break;
	}
  return $contextual_help;

}
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
/* template redirection/selection */
function AA_redirect(){
	global $wp;
	if ($wp->query_vars["post_type"] == "Video"){
		include(TEMPLATEPATH . "/".$wp->query_vars["post_type"].".php");
		die();
	}
} 
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
function AA_dashboard() {
	global $wp;
	if ($wp->query_vars["post_type"] == "Video" ){
		return;
	}
        $num_posts = wp_count_posts('Video');
        $num = number_format_i18n( $num_posts->publish );
        $text = _n( 'Published Videos', 'Published Videos', intval($num_posts->publish) );
        if ( current_user_can( 'edit_posts' ) ) {
            $num = "<a href='edit.php?post_type=Video'>$num</a>";
            $text = "<a href='edit.php?post_type=Video'>$text</a>";
        }
        echo '<td>' . $num . '</td>';
        echo '<td>' . $text . '</td>';
        echo '</tr>';
        if ($num_posts->pending > 0) {
            $num = number_format_i18n( $num_posts->pending );
            $text = _n( 'Videos Pending', 'Videos Pending', intval($num_posts->pending) );
            if ( current_user_can( 'edit_posts' ) ) {
                $num = "<a href='edit.php?post_status=pending&post_type=Video'>$num</a>";
                $text = "<a href='edit.php?post_status=pending&post_type=Video'>$text</a>";
            }
            echo '<td>' . $num . '</td>';
            echo '<td>' . $text . '</td>';
            echo '</tr>';
        }
}


/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
/**
 * @function AA_create_menu
 * @descrip: adds a submenu under the 'options' panel in wp-admin for managing 'Video settings'
 */
function AA_create_menu() {
    add_management_page('Add data from Video to your site', 'Video', 'administrator', 'Video', 'AA_settings_page');
}
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
/**
 * @function AA_register_settings
 * @descrip: registers our Video settings in the system
 */
function AA_register_settings() {
	register_setting( 'AA_VideoSettings', 'vimeo_username' );
	
}
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
/**
 * @function AA_update_videos_js
 * @descrip: adds a bit of JS to the wp admin header.
 */
function AA_update_videos_js(){ ?>
<script type="text/javascript" >
jQuery(document).ready(function($) {
	var data = {
		action: 'AA_update_videos',
	}
	$('#update_videos').click(function(){
		jQuery.post(ajaxurl, data, function(response) { 
			$('#message').fadeIn().html('<p>Great Success!<\/p>');
		});	
		return false;
	});
});
</script>
<?php
}
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
/**
 * @function AA_update_videos
 * @descrip: does the heavy lifting.  adds pages w/ postmeta to WPDB  
 */
function AA_update_videos() {
	// query vimeo for the list of our 'vimeo_username' videos
	global $current_user; get_currentuserinfo();
	$vimeo_username = get_option('vimeo_username');
	$Video_xml = 'http://vimeo.com/api/v2/'.$vimeo_username.'/videos.xml';
	if(function_exists(simplexml_load_file)){
		$data = simplexml_load_file($Video_xml);
		AA_insertSimpleXMlObj($data);
	}
}


/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
function AA_insertSimpleXMlObj($data){
	global $current_user; get_currentuserinfo();
	global $wpdb;
	if($data){
		$i=0; // counter for default display order
		// if data is returned then loop through json object 

		// get every "Video" post in the system
		$args=array(
		  'post_type' => 'Video',
		  'numberposts'     => -1,
		  'caller_get_posts'=> 1
		);
		$existing_videos = get_posts($args);			
		
		foreach($data->video as $update){
			$Video_object = '<iframe src="http://player.vimeo.com/video/'.$update->id.'?show_title=0&amp;show_byline=0&amp;show_portrait=0&amp;color=ff0179&amp;fullscreen=1" width="640" height="360" frameborder="0"></iframe>';
			$query_for_post_id = "SELECT post_id FROM  ".$wpdb->postmeta." WHERE meta_key = 'id' and meta_value = '". $update->id ."' LIMIT 0,1";
			$result = $wpdb->get_results($query_for_post_id);
			if(isset($result[0]->post_id)){ // we've got a match
				// it exists. update it with the information from Video.
				$row = wp_get_single_post($result[0]->post_id);
				$post = array(
				  'ID' => $result[0]->post_id, 
				  'comment_status' => 'closed', 
				  'ping_status' => 'closed', 
				  'post_author' => $current_user->ID, 
				  'post_content' => $row->post_content,		// keep the existing post content  
				  'menu_order' => $row->menu_order,
				  'post_date' => $update->upload_date, 
				  'post_date_gmt' => $update->upload_date, 
				  'post_excerpt' => $update->url, 
				  'post_name' => $row->post_title,
				  //'post_parent' =>$row->post_parent, 
				  'post_status' => $row->post_status,  
				  'post_title' => $row->post_title,	// keep the same title 
				  'post_type' => 'Video',
				  'tags_input' => explode(',',$update->tags), 
				); 
				$success = wp_update_post($post);
				#echo 'Updated Video:  '. $update->title."\n";
				#print_r($post);
				foreach($update as $k=>$v){	
					#echo "UPDATE META key = ".$k." & value = ".$v." \n";
					update_post_meta($row->ID , $k, addslashes($v));
				}
				update_post_meta($row->ID, 'vimeo_object', $Video_object);
				#echo "- - - - - - - - - - - - - - - - - - - - "."\n";
			} else {
			// add new video post
				$post = array(
				  'ID' => null, 
				  'comment_status' => 'closed', 
				  'ping_status' => 'closed', 
				  'post_author' => $current_user->ID, 
				  'post_content' =>  $update->description,  
				  'menu_order' => $i++,
				  'post_date' => $update->upload_date, 
				  'post_date_gmt' => $update->upload_date, 
				  'post_excerpt' => $update->url, 
				  'post_name' => $update->title,
				  //'post_parent' =>$Video_parentPage, 
				  'post_status' => 'draft',  
				  'post_title' => addslashes($update->title),
				  'post_type' => 'Video',
				  'tags_input' => explode(',',$update->tags), 
				); 
				// go ahead and insert new page record to local db.
				$success = wp_insert_post( $post );
				$sql = "SELECT id FROM ".$wpdb->prefix."posts WHERE post_type = 'Video' ORDER BY id DESC LIMIT 0,1";
				$recent_id = $wpdb->get_row($sql, 'ARRAY_A');			
				// add Video info to post meta
				#echo "Inserted: ".$update->title."\n";
				#print_r($post);
				foreach($update as $k=>$v){	
					#echo "ADD META key = ".$k." & value = ".$v." \n";
					add_post_meta($recent_id['id'] , $k, addslashes($v));
				}
				// add Video object to a custom post field (post meta)
				add_post_meta($recent_id['id'], 'vimeo_object', $Video_object);
				#echo "- - - - - - - - - - - - - - - - - - - - "."\n";
			} // end switch on insert/update	
		}  // end for each.
		echo "Great Success! The site has been updated";
	} else {
		// error.  womp.
		echo "Failure has occured.";
	}
}


/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
/**
 * @function AA_settings_page
 * @descrip:admin page HTML for adding/editing Video username 
 */
function AA_settings_page() { 
	$vimeo_username = get_option('vimeo_username'); 
	$pages = get_posts(array('post_type'=>'page', 'post_parent'=>0));
	if(is_array($pages)){
		$page_options = array();
		foreach($pages as $page){
			if($page->ID == $Video_parentPage){ $s = 'selected="SELECTED"'; } else { $s = ''; }
			$page_options[] = '<option value="'.$page->ID.'" '.$s.' >'.$page->post_title.'</option>'."\n";
		}
	}
?>
<div class="wrap">
<h2>Manage Video Settings</h2>
<div class="updated below-h2" id="message" style="display:none;"></div>
<form method="post" action="options.php">
    <?php settings_fields( 'AA_VideoSettings' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Video Username</th>
        <td><input type="text" name="vimeo_username" value="<?php echo $vimeo_username; ?>" /></td>
        </tr>
    </table>
    <p class="submit">
    <input type="submit"  class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>
</form>
</div>
<div class="wrap">
<h2>Refresh Video List</h2>
<form method="post" action="options.php">
    <?php /* settings_fields( 'aa-Videodata' ); ?>
    <input type="hidden" name="Video_data" value="<?php echo get_option('Video_data'); ?>" />
    */ ?>
    <p class="submit">
    <input type="submit" id="update_videos" class="button-primary" value="<?php _e('Update Videos') ?>" />
    </p>
</form>
</div>
<?php } 

/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */


function AA_restrict_manage_posts() {
   // only display these taxonomy filters on desired custom post_type listings
    global $typenow;
    if ($typenow == 'Video') {

        // create an array of taxonomy slugs you want to filter by - if you want to retrieve all taxonomies, could use get_taxonomies() to build the list
        $filters = array('grouping');

        foreach ($filters as $tax_slug) {
            // retrieve the taxonomy object
            $tax_obj = get_taxonomy($tax_slug);
            $tax_name = $tax_obj->labels->name;
            // retrieve array of term objects per taxonomy
            $terms = get_terms($tax_slug);

            // output html for taxonomy dropdown filter
            echo " Sort by: "; 
            echo "<select name='$tax_slug' id='$tax_slug' class='postform'>";
            echo "<option value=''>Show All</option>";
            foreach ($terms as $term) {
                // output each select option line, check against the last $_GET to show the current option selected
                echo '<option value='. $term->slug, $_GET[$tax_slug] == $term->slug ? ' selected="selected"' : '','>' . $term->name .' (' . $term->count .')</option>';
            }
            echo "</select>";
        }
    }
}
/* */
function AA_video_sort_request($request) {
	if (is_admin() && $GLOBALS['PHP_SELF'] == '/wp-admin/edit.php' && isset($request['post_type']) && $request['post_type']=='Video') {
		$request['term'] = get_term($request['Video'],'grouping')->name;
	}
	return $request;
}
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
/* EOF */  ?>
