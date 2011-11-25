<?php
/*
Plugin Name: 23 Video fetcher
Plugin URI: http://www.clcbio.com
Description: This plugin creates a link between your 23 video site and a wordpress site. Simply provide the plugin with the URL of the video site and a tag to search for. The plugin will then search your video site for that tag and create a gallery on your wordpress site, by using the [23video] shortcode
Version: 1.0
Author: Ingiber Olafsson
Author URI: http://www.clcbio.com
License: GPL2
*/

add_action('admin_menu', 'plugin_admin_add_page');
add_action('admin_init', 'plugin_admin_init');
add_action('admin_enqueue_scripts', 'enqueue_jquery_ui');
add_action('wp_print_styles', 'include_style');
// Add support for 23 video gallery shortcodes
add_shortcode( '23video', 'twentythreevideo_parser' );

function enqueue_jquery_ui()
{
	wp_enqueue_script('jquery');
	wp_enqueue_script( 'jquery_ui', plugins_url('/js/jquery-ui-1.8.16.custom.min.js', __FILE__) );
	wp_enqueue_script( 'orders', plugins_url('/js/order.js', __FILE__) );
}

function include_style()
{

	if (preg_match("/(.+\/)/", plugin_basename(__FILE__), $matches)) {
		$folder = $matches[1];
	}
	$style = plugin_dir_url( '23video_fetcher.php' ) . $folder .  'style/23video.css';
    wp_register_style('twentythreestyles', $style);
    wp_enqueue_style('twentythreestyles');
}

function plugin_admin_add_page() 
{
	add_options_page('23 Video settings', '23Video', 'manage_options', '23-video', 'render_admin_page');
}

function render_admin_page()
{

	echo '<h2>23 Video settings</h2>';
	echo '<form action="options.php" method="post">';
	settings_fields('23video_plugin_options');
	do_settings_sections('23video_plugin');
	echo '<input name="Submit" id="save_tag" type="submit" style="margin-left: 231px; margin-top: 20px; text-align:center; width: 150px;" value="Save" />';
	echo '</form>';
	
	echo '<div id="preview" style="position:relative;">';
	echo '<h3>Selected videos <a style="font-size: 12px; margin-left: 10px;" id="turn_on_order" href="javascript:void(false);">Order videos</a></h3>';
	
	$option = get_option('23video_plugin_options');
	if ($option['tag'] == '') {
		echo '<p>Please provide a tag to search for.</p>';
		return;
	}
	
	$videos = getVideos();				
	$sorted = orderVideos($videos);
	
	if (empty($sorted)) {
		echo '<p>It looks like there are no videos with this tag in your <em>23 Video</em> installation.</p>';
	} else	{
		echo '<ul id="selected-videos">';
		foreach ($sorted as $video) {
			echo '<li style="width: 300px; display:inline-block;" rel="' . $video->photo_id . '"><img title="'. $video->title . '" src="' . $option['installation_url'] . '/' . $video->medium_download . '" /><br />'. substr_replace($video->title, "...", 40) . '</li>';
		}
		echo '</ul>';
		if ($videos->cached) {
			echo '<span style="font-size: 11px; color: #333;">Please note that these videos are cached. If you have made any changes that aren\'t showing up, wait a few minutes and try again.</span>';
		}
	}
	
	
	echo '</div>';
}

