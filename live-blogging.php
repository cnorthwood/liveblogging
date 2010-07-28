<?php
/*
Plugin Name: Live Blogging
Plugin URI: http://wordpress.org/extend/plugins/live-blogging/
Description: Plugin to support automatic live blogging
Version: 2.0
Author: Chris Northwood
Author URI: http://www.pling.org.uk/

Icon CC-BY-NC-SA: http://www.flickr.com/photos/64892304@N00/2073251155
*/

/*
 TODO:
 
 New posting to Twitter functionality
 New hooks for push to Meteor/push to Twitter stuff
 Migration of old content
 New JavaScript/PHP for polling and pushing
 New documentation
 RSS feed functionality
 Allow disabling share this, sociable, etc, functionality in the front-end
*/

//
// BACK END
//

// Register custom post types and custom taxonomy
add_action('init', 'live_blogging_init');
function live_blogging_init()
{
    // Add the taxonomy which allows us to associate entries with posts
    register_taxonomy('liveblog', 'liveblog_entry',
                      array(
                        'public' => false
                      ));
    
    // Register custom post type
    register_post_type('liveblog_entry',
                       array(
                        'labels' => array(
                            'name' => __('Live Blog Entries', 'live_blogging'),
                            'singular_name' => __('Live Blog Entry', 'live_blogging'),
                            'add_new_item' => __('Add New Entry', 'live_blogging'),
                            'edit_item' => __('Edit Entry', 'live_blogging'),
                            'new_item' => __('New Entry', 'live_blogging'),
                            'view_item' => __('Edit Entry', 'live_blogging'),
                            'search_items' => __('Search Entries', 'live_blogging'),
                            'not_found' => __('No entries found', 'live_blogging'),
                            'not_found_in_trash' => __('No entries found in trash', 'live_blogging')
                        ),
                        'description' => __('Individual entries for a live blog', 'live_blogging'),
                        'show_ui' => true,
                        'supports' => array('editor', 'author'),
                        'taxonomies' => array('liveblog'),
                        'menu_icon' => plugins_url('icon.png', __FILE__)
                       ));
    
    load_plugin_textdomain( 'live-blogging', false, dirname(plugin_basename( __FILE__ )) . '/lang/' );
    
}

// Register settings for the settings API
add_action('admin_init', 'live_blogging_register_settings');
function live_blogging_register_settings()
{
    register_setting('live-blogging', 'liveblogging_method', 'live_blogging_sanitise_method');
    register_setting('live-blogging', 'liveblogging_date_style');
    register_setting('live-blogging', 'liveblogging_style');
    register_setting('live-blogging', 'liveblogging_meteor_host');
    register_setting('live-blogging', 'liveblogging_meteor_controller');
    register_setting('live-blogging', 'liveblogging_meteor_controller_port', 'intval');
    register_setting('live-blogging', 'liveblogging_id');
}

function live_blogging_sanitise_method($input)
{
    switch ($input)
    {
        case 'poll':
        case 'meteor':
            return $input;
        default:
            return 'poll';
    }
}

register_activation_hook(__FILE__, 'live_blogging_activate');
function live_blogging_activate()
{
    if (false === get_option('liveblogging_method'))
    {
        add_option('liveblogging_method', 'poll');
    }
    if (false === get_option('liveblogging_id'))
    {
        add_option('liveblogging_id', 'liveblog');
    }
    if (false === get_option('liveblogging_date_style'))
    {
        add_option('liveblogging_style', '<p><strong>$DATE</strong></p>$CONTENT<div style="width:100%; height:1px; background-color:#6f6f6f; margin-bottom:3px;"></div>');
    }
    if (false === get_option('liveblogging_date'))
    {
        add_option('liveblogging_date_style', 'H.i');
    }
}

//
// ADMIN SECTION
//

