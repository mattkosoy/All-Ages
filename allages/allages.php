<?php
/**
 * @package All Ages Vimeo Support
 * @version 0.4
 */
/*
Plugin Name: All Ages
Plugin URI: http://allagesproductions.com/
Description: This will create a page, and a thumbnail for each video that a particular user has uploaded.   The public side displays a grid of thumbnails that link internally.  This allows for a high level of customization for branding & redisplaying video content.
Author: Matt Kosoy
Version: 0.4
Author URI: http://mattkosoy.com/
*/
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
// define paths to plugin dir & URL
define ('AA_DIR',WP_PLUGIN_DIR . '/allages');
define ('AA_URL',WP_PLUGIN_URL . '/allages');

// hook into wp and add in the administration menus
if ( is_admin() ){ // admin actions
	add_action('admin_menu', 'AA_create_menu');
	add_action('admin_head', 'AA_update_videos_js');
    add_action('admin_init', 'AA_register_settings' );
	add_action('wp_ajax_AA_update_videos', 'AA_update_videos');
}
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
/**
 * @function AA_create_menu
 * @descrip: adds a submenu under the 'options' panel in wp-admin for managing 'vimeo settings'
 */
function AA_create_menu() {
    add_management_page('Add data from Vimeo to your site', 'Vimeo', 'administrator', 'vimeo', 'AA_settings_page');
}
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
/**
 * @function AA_register_settings
 * @descrip: registers our vimeo settings in the system
 */
function AA_register_settings() {
	register_setting( 'AA_vimeoSettings', 'vimeo_username' );
	register_setting( 'AA_vimeoSettings', 'vimeo_parentPage' );
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
	$vimeo_xml = 'http://vimeo.com/api/v2/'.$vimeo_username.'/videos.xml';
	
	if(function_exists(simplexml_load_file)){
		$data = simplexml_load_file($vimeo_xml);
		AA_insertSimpleXMlObj($data);
	} else {
		ini_set('allow_url_fopen', 'on');
		include(AA_DIR.'/parser.php');
		$xml_loaded = curlPage($vimeo_xml, "http://allagesproductions.com/", '30');
		$parser = new XMLParser($xml_loaded);
		$parser->Parse();
		AA_insertPHP4XML($parser);
	}
}
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
function AA_insertSimpleXMlObj($data){
	global $current_user; get_currentuserinfo();
	global $wpdb;
	$vimeo_parentPage = get_option('vimeo_parentPage');

	if($data){
			$i=0; // counter for default display order
			// if data is returned then loop through json object 
			foreach($data->video as $update){
				
				
				// check to see if this page already exists
				$sql = "SELECT post_title FROM ".$wpdb->prefix."posts WHERE post_title = '" .  addslashes($update->title) . "'";
				if($wpdb->get_row($sql, 'ARRAY_A')) { 
					// it exists. skip.
				} else {
				// create a wp post object for a new vimeo record
/*				$vimeo_object = '
	<object width="640" height="360">
		<param name="allowfullscreen" value="true" />
		<param name="allowscriptaccess" value="always" />
		<param name="movie" value="http://vimeo.com/moogaloop.swf?clip_id='.$update->id.'" />
		<embed src="http://vimeo.com/moogaloop.swf?clip_id='.$update->id.'&amp;server=vimeo.com&amp;show_title=0&amp;show_byline=0&amp;show_portrait=0&amp;color=ff0179&amp;fullscreen=1" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="640" height="360"></embed>
	</object>
	';
*/
				$vimeo_object = '<iframe src="http://player.vimeo.com/video/'.$update->id.'?show_title=0&amp;show_byline=0&amp;show_portrait=0&amp;color=ff0179&amp;fullscreen=1" width="640" height="360" frameborder="0"></iframe>';

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
				  'post_parent' =>$vimeo_parentPage, 
				  'post_status' => 'draft',  
				  'post_title' => addslashes($update->title),
				  'post_type' => 'page',
				  'tags_input' => explode(',',$update->tags), 
				); 
				
					// go ahead and insert new page record to local db.
					$success = wp_insert_post( $post );
					$sql = "SELECT id FROM ".$wpdb->prefix."posts WHERE post_type = 'page' ORDER BY id DESC LIMIT 0,1";
					$recent_id = $wpdb->get_row($sql, 'ARRAY_A');			
					// add vimeo info to post meta
					foreach($update as $k=>$v){	
						add_post_meta($recent_id['id'] , $k, addslashes($v));
					}
					// add vimeo object to a custom post field (post meta)
					add_post_meta($recent_id['id'], 'vimeo_object', $vimeo_object);
					//echo "Inserted: ".$update->title."\n";
				}
			}
			echo "Great Success!";
		} else {
			// error.  womp.
			echo "You have failed.";
		}
}
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
function AA_insertPHP4XML($parser){
	global $current_user; get_currentuserinfo();
	global $wpdb;
	$vimeo_parentPage = get_option('vimeo_parentPage');
	$vimeo_info = $parser->document->tagChildren;
	if(is_array($vimeo_info)){
		$i=0; // counter for default display order
		// if data is returned then loop through json object 
		
		foreach($vimeo_info as $data){
			// create a wp post object for each vimeo record
			$update = $data->tagChildren;
			$vimeo_object = '
<object width="640" height="360">
	<param name="allowfullscreen" value="true" />
	<param name="allowscriptaccess" value="always" />
	<param name="movie" value="http://vimeo.com/moogaloop.swf?clip_id='.$update[0]->tagData.'&amp;server=vimeo.com&amp;show_title=0&amp;show_byline=0&amp;show_portrait=0&amp;color=ff0179&amp;fullscreen=1" />
	<embed src="http://vimeo.com/moogaloop.swf?clip_id='.$update[0]->tagData.'&amp;server=vimeo.com&amp;show_title=0&amp;show_byline=0&amp;show_portrait=0&amp;color=ff0179&amp;fullscreen=1" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="640" height="360"></embed>
</object>
';
			$post = array(
			  'ID' => null, 
			  'comment_status' => 'closed', 
			  'ping_status' => 'closed', 
			  'post_author' => $current_user->ID, 
			  'post_content' =>  $vimeo_object.'<span>'.$update[2]->tagData.'</span>',  
			  'menu_order' => $i++,
			  'post_date' => $update[4]->tagData, 
			  'post_date_gmt' => $update[4]->tagData, 
			  'post_excerpt' => $update[3]->tagData, 
			  'post_name' => $update[1]->tagData,
			  'post_parent' =>$vimeo_parentPage, 
			  'post_status' => 'draft',  
			  'post_title' => addslashes($update[1]->tagData),
			  'post_type' => 'page',
			  'tags_input' => explode(',',$update[20]->tagData), 
			); 
			// check to see if this page already exists
			$sql = "SELECT post_title FROM ".$wpdb->prefix."posts WHERE post_title = '" .  addslashes($update[1]->tagData) . "'";		
			if($wpdb->get_row($sql, 'ARRAY_A')) { 
				/* skip. this one has already been added added. */ 
			} else {
				// go ahead and insert new page record to local db.
				$success = wp_insert_post( $post );
				$sql = "SELECT id FROM ".$wpdb->prefix."posts WHERE post_type = 'page' ORDER BY id DESC LIMIT 0,1";
				$recent_id = $wpdb->get_row($sql, 'ARRAY_A');
				// add vimeo info to post meta
				foreach($update as $u){	
					print_r($u);
					echo $recent_id['id']." ".$u->tagName." ".$u->tagData." \n\n";
					if(add_post_meta($recent_id['id'] , $u->tagName, $u->tagData)){ 
					#	echo "SAVED\n";
					} else { 
					#	echo "FAIL\n"; 
					}
				} 
				//echo "Inserted: ".$update->title."\n";
			}
		}
		echo "Great Success!";
	} else {
		// error.  womp.
		echo "You have failed.";
	}
}