function plugin_admin_init()
{
	register_setting( '23video_plugin_options', '23video_plugin_options', 'twentythreevideo_options_validate' );
	add_settings_section('23video_plugin_main', 'Setting up a <em>23 video</em> gallery in Wordpress', 'twentythreevideo_plugin_section_text', '23video_plugin');
	add_settings_field('23video_plugin_installation_url', 'Installation URL', 'twentythreevideo_installation_url_set', '23video_plugin', '23video_plugin_main');
	add_settings_field('23video_plugin_tag', '<em>23 Video</em> tag', 'twentythreevideo_plugin_input_text', '23video_plugin', '23video_plugin_main');
	add_settings_field('23video_plugin_ui', 'Display mode', 'twentythreevideo_plugin_input_ui', '23video_plugin', '23video_plugin_main');
	
	$options = get_option('23video_plugin_options');
	if ($options['ui'] == 'none') {
		add_settings_field('23video_plugin_ui_width', '<span class="label hidden" style="position:relative; left: 222px;">Overlay width</span>', 'twentythreevideo_plugin_input_ui_width', '23video_plugin', '23video_plugin_main');
		add_settings_field('23video_plugin_ui_height', '<span class="label hidden" style="position:relative; left: 222px;">Overlay height</span>', 'twentythreevideo_plugin_input_ui_height', '23video_plugin', '23video_plugin_main');
	} else	{
		add_settings_field('23video_plugin_ui_width', '<span class="label" style="position:relative; left: 222px;">Overlay width</span>', 'twentythreevideo_plugin_input_ui_width', '23video_plugin', '23video_plugin_main');
		add_settings_field('23video_plugin_ui_height', '<span class="label" style="position:relative; left: 222px;">Overlay height</span>', 'twentythreevideo_plugin_input_ui_height', '23video_plugin', '23video_plugin_main');
	}
	add_settings_field('23video_plugin_order', '', 'twentythreevideo_plugin_input_text_order', '23video_plugin', '23video_plugin_main');	
}

function twentythreevideo_plugin_input_ui_width()
{
	$options = get_option('23video_plugin_options');
	if ($options['ui'] == 'none') {
		echo "<input id='23video_plugin_ui_width' style='position:relative; left: 100px;' class='hidden' name='23video_plugin_options[ui_width]' size='5' type='text' value='" . $options['ui_width'] . "' /> <span class='label hidden' style='position: relative; left: 100px;font-size:11px'>(default: 531)</span>";
	} else 	{
		echo "<input id='23video_plugin_ui_width' style='position:relative; left: 100px;' name='23video_plugin_options[ui_width]' size='5' type='text' value='" . $options['ui_width'] . "' /> <span class='label' style='position: relative; left: 100px;font-size:11px'>(default: 531)</span>";
	}
	
}

function twentythreevideo_plugin_input_ui_height()
{
	$options = get_option('23video_plugin_options');
	if ($options['ui'] == 'none') {
		echo "<input id='23video_plugin_ui_height' style='position:relative; left: 100px;' class='hidden' name='23video_plugin_options[ui_height]' size='5' type='text' value='" . $options['ui_height'] . "' /><span class='label hidden' style='position: relative; left: 100px;font-size:11px'>(default: 300)</span>";
	} else	{
		echo "<input id='23video_plugin_ui_height' style='position:relative; left: 100px;' name='23video_plugin_options[ui_height]' size='5' type='text' value='" . $options['ui_height'] . "' /><span  class='label' style='position: relative; left: 100px;font-size:11px'> (default: 300)</span>";
	}
	
}

function twentythreevideo_installation_url_set()
{
	$options = get_option('23video_plugin_options');
	echo "<input id='23video_plugin_installation_url' name='23video_plugin_options[installation_url]' size='40' type='text' value='" . $options['installation_url'] . "' />";
}

function twentythreevideo_plugin_input_ui()
{
	$options = get_option('23video_plugin_options');
	echo "<input type='radio' name='23video_plugin_options[ui]' value='none' " . checked($options['ui'], 'none', false) .  "  /> None - Video opens up in a new window/tab <br />";
	echo "<input type='radio' name='23video_plugin_options[ui]' value='shadowbox' " . checked($options['ui'], 'shadowbox', false) . " /> Video opens in an overlay (Recommended) - <a href='http://wordpress.org/extend/plugins/shadowbox-js/' target='_blank'>Requires the Shadowbox plugin</a>  <br />";
}