// Add menus to the admin area
add_action('admin_menu', 'live_blogging_add_menu');
function live_blogging_add_menu()
{
    add_submenu_page('options-general.php', __('Live Blogging Options', 'live-blogging'), __('Live Blogging Options', 'live-blogging'), 'manage_options', 'live-blogging-options', 'live_blogging_options');
    if ('meteor' == get_option('liveblogging_method'))
    {
        add_submenu_page('index.php', __('Meteor Status', 'live-blogging'), __('Meteor Status', 'live-blogging'), 'manage_options', 'live-blogging-meteor-status', 'live_blogging_meteor_status');
    }
    add_meta_box('live_blogging_post_enable', __('Enable Live Blog', 'live-blogging'), 'live_blogging_post_meta', 'post', 'side');
    add_meta_box('live_blogging_post_select', __('Select Live Blog', 'live-blogging'), 'live_blogging_entry_meta', 'liveblog_entry', 'side');
}

// Handle the options page, using the Settings API
function live_blogging_options()
{
?>
<div class="wrap">
<h2><?php _e('Live Blogging Options', 'live-blogging'); ?></h2>

<form method="post" action="options.php">
    <?php settings_fields( 'live-blogging' ); ?>
    <table class="form-table">
        
        <tr valign="top">
            <th scope="row"><label for="liveblogging_method"><?php _e('Method to use for live blog reader updating', 'live-blogging'); ?></th>
            <td><select name='liveblogging_method'>
                <option value="poll"<?php if ('poll' == get_option('liveblogging_method')) { ?> selected="selected"<?php } ?>><?php _e('Poll (default, requires no special configuration)', 'live-blogging'); ?></option>
                <option value="meteor"<?php if ('meteor' == get_option('liveblogging_method')) { ?> selected="selected"<?php } ?>><?php _e('Stream using Meteor (lower server load and faster updates, but needs special configuration)', 'live-blogging'); ?></option>
            </select></td>
        </tr>
        
        <tr valign="top">
            <th scope="row"><label for="liveblogging_date_style"><?php _e('Date specifier for live blog entries (see <a href="http://uk3.php.net/manual/en/function.date.php">PHP\'s date function</a> for allowable strings)', 'live-blogging'); ?></label></th>
            <td><input type="text" name="liveblogging_date_style" value="<?php echo esc_attr(get_option('liveblogging_date_style')); ?>" /></td>
        </tr>
        
        <tr valign="top">
            <th scope="row"><label for="liveblogging_style"><?php _e('Style for live blogging entries ($DATE gets replaced with the entry date/time, in the format specified above, $CONTENT with the body of the entry and $AUTHOR with the name of that author)', 'live-blogging'); ?></label></th>
            <td><textarea name="liveblogging_style"><?php echo esc_html(get_option('liveblogging_style')); ?></textarea></td>
        </tr>
        
        <tr valign="top">
            <th scope="row"><label for="liveblogging_meteor_host"><?php _e('Meteor Subscriber Host (the publicly accessible URL of your Meteor server - can be left blank if not using Meteor)', 'live-blogging'); ?></label></th>
            <td><input type="text" name="liveblogging_meteor_host" value="<?php echo esc_attr(get_option('liveblogging_meteor_host')); ?>" /></td>
        </tr>
        
        <tr valign="top">
            <th scope="row"><label for="liveblogging_meteor_controller"><?php _e('Meteor Controller Host (the private IP of the Meteor server - can be left blank if not using Meteor)', 'live-blogging'); ?></label></th>
            <td><input type="text" name="liveblogging_meteor_controller" value="<?php echo esc_attr(get_option('liveblogging_meteor_controller')); ?>" /></td>
        </tr>
        
        <tr valign="top">
            <th scope="row"><label for="liveblogging_meteor_controller_port"><?php _e('Meteor Controller Port (can be left blank if not using Meteor)', 'live-blogging'); ?></label></th>
            <td><input type="text" name="liveblogging_meteor_controller_port" value="<?php echo esc_attr(get_option('liveblogging_meteor_controller_port')); ?>" /></td>
        </tr>
        
        <tr valign="top">
            <th scope="row"><label for="liveblogging_id"><?php _e('Meteor Identifier (a unique string to identify this WordPress install - can be left blank if not using Meteor)', 'live-blogging'); ?></label></th>
            <td><input type="text" name="liveblogging_id" value="<?php echo esc_attr(get_option('liveblogging_id')); ?>" /></td>
        </tr>
        
    </table>
    
    <p class="submit">
        <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
<?php
}

