<?php
/*
    Plugin Name: Google Hourly Trends
    Plugin URI: http://Articletyme.com/
    Description: Google hourly Trends will post automatically each hour the most searched keywords according to Google Trends.
    Author: Giftedwon
    Version: 1.0
    Author URI: http://articletyme.com/

	This plugin is released under GPL: http://www.opensource.org/licenses/gpl-license.php
*/

// Let's set the default values
function google_defaults() {
    $default = array(
        'title'     => 'Hot trends for',
        'category'  => 1,
        'author'    => 1
    );
return $default;
}

// Runs when plugin is activated and creates new database field
register_activation_hook(__FILE__,'google_plugin_install');
function google_plugin_install() { $options = get_option('google_options');
    add_option('google_options', google_defaults());
    $start = time();
    wp_schedule_event($start, 'hourly', 'google_schedule');
}

add_action('google_schedule', 'google_post');

// Runs on plugin deactivation and deletes the database field
register_deactivation_hook( __FILE__, 'google_plugin_remove' );
function google_plugin_remove() {
    wp_clear_scheduled_hook('google_schedule');
}

// Reset to defaults
if (isset($_POST['reset-gtd'])) {
    update_option('google_options', google_defaults());
    echo '<div class="updated" id="message"><p><strong>Settings Reset to Default</strong></p></div>';
}

// Hook for adding admin menus
// More menu page examples here: http://codex.wordpress.org/Adding_Administration_Menus
add_action('admin_menu', 'google_plugin_admin_menu');
function google_plugin_admin_menu() {
    add_options_page('google Current Trends', 'google Current Trends', 8, 'google-Current-trends', 'google_plugin_html_page');
}

function google_plugin_html_page() { ?>

<div class="wrap"><div id="icon-options-general" class="icon32"><br /></div>
<h2><?php _e("google Trends Plugin Settings"); ?></h2>


<form method="post" action="options.php">
<?php wp_nonce_field('update-options'); $options = get_option('google_options'); ?>

	<div class="metabox-holder" style="width: 600px;">
		<div class="postbox">
		<h3><?php _e("Set your default values here."); ?></h3>
			<div class="inside" style="padding: 10px;">

                <p><?php _e("Set your Category where you want the google Trends to be posted to each hour."); ?></p>
				<p><?php _e("Post title"); ?>:
                <input type="text" name="google_options[title]" value="<?php echo $options['title'] ?>" size="70" />
                </p>
                <p><?php _e("Select a Category"); ?>:
                <?php wp_dropdown_categories(array('selected' => $options['category'], 'name' => 'google_options[category]', 'orderby' => 'Name' , 'hierarchical' => 1, 'show_option_all' => __("Default"), 'hide_empty' => '0' )); ?>
                </p>
                <p><?php _e("Select post author"); ?>:
                <?php wp_dropdown_users(array('selected' => $options['author'], 'name' => 'google_options[author]', 'orderby' => 'display_name' , 'show' => 'display_name', 'show_option_all' => __("Select author") )); ?>
                </p>

                <p><input type="submit" class="button" value="<?php _e('Save Settings') ?>" /></p>
			</div>
		</div>
	</div>

<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="google_options" />
</form>

<form method="post">
<input type="hidden" name="reset-google" value="1">
<p><input type="submit" class="button" onclick="return confirm('Are you sure you want to reset to default settings?')" value="<?php _e('Reset') ?>" /></p>
</form>

</div>
<script type="text/javascript">
    var $jq = jQuery.noConflict();
    $jq(document).ready(function() { $jq(".updated").fadeIn(1000).fadeTo(1000, 1).fadeOut(1000); });
</script>

<?php
}

// Get the feed function
function google_feed() {

    if(function_exists('fetch_feed')) {
    	include_once(ABSPATH . WPINC . '/feed.php');

    	$feed           = fetch_feed('http://www.google.com/trends/hottrends/atom/hourly');
    	$limit          = $feed->get_item_quantity(50); // specify number of items
    	$items          = $feed->get_items(0, $limit); // create an array of items
       }

    if ($limit == 0) return;

    $content = '';
    foreach ($items as $item) {
        $oldtitle = $item->get_title();
        $newtitle = str_replace('Daily news for', 'Hot Topic', $oldtitle);
        $content.= '<h4>'.$newtitle.'</h4>
        <p>'.substr($item->get_description(), 0, 3000).'</p>';
        print '<a href="http://' . $data . '" target="_blank">' . $data . '</a>';
       
    }
     return $content;
}

// Post the feed
function google_post() {

    $options    = get_option('google_options');
    $category   = $options['category'];
    $author     = $options['author'];
    $title      = ''.$options['title'].' '.date('D j M H i').'';
    $content    = google_feed();

    $args = array(
        'post_status' => 'publish',
    	'post_type' => 'post',
    	'post_author' => $author,
    	'ping_status' => get_option('default_ping_status'),
        'tags_input'	=> 'Google Trends',
    	'post_category' => array($category),
    	'post_title' => $title,
        'to_ping' =>  'http://rpc.pingomatic.com/',
    	'post_content' => $content
    );

    // Insert the post
    $insert = wp_insert_post($args);

}

?>