function twentythreevideo_options_validate($input)
{
	$newinput['tag'] = trim($input['tag']);
	if(!is_string($newinput['tag'])) {
		$newinput['tag'] = '';
	}
	$newinput['order'] = trim($input['order']);
	if(!is_string($newinput['order'])) {
		$newinput['order'] = '';
	}
	
	$newinput['installation_url'] = trim($input['installation_url']);
	if(!is_string($newinput['installation_url'])) {
		$newinput['installation_url'] = '';
	}
	
	$newinput['ui'] = trim($input['ui']);
	if(!is_string($newinput['ui'])) {
		$newinput['ui'] = '';
	}
	
	$newinput['ui_width'] = (int) trim($input['ui_width']);
	if($newinput['ui_width'] == 0) {
		$newinput['ui_width'] = '';
	}
	
	$newinput['ui_height'] = (int) trim($input['ui_height']);
	if($newinput['ui_height'] == 0) {
		$newinput['ui_height'] = '';
	}
	return $newinput;
}

function twentythreevideo_plugin_section_text()
{
	echo '<p>This plugin allows you to search your <em>23 Video</em> installation for all videos containing the following tag. <br /><br /> To search for a tag, follow these instructions: </p> <ol> <li>Enter the URL to the 23 Video installation (e.g. http://www.yourvideosite.com) - without the trailing slash</li> <li>Enter the tag you want to search for.</li> </ol> <p>You will see still images of the videos the plugin fetches below when you have saved your information.</p>';
}

function twentythreevideo_plugin_input_text()
{
	$options = get_option('23video_plugin_options');
	echo "<input id='23video_plugin_tag' name='23video_plugin_options[tag]' size='40' type='text' value='" . $options['tag'] . "' />";
}

function twentythreevideo_plugin_input_text_order()
{
	$options = get_option('23video_plugin_options');
	echo "<input id='23video_plugin_order' name='23video_plugin_options[order]' size='40' type='hidden' value='" . $options['order'] . "' />";

}

function getVideos()
{
	$option = get_option('23video_plugin_options');	
	$qry_str = "?format=json&raw=1&tags=" . $option['tag'];
	$ch = curl_init();

	// Set query data here with the URL
	curl_setopt($ch, CURLOPT_URL, $option['installation_url'] . '/api/photo/list/' . $qry_str); 
	
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, '3');
	$content = trim(curl_exec($ch));
	curl_close($ch);
	return json_decode($content);
}

function orderVideos($videos)
{
	if (sizeof($videos->photos) == 0) {
		return array();
	}
	
	$option = get_option('23video_plugin_options');	

	$order = explode(",", $option['order']);
	
	// Videos haven't been sorted, proceed with default sorting
	$sorted = array();
	if (sizeof($order) == 1 && $order[0] == '') {
		foreach ($videos->photos as $video) {
			$sorted[] = $video;
		}
		return $sorted;
	}
	
	// Videos have been sorted, proceed with custom sorting
	$sorted = array();
	foreach ($order as $value) {
		foreach ($videos->photos as $video) {
			if ($video->photo_id == $value) {
				$sorted[] = $video;
			}
			continue;
		}
		continue;
	}
	
	return $sorted;

}

function twentythreevideo_parser( $atts ){
	$videos = getVideos();	
	$sorted = orderVideos($videos);	
	$html = '<ul id="twentythreevideo-list">';
	$option = get_option('23video_plugin_options');
	foreach ($sorted as $video) {	
		if ($option['ui_width'] == '' && $option['ui_height'] == '') {
			$html .= '<li><a target="_blank" rel="' . $option['ui'] . ';height=300;width=531" href="' . $option['installation_url'] . '/628830.ihtml?token=' . $video->token . '&photo_id=' . $video->photo_id . '"><img title="'. $video->title . '" src="' . $option['installation_url'] .'/' . $video->small_download . '" /></a><br />'. substr_replace($video->title, "...", 30) . '</li>';
		} else	{
			$html .= '<li><a target="_blank" rel="' . $option['ui'] . ';height=' . $option['ui_height'] . ';width=' . $option['ui_width'] . '" href="' . $option['installation_url'] . '/628830.ihtml?token=' . $video->token . '&photo_id=' . $video->photo_id . '"><img title="'. $video->title . '" src="' . $option['installation_url'] .'/' . $video->small_download . '" /></a><br />'. substr_replace($video->title, "...", 30) . '</li>';
		}			
	}
	$html .= '</ul>';
	$html .= '<div class="clearfix"></div>';
	return $html;
}


?>