// Meta Box for post page
function live_blogging_post_meta()
{
    global $post;
    wp_nonce_field('live_blogging_post_meta', 'live_blogging_post_nonce');
    $checked = '';
    if (get_post_meta($post->ID, '_liveblog', true) == '1')
    {
        $checked = ' checked="checked"';
    }
?>

  <p><input type="checkbox" name="live_blogging_post_enable" value="enabled"<?php echo $checked; ?> /><label for="live_blogging_post_enable"><?php _e('Enable live blogging on this post', 'live-blogging' ); ?></label></p>
  <p><em><?php _e('Please remember to include the [liveblog] shortcode where you want the liveblog in this post to appear.', 'live-blogging'); ?></em></p>
<?php
}

// Post page metabox saving
add_action('save_post', 'live_blogging_save_post_meta');
function live_blogging_save_post_meta($post_id)
{
    // Check for autosaves
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
        !isset($_POST['live_blogging_post_nonce']))
    {
        return $post_id;
    }
    
    // Verify nonce
    check_admin_referer('live_blogging_post_meta', 'live_blogging_post_nonce');
    
    // Verify permissions
    if (!current_user_can('edit_post', $post_id))
    {
        return $post_id;
    }
    
    if (isset($_POST['live_blogging_post_enable']) && 'enabled' == $_POST['live_blogging_post_enable'])
    {
        update_post_meta($post_id, '_liveblog', '1');
    }
    else
    {
        update_post_meta($post_id, '_liveblog', '0');
    }
}

// Meta box on entry page
function live_blogging_entry_meta()
{
    global $post;
    wp_nonce_field('live_blogging_entry_meta', 'live_blogging_entry_nonce');
    $lblogs = array();
    $q = new WP_Query(array(
            'meta_key' => '_liveblog',
            'meta_value' => '1',
            'post_type' => 'post',
            'posts_per_page' => -1
          ));
    
    // Get all active live blogs
    while ($q->have_posts())
    {
        $q->next_post();
        $lblogs[$q->post->ID] = esc_html($q->post->post_title);
    }
    
    // Add the current live blog, always
    $active_id = 0;
    $b = wp_get_object_terms(array($post->ID), 'liveblog');
    if (count($b) > 0)
    {
        $b = $b[0]->name;
        $lblogs[intval($b)] = get_the_title(intval($b));
        $active_id = intval($b);
    }
    
    // Error if there are no live blogs
    if (count($lblogs) == 0)
    {
        wp_die('<p>' . __('There are no currently active live blogs.', 'live-blogging') . '</p>');
    }
?>

  <label for="live_blogging_entry_post"><?php _e('Select live blog', 'live-blogging' ); ?></label><br/>
  <select name="live_blogging_entry_post">
    <?php foreach ($lblogs as $lbid => $lbname) { ?>
        <option value="<?php echo $lbid; ?>"><?php echo $lbname; ?></option>
    <?php } ?>
  </select>
<?php
}

// Entry page meta saving
add_action('save_post', 'live_blogging_save_entry_meta');
function live_blogging_save_entry_meta($post_id)
{
    // Check for autosaves
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
        !isset($_POST['live_blogging_entry_nonce']))
    {
        return $post_id;
    }
    
    // Verify nonce
    check_admin_referer('live_blogging_entry_meta', 'live_blogging_entry_nonce');
    
    // Verify permissions
    if (!current_user_can('edit_post', $post_id))
    {
        return $post_id;
    }
    
    if (!isset($_POST['live_blogging_entry_post']))
    {
        wp_die('<p>' . __('There are no currently active live blogs.', 'live-blogging') . '</p>');
    }
    
    wp_set_post_terms($post_id, $_POST['live_blogging_entry_post'], 'liveblog');
}