/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
function curlPage($url, $referer, $timeout, $header=0){
    if(!isset($timeout))
    $timeout=30;
    $curl = curl_init();
    if(strstr($referer,"://")){
    curl_setopt ($curl, CURLOPT_REFERER, $referer);
    }
    curl_setopt ($curl, CURLOPT_URL, $url);
    curl_setopt ($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt ($curl, CURLOPT_USERAGENT, sprintf("Mozilla/%d.0",rand(4,5)));
    curl_setopt ($curl, CURLOPT_HEADER, (int)$header);
    curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, 0);
    $html = curl_exec ($curl);
    curl_close ($curl);
    return $html;
}
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
/**
 * @function AA_settings_page
 * @descrip:admin page HTML for adding/editing vimeo username 
 */
function AA_settings_page() { 
	$vimeo_username = get_option('vimeo_username'); 
	$vimeo_parentPage = get_option('vimeo_parentPage'); 
	$pages = get_posts(array('post_type'=>'page', 'post_parent'=>0));
	if(is_array($pages)){
		$page_options = array();
		foreach($pages as $page){
			if($page->ID == $vimeo_parentPage){ $s = 'selected="SELECTED"'; } else { $s = ''; }
			$page_options[] = '<option value="'.$page->ID.'" '.$s.' >'.$page->post_title.'</option>'."\n";
		}
	}
?>
<div class="wrap">
<h2>Manage Vimeo Settings</h2>
<div class="updated below-h2" id="message" style="display:none;"></div>
<form method="post" action="options.php">
    <?php settings_fields( 'AA_vimeoSettings' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Vimeo Username</th>
        <td><input type="text" name="vimeo_username" value="<?php echo $vimeo_username; ?>" /></td>
        </tr>
        <tr valign="top">
        <th scope="row">Parent Page</th>
        <td><select name="vimeo_parentPage" id="vimeo_parentPage">
        <?php	foreach($page_options as $o){
        		echo $o;
        	} ?>
        </select>
        </td>
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
    <?php /* settings_fields( 'aa-vimeodata' ); ?>
    <input type="hidden" name="vimeo_data" value="<?php echo get_option('vimeo_data'); ?>" />
    */ ?>
    <p class="submit">
    <input type="submit" id="update_videos" class="button-primary" value="<?php _e('Update Videos') ?>" />
    </p>
</form>
</div>
<?php } 

/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */