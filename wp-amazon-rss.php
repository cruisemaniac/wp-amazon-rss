<?php

/*
//Plugin Name: Wordpress Amazon RSS Feed Poster
Plugin URI: http://imashw.in/wordpress/wp-amazon-rss
Description: Simple plugin for creating Posts from Amazon Product RSS Feeds
Author: Ashwin Murali
Version: 0.1
Author URI: http://imashw.in
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; 
*/


//add action hook for settings menu
add_action('admin_menu', 'wparf_admin_actions');

function wparf_admin_actions() {
	add_options_page('WP Amazon RSS Feed Poster', 'WP Amazon RSS Feed Poster', 'manage_options',
					 'wparf_feed_options', 'wparf_admin_menu');
}

//render the admin menu for plugin
function wparf_admin_menu() {

	if(!current_user_can('manage_options')) {
		wp_die('Access Denied');
	}
	
	
	//all settings and data in this page will be saved under key = wparf_options in the wp_options table
	$wpoption_name = 'wparf_options';	
	$options = get_option($wpoption_name);
	
	// failsafe to check if the wp_options table actually has data from this plugin.
	if($options == null) {
		$options = array(); //initialize null array
		
	}
	
	$amazonfeeds = array(); // list of feeds from the options array	
	$maxfeeds = ''; // maximum number of items we want to draw into a single post from a feed url
	$maxexcerptlength = ''; //if excerpt exists, the maximum length we will show from the excerpt tag in the feed
	$freq = ''; // frequency for executing the cron job to pull feeds
	
	if(array_key_exists('feeds', $options)) $amazonfeeds = $options['feeds'];
	wparf_logger(print_r($amazonfeeds, true));
	if(array_key_exists('maxfeeds', $options)) $maxfeeds = $options['maxfeeds'];
	if(array_key_exists('maxlength', $options)) $maxexcerptlength = $options['maxlength'];
	if(array_key_exists('freq', $options)) $freq = $options['freq'];
	
	//data pulled in to variables for processing
	//now to validate it and take evasive action to prevent problems
	
	if($maxfeeds > 50 || $maxfeeds < 3) $maxfeeds = 10;
	
	if($maxexcerptlength > 300 || $maxexcerptlength < 50) $maxexcerptlength = 100;
	
	if(empty($freq)) $freq = 'daily';
	
	//lets sort out the saving routine first...
	
	if(!empty($_POST['Submit'])) {
		
		wparf_logger('inside post');
		
		//todo: add cronjob functionality here
		
		$feedurls = array();
		$categories = array();
		
		//checking for data in post payload
		
		if(array_key_exists('wparf_feedurl', $_POST)) $feedurls = $_POST['wparf_feedurl'];
		if(array_key_exists('wparf_feedcats', $_POST)) $categories = $_POST['wparf_feedcats'];
		
		$rssfeeeds = array();
		
		//now the loop to set the payload into an array and push it into the wp_options table
		$k = 0; // separate counter variable to save data sequentially no matter how many urls are entered in what position
		
		for($i = 0; $i < 10; $i++) {
		
			//check to make sure we dont save empty spaces
			$feedurl = $feedurls[$i];
			$cats = $categories[$i];
			
			if(!empty($feedurl)) {
				$rssfeeds[$k]['feedurl'] = $feedurl;
				$rssfeeds[$k]['cats'] = array($cats);
				$k++;
			}
		}
		
		$options['feeds'] = $rssfeeds;
		
		//checking for other options in the post payload now
		if(array_key_exists('wparf_maxfeeds', $_POST)) $options['maxfeeds'] = $_POST['wparf_maxfeeds'];
		if(array_key_exists('wparf_maxlength', $_POST)) $options['maxlength'] = $_POST['wparf_maxlength'];
		if(array_key_exists('wparf_freq', $_POST)) $options['freq'] = $_POST['wparf_freq'];
		
		update_option($wpoption_name, $options); //saving the settings to wp_options table
		
		echo '<h3>Save successful</h3>';
		
	

	}
	
	//query categories for displaying in form
	$wp_cats = get_categories('hide_empty=0'); // Wordpress category list
	
	//coding to save data done! now to display the form for user input...
?>
	<div class="wrap">
		<h2>WP Amazon RSS Feed to Post Plugin</h2>
		<p>Simple plugin for creating Posts from Amazon Product RSS Feeds.<br/>Enter the settings in the fields below to save them.</p>
		
		<form method="post" action="">
			<input type="hidden" name="action" value="update" />
			<?php wp_nonce_field('update-options'); ?>
			<fieldset style="border:thin black solid;padding:2px;">
		    <legend style="font-weight:bold">WPARF Options</legend>
			<p><strong>Maximum number of rss feeds: </strong>
				<input class="widefat" name="maxfeeds" type="text" value="<?php echo $maxfeeds; ?>" />
			</p>
			<p><strong>Maximimum Length of Excerpt: </strong>
				<input class="widefat" name="maxexcerpt" type="text" value="<?php echo $maxexcerptlength; ?>" />
			</p>
			<p><strong>Posting Frequency: </strong>
				<select name="freq">
					<option value="hourly"<?php if ($freq=='hourly') echo " selected=\"true\"";?>>Hourly</option>
					<option value="twicedaily"<?php if ($freq=='twicedaily') echo " selected=\"true\"";?>>Twice Daily</option>
					<option value="daily"<?php if ($freq=='daily') echo " selected=\"true\"";?>>Daily</option>
				</select>
			</p>
			</fieldset>
			
			<table>
			<tr style="width: 100%;">
				<th style="width: 80%;">RSS Feed URL</td>
				<th style="width: 20%;align: right;">Post Categories</td>
			</tr>
			
			<?php
			
			//run the loop to show saved feeds from the wp_options table.
			var_dump($maxfeeds);
			for($n = 0; $n < $maxfeeds; $n++) {
				$feed = '';
				$cats = array();
				
				if($n < count($amazonfeeds)) {
					$feed = $amazonfeeds[$n]['feedurl'];
					var_dump($feed);					
					$cats = $amazonfeeds[$n]['cats'];
				}
				
				?>
				
				<tr>
					<td align="right"><input type="text" size="72" name="wparf_feedurl[<?php echo $n; ?>]" value="<?php echo $feed; ?>" /></td>
					<td align="right"><select name="wparf_feedcats[<?php echo $n; ?>]">
					<?php
					
						foreach($wp_cats as $key => $value) {
							
							$nicename = $value->category_nicename;
							$catid = $value->cat_ID;
							$sel = '';
							
							if(in_array($catid, $cats)) $sel = 'selected="true"';
							echo "<option $sel value=\"$catid\">$nicename</option>";
						}
					
					?>
					</select>
					</td>
				</tr>
				
				<?php
			}
			
			?>
			</table>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes'); ?>" name="Submit" />
			</p>
		</form>
		
	</div>
<?php
}


//function to pull rss feeds

//tested and works - todo: replace feed with variables to pull from options!

function wparf_pull_rss_feeds() {
	include_once(ABSPATH . WPINC . '/feed.php');
	
	$items = '';
	if(function_exists('fetch_feed')) {
		$feed = fetch_feed('http://cruisemaniac.com/rss');
		
		if(!is_wp_error($feed)) : $feed->init();
		
		$feed->set_output_encoding('UTF-8');
		$feed->handle_content_type();
		$feed->set_cache_duration(21600);
		$limit = $feed->get_item_quantity(10);
		$items = $feed->get_items(0, $limit);
		endif;		
	}
	
	foreach($items as $item) {
		echo $item->get_title() .'<br />';
		echo $item->get_permalink() . '<br />';
	}
}


//uninstall routine

/* 
 * wparf_plugin_uninstall
 * todo: implementation
 */
 

// development logger. set debug = true to have log files written to disk.
function wparf_logger($data) {
	$t = date('Y-m-d H:i:s', time());
	$f=fopen('wparf_log.txt','a');
	fwrite($f,$t.': '.$data."\r\n");
	fclose($f);
	return;
}
 
?>