// Fix the title for live blogging entries to show the content (or at least
// it's excerpt)
add_filter('the_title', 'live_blogging_fix_title', 10, 2);
function live_blogging_fix_title($title, $id)
{
    $post = get_post($id);
    if ('liveblog_entry' == $post->post_type)
    {
        $title = apply_filters('get_the_excerpt', $post->post_excerpt);
    }
    return $title;
}

// Show a custom column on the liveblog_entry edit screen
add_action('manage_posts_custom_column', 'live_blogging_custom_column', 10, 2);
function live_blogging_custom_column($column_name, $post_ID)
{
    switch ($column_name)
    {
        case 'liveblog':
            $blogs = wp_get_object_terms(array($post_ID), 'liveblog');
            if (count($blogs) > 0)
            {
                $blog = $blogs[0]->name;
                echo get_the_title(intval($blog));
            }
            break;
    }
}

add_filter('manage_liveblog_entry_posts_columns', 'live_blogging_add_custom_column');
function live_blogging_add_custom_column($columns)
{
    $columns['liveblog'] = __('Live Blog', 'live-blogging');
    return $columns;
}

function live_blogging_meteor_status()
{
    // Limit new entries to users who can edit
    if (!current_user_can('manage_options'))
    {
        wp_die(__('Access Denied', 'live-blogging'));
    }
?>
<div class="wrap">
    <h2><?php _e('Meteor Status', 'live-blogging'); ?></h2>
    <pre>
<?php
    if (strlen(get_option('liveblogging_meteor_controller')) > 0)
    {
        // If Meteor is configured, connect to it and get the output of SHOWSTATS
        $fp = fsockopen(get_option('liveblogging_meteor_controller'), get_option('liveblogging_meteor_controller_port'), $errno, $errstr, 30);
        if (!$fp)
        {
            _e('Unable to establish connection to Meteor:', 'live-blogging');
            echo " ($errno)$errstr \n";
        }
        else
        {
            fwrite($fp, "SHOWSTATS\n");
            fwrite($fp, "QUIT\n");
            while (!feof($fp))
            {
                echo fgets($fp);
            }
            fclose($fp);
        }
    }
    else
    {
        _e('Connection to Meteor not configured.', 'live-blogging');
    }

?>
    </pre>
</div>
<?php
}

//
// FRONT END DISPLAYING
//

function live_blogging_get_entry($entry)
{
    $style = get_option('liveblogging_style');
    $style = preg_replace('/\$DATE/', get_the_time(get_option('liveblogging_date_style'), $entry), $style);
    $style = preg_replace('/\$CONTENT/', apply_filters('the_content', $entry->post_content), $style);
    $user = get_userdata($entry->post_author);
    $style = preg_replace('/\$AUTHOR/', apply_filters('the_author', $user->display_name), $style);
    return $style;
}

// Insert the live blog in a post
function live_blogging_shortcode($atts, $id = null)
{
    global $post;
    $s = '';
    $q = new WP_Query(array(
          'post_type' => 'liveblog_entry',
          'liveblog' => $post->ID
        ));
    while ($q->have_posts())
    {
        $q->next_post();
        $s .= live_blogging_get_entry($q->post);
    }
    return $s;
}
add_shortcode('liveblog', 'live_blogging_shortcode');

//
// METEOR SUPPORT
//

// Set the unique ID for Meteor
add_action('init', 'live_blogging_cookie');
function live_blogging_cookie()
{
    if ('meteor' == get_option('liveblogging_method'))
    {
        global $liveblog_client_id;
        if (!isset($_COOKIE['liveblog-client-id']))
        {
            $liveblog_client_id = get_option('liveblog_assigned_ids');
            if (false === $liveblog_client_id)
            {
                $liveblog_client_id = 0;
            }
            update_option('liveblog_assigned_ids', ++$liveblog_client_id);
            setcookie('liveblog-client-id', $liveblog_client_id, 0, COOKIEPATH);
        }
        else
        {
            $liveblog_client_id = $_COOKIE['liveblog-client-id'];
        }
    }
}

//
// TWITTER SUPPORT
//