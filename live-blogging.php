<?php
/*
Plugin Name: Live Blogging
Plugin URI: http://wordpress.org/extend/plugins/live-blogging/
Description: Plugin to support automatic live blogging
Version: 1.9
Author: Chris Northwood
Author URI: http://www.pling.org.uk/
Text-Domain: live-blogging
*/

/*
Live Blogging for WordPress
Copyright (C) 2010 Chris Northwood <chris@pling.org.uk>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

/*
 TODO:
 
 Migration of old content
 New documentation
 - How to use
   * style format
   * date format
   * unhooks
 - FAQ: ensure liveblog tag is there/disabling liveblog just means disabling
        auto-updating
 - FAQ: migration
 - FAQ: Editing posts does/can not edit Twitter
 - Known Issue: Setting posts to appear at a point back in time will not appear
                in the correct place when auto updating
 - What is Meteor/when to use it
 - RSS feed for custom post types: WP-URL/feed/?post_type=liveblog_entry&liveblog=ID
 - Filter for custom comment displaying
 - Comment updating won't work with imported live blogs
 
 For version 3.1:
 * @replying to a tweet from a live blog leaves a comment on that live blog
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
    
    if ('disabled' != get_option('liveblogging_method'))
    {
        wp_enqueue_script('live-blogging', plugins_url('live-blogging.js', __FILE__), array('jquery', 'json2'));
    }
    
    if ('meteor' == get_option('liveblogging_method'))
    {
        wp_enqueue_script('meteor', 'http://' . get_option('liveblogging_meteor_host') . '/meteor.js');
    }
    elseif ('poll' == get_option('liveblogging_method'))
    {
        wp_localize_script('live-blogging', 'live_blogging', array('ajaxurl' => addslashes(admin_url('admin-ajax.php'))));
        add_action('wp_ajax_live_blogging_poll', 'live_blogging_ajax');
        add_action('wp_ajax_nopriv_live_blogging_poll', 'live_blogging_ajax');
    }
    
}

// Register settings for the settings API
add_action('admin_init', 'live_blogging_register_settings');
function live_blogging_register_settings()
{
    register_setting('live-blogging', 'liveblogging_method', 'live_blogging_sanitise_method');
    register_setting('live-blogging', 'liveblogging_comments');
    register_setting('live-blogging', 'liveblogging_enable_twitter');
    register_setting('live-blogging', 'liveblogging_date_style');
    register_setting('live-blogging', 'liveblogging_style');
    register_setting('live-blogging', 'liveblogging_meteor_host');
    register_setting('live-blogging', 'liveblogging_meteor_controller');
    register_setting('live-blogging', 'liveblogging_meteor_controller_port', 'intval');
    register_setting('live-blogging', 'liveblogging_id');
    register_setting('live-blogging', 'liveblogging_unhooks');
}

function live_blogging_sanitise_method($input)
{
    switch ($input)
    {
        case 'poll':
        case 'meteor':
        case 'disabled':
            return $input;
        default:
            return 'disabled';
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
    if (false === get_option('liveblogging_unhooks'))
    {
        add_option('liveblogging_unhooks', array('sociable_display_hook'));
    }
}

//
// ADMIN SECTION
//

// Add menus to the admin area
add_action('admin_menu', 'live_blogging_add_menu');
function live_blogging_add_menu()
{
    add_submenu_page('options-general.php', __('Live Blogging', 'live-blogging'), __('Live Blogging', 'live-blogging'), 'manage_options', 'live-blogging-options', 'live_blogging_options');
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
    <h3><?php _e('Automatic Updating'); ?></h3>
    <table class="form-table">
        
        <tr valign="top">
            <th scope="row"><label for="liveblogging_method"><?php _e('Method to use for live blog reader updating', 'live-blogging'); ?></label></th>
            <td><select name='liveblogging_method'>
                <option value="poll"<?php if ('poll' == get_option('liveblogging_method')) { ?> selected="selected"<?php } ?>><?php _e('Poll (default, requires no special configuration)', 'live-blogging'); ?></option>
                <option value="meteor"<?php if ('meteor' == get_option('liveblogging_method')) { ?> selected="selected"<?php } ?>><?php _e('Stream using Meteor (lower server load and faster updates, but needs special configuration)', 'live-blogging'); ?></option>
                <option value="disabled"<?php if ('disabled' == get_option('liveblogging_method')) { ?> selected="selected"<?php } ?>><?php _e('Disable automatic updates', 'live-blogging'); ?></option>
            </select></td>
        </tr>
        
        <tr valign="top">
            <th scope="row"><label for="liveblogging_comments"><?php _e('Enable auto-updating of comments (this will not work on some themes, please read the documentation!)', 'live-blogging'); ?></label></th>
            <td><input type="checkbox" name="liveblogging_comments" value="1"<?php if ('1' == get_option('liveblogging_comments')) { ?> checked="checked"<?php } ?> /></td>
        </tr>
    
    </table>
    
    <h3><?php _e('Posting to Twitter'); ?></h3>
    <table class="form-table">
        
        <tr valign="top">
            <th scope="row"><?php _e('Connect with Twitter', 'live-blogging'); ?></th>
            <td><?php
                if (false !== get_option('liveblogging_twitter_token'))
                {
                    _e('You are already connected to Twitter. Click the image below to change the account you are connected to.', 'live-blogging');
                    echo '<br/>';
                }
                else
                {
                    _e('You are not yet connected to Twitter. Click the image below to connect.', 'live-blogging');
                    echo '<br/>';
                }
                $connection = new TwitterOAuth(LIVE_BLOGGING_TWITTER_CONSUMER_KEY, LIVE_BLOGGING_TWITTER_CONSUMER_SECRET);
                $request_token = $connection->getRequestToken(plugins_url('twittercallback.php', __FILE__));
                update_option('liveblogging_twitter_request_token', $request_token['oauth_token']);
                update_option('liveblogging_twitter_request_secret', $request_token['oauth_token_secret']);
                if (200 == $connection->http_code) {
                ?>
                    <a href="<?php echo $connection->getAuthorizeURL($request_token['oauth_token']); ?>"><img src="<?php echo plugins_url('twitteroauth/signin.png', __FILE__); ?>" alt="Sign In with Twitter" /></a>
                <?php } else { 
                    _e('Unable to connect to Twitter at this time.', 'live-blogging');
                } ?>
            </td>
        </tr>
        
<?php if (false !== get_option('liveblogging_twitter_token')) { ?>
        <tr valign="top">
            <th scope="row"><label for="liveblogging_enable_twitter"><?php _e('Enable posting to Twitter', 'live-blogging'); ?></label></th>
            <td><input type="checkbox" name="liveblogging_enable_twitter" value="1"<?php if ('1' == get_option('liveblogging_enable_twitter')) { ?> checked="checked"<?php } ?> /></td>
        </tr>
<?php } ?>
    
    </table>
    
    <h3><?php _e('Live Blog Style'); ?></h3>
    <table class="form-table">
        
        <tr valign="top">
            <th scope="row"><label for="liveblogging_date_style"><?php _e('Date specifier for live blog entries (see <a href="http://uk3.php.net/manual/en/function.date.php">PHP\'s date function</a> for allowable strings)', 'live-blogging'); ?></label></th>
            <td><input type="text" name="liveblogging_date_style" value="<?php echo esc_attr(get_option('liveblogging_date_style')); ?>" /></td>
        </tr>
        
        <tr valign="top">
            <th scope="row"><label for="liveblogging_style"><?php _e('Style for live blogging entries ($DATE gets replaced with the entry date/time, in the format specified above, $CONTENT with the body of the entry and $AUTHOR with the name of that author)', 'live-blogging'); ?></label></th>
            <td><textarea name="liveblogging_style" cols="60" rows="8"><?php echo esc_html(get_option('liveblogging_style')); ?></textarea></td>
        </tr>
    
    </table>
    
    <h3><?php _e('Meteor Configuration'); ?></h3>
    <table class="form-table">
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
    
    <h3><?php _e('Advanced Settings'); ?></h3>
    <table class="form-table">
        
        <tr valign="top">
            <th scope="row"><label for="liveblogging_unhooks"><?php _e('Actions to be unhooked when displaying live blog content (advanced feature that can be used to work around badly coded plugins that mangle individual live blog entries in undesirable ways, e.g., some bookmarking plugins)', 'live-blogging'); ?></label></th>
            <td>
            <?php
                $i = 1;
                foreach (get_option('liveblogging_unhooks') as $unhook)
                {
?>
                <div id="liveblogging_unhook_<?php echo $i; ?>">
                    <input type="text" name="liveblogging_unhooks[]" value="<?php echo esc_attr($unhook); ?>" />
                    <input type="button" id="liveblogging_unhook_delete_<?php echo $i; ?>" title="<?php _e('Delete Unhook', 'live-blogging'); ?>" style="width:16px;height:16px;background:url('<?php echo plugins_url('delete.png', __FILE__) ?>');cursor:pointer;border:0;padding:0;margin:0;" />
                    <script type="text/javascript">
                    /*<![CDATA[ */
                        jQuery('#liveblogging_unhook_delete_<?php echo $i; ?>').click(function () {
                            jQuery('#liveblogging_unhook_<?php echo $i; ?>').remove()
                        });
                    /*]]>*/
                    </script>
                </div><br/>
<?php
                    $i++;
                }
            ?>
                <input type="button" id="liveblogging_unhook_add" title="<?php _e('Add Unhook', 'live-blogging'); ?>" style="width:16px;height:16px;background:url('<?php echo plugins_url('add.png', __FILE__) ?>');cursor:pointer;border:0;padding:0;margin:0;" />
                <script type="text/javascript">
                /*<![CDATA[ */
                    var unhook_i = <?php echo $i; ?>;
                    jQuery('#liveblogging_unhook_add').click(function () {
                        jQuery('<div id="liveblogging_unhook_' + unhook_i + '"> \
                                <input type="text" name="liveblogging_unhooks[]"  /> \
                                <input type="button" id="liveblogging_unhook_delete_' + unhook_i + '" title="<?php _e('Delete Unhook', 'live-blogging'); ?>" style="width:16px;height:16px;background:url(\'<?php echo plugins_url('delete.png', __FILE__) ?>\');cursor:pointer;border:0;padding:0;margin:0;" /> \
                                </div>').insertBefore('#liveblogging_unhook_add');
                        jQuery('#liveblogging_unhook_delete_' + unhook_i).click(function () {
                            jQuery(this.parentNode).remove()
                        });
                        unhook_i++;
                    });
                /*]]>*/
                </script>
            </td>
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
    // Remove content hooks
    foreach (get_option('liveblogging_unhooks') as $unhook)
    {
        if (function_exists($unhook))
        {
            remove_filter('the_content', $unhook);
        }
    }
    $style = preg_replace('/\$CONTENT/', apply_filters('the_content', $entry->post_content), $style);
    // Add content back in hooks
    foreach (get_option('liveblogging_unhooks') as $unhook)
    {
        if (function_exists($unhook))
        {
            add_filter('the_content', $unhook);
        }
    }
    $user = get_userdata($entry->post_author);
    $style = preg_replace('/\$AUTHOR/', apply_filters('the_author', $user->display_name), $style);
    return $style;
}

// Insert the live blog in a post
add_shortcode('liveblog', 'live_blogging_shortcode');
function live_blogging_shortcode($atts, $id = null)
{
    global $post, $liveblog_client_id;
    if (!empty($id))
    {
        $id = 'legacy-' . $id;
    }
    else
    {
        $id = $post->ID;
    }
    $s = '';
    if ('meteor' == get_option('liveblogging_method'))
    {
        $s .= '<script type="text/javascript">
               /*<![CDATA[ */
                // Configure Meteor
                Meteor.hostid = "' . $liveblog_client_id . '";
                Meteor.host = "' . get_option('liveblogging_meteor_host') . '";
                Meteor.registerEventCallback("process", live_blogging_handle_data);
                Meteor.joinChannel("' . get_option('liveblogging_id') . '-liveblog-' . $id . '", 2);
                Meteor.mode = "stream";
                jQuery(document).ready(function() {
                    Meteor.connect();
                });
               /*]]>*/
               </script>';
    }
    elseif ('poll' == get_option('liveblogging_method'))
    {
        $s .= '<script type="text/javascript">
               /*<![CDATA[ */
                setTimeout(live_blogging_poll, 15000, "' . $id . '")
               /*]]>*/
               </script>';
    }
    $q = new WP_Query(array(
          'post_type' => 'liveblog_entry',
          'liveblog' => $id,
          'posts_per_page' => -1
        ));
    $s .= '<div id="liveblog-' . $id . '">';
    while ($q->have_posts())
    {
        $q->next_post();
        $s .= '<div id="liveblog-entry-' . $q->post->ID . '">' . live_blogging_get_entry($q->post) . '</div>';
    }
    $s .= '</div>';
    return $s;
}

function live_blogging_build_comments($liveblog)
{
    $args = apply_filters('live_blogging_build_comments', array());
    ob_start();
    wp_list_comments($args, get_comments(array('status' => 'approve', 'post_id' => intval($liveblog), 'order' => 'ASC')));
    $comments = ob_get_clean();
    return $comments;
}

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

add_action('publish_liveblog_entry', 'live_blogging_entry_to_meteor', 100, 2);
function live_blogging_entry_to_meteor($id, $post)
{
    if ('meteor' == get_option('liveblogging_method'))
    {
        $fd = fsockopen(get_option('liveblogging_meteor_controller'), get_option('liveblogging_meteor_controller_port'));
        $liveblogs = wp_get_object_terms(array($id), 'liveblog');
        if (isset($_POST['live_blogging_entry_post']))
        {
            $liveblogs[] = (object)array('name' => $_POST['live_blogging_entry_post']);
        }
        foreach ($liveblogs as $liveblog)
        {
            $e = array(
                'liveblog' => $liveblog->name,
                'id' => $id,
                'type' => 'entry',
                'html' => live_blogging_get_entry($post)
            );
            fwrite($fd, 'ADDMESSAGE ' . get_option('liveblogging_id') . '-liveblog-' . $liveblog->name . ' ' . addslashes(json_encode($e)) . "\n");
        }
        fwrite($fd, "QUIT\n");
        fclose($fd);
    }
}

add_action('delete_post', 'live_blogging_delete_to_meteor');
add_action('trash_post', 'live_blogging_delete_to_meteor');
function live_blogging_delete_to_meteor($id)
{
    if ('meteor' == get_option('liveblogging_method'))
    {
        $post = get_post($id);
        if ('liveblog_entry' == $post->post_type)
        {
            $fd = fsockopen(get_option('liveblogging_meteor_controller'), get_option('liveblogging_meteor_controller_port'));
            $liveblogs = wp_get_object_terms(array($id), 'liveblog');
            foreach ($liveblogs as $liveblog)
            {
                $e = array(
                    'liveblog' => $liveblog->name,
                    'id' => $id,
                    'type' => 'delete-entry'
                );
                fwrite($fd, 'ADDMESSAGE ' . get_option('liveblogging_id') . '-liveblog-' . $liveblog->name . ' ' . addslashes(json_encode($e)) . "\n");
            }
            fwrite($fd, "QUIT\n");
            fclose($fd);
        }
    }
}

add_action('edit_comment', 'live_blogging_comment_to_meteor');
add_action('comment_post', 'live_blogging_comment_to_meteor');
add_action('wp_set_comment_status', 'live_blogging_comment_to_meteor');
function live_blogging_comment_to_meteor($id)
{
    if ('1' == get_option('liveblogging_comments') && 'meteor' == get_option('liveblogging_method'))
    {
        $comment = get_comment($id);
        $fd = fsockopen(get_option('liveblogging_meteor_controller'), get_option('liveblogging_meteor_controller_port'));
        $r = array(
            'liveblog' => $comment->comment_post_ID,
            'type' => 'comments',
            'html' => live_blogging_build_comments($comment->comment_post_ID)
        );
        fwrite($fd, 'ADDMESSAGE ' . get_option('liveblogging_id') . '-liveblog-' . $comment->comment_post_ID . ' ' . addslashes(json_encode($r)) . "\n");
        fwrite($fd, "QUIT\n");
        fclose($fd);
    }
}

//
// TWITTER SUPPORT
//

include('twitteroauth/twitteroauth.php');
define('LIVE_BLOGGING_TWITTER_CONSUMER_KEY', 'ToetcXqpSlUG8rObbnxwyA');
define('LIVE_BLOGGING_TWITTER_CONSUMER_SECRET', 'JnkDixVMDuTA103zPQSRs9eWLzy1Lgv2E97h1q2GC4');

add_action('publish_liveblog_entry', 'live_blogging_tweet', 10, 2);
function live_blogging_tweet($id, $post)
{
    if ('1' == get_option('liveblogging_enable_twitter') && '' == get_post_meta($id, '_liveblogging_tweeted', true))
    {
        $connection = new TwitterOAuth(LIVE_BLOGGING_TWITTER_CONSUMER_KEY, LIVE_BLOGGING_TWITTER_CONSUMER_SECRET, get_option('liveblogging_twitter_token'), get_option('liveblogging_twitter_secret'));
        if (strlen($post->post_content) > 140)
        {
            $tweet = $connection->post('statuses/update', array('status' => substr($post->post_content, 0, 139) . '…'));
        }
        else
        {
            $tweet = $connection->post('statuses/update', array('status' => $post->post_content));
        }
    }
    if (isset($tweet->id))
    {
        update_post_meta($id, '_liveblogging_tweeted', $tweet->id);
    }
}

add_action('delete_post', 'live_blogging_delete_tweet');
add_action('trash_post', 'live_blogging_delete_tweet');
function live_blogging_delete_tweet($id)
{
    $tweet = get_post_meta($id, '_liveblogging_tweeted', true);
    if ('1' == get_option('liveblogging_enable_twitter') && '' != $tweet)
    {
        $post = get_post($id);
        if ('liveblog_entry' == $post->post_type)
        {
            $connection = new TwitterOAuth(LIVE_BLOGGING_TWITTER_CONSUMER_KEY, LIVE_BLOGGING_TWITTER_CONSUMER_SECRET, get_option('liveblogging_twitter_token'), get_option('liveblogging_twitter_secret'));
            $connection->post('statuses/destroy/' . $tweet);
        }
    }
}

// POLLING SUPPORT
function live_blogging_ajax()
{
    header('Content-Type: application/json');
    $liveblog_id = $_POST['liveblog_id'];
    
    // Build currently active posts
    $q = new WP_Query(array(
          'post_type' => 'liveblog_entry',
          'liveblog' => $liveblog_id,
          'posts_per_page' => -1,
          'orderby' => 'date',
          'order' => 'ASC'
        ));
    $r = array();
    while ($q->have_posts())
    {
        $q->next_post();
        $r[] = array(
                'liveblog' => $liveblog_id,
                'id' => $q->post->ID,
                'type' => 'entry',
                'html' => live_blogging_get_entry($q->post)
            );
    }
    
    foreach (get_post_meta($liveblog_id, '_liveblogging_deleted') as $deleted)
    {
        $r[] = array(
                'liveblog' => $liveblog_id,
                'id' => $deleted,
                'type' => 'delete-entry'
            );
    }
    if ('1' == get_option('liveblogging_comments'))
    {
        $r[] = array(
                'liveblog' => $liveblog_id,
                'type' => 'comments',
                'html' => live_blogging_build_comments($liveblog_id)
            );
    }
    echo json_encode($r);
    die();
}

add_action('delete_post', 'live_blogging_poll_mark_to_delete');
add_action('trash_post', 'live_blogging_poll_mark_to_delete');
function live_blogging_poll_mark_to_delete($id)
{
    $post = get_post($id);
    if ('liveblog_entry' == $post->post_type)
    {
        $liveblogs = wp_get_object_terms(array($id), 'liveblog');
        foreach ($liveblogs as $liveblog)
        {
            add_post_meta(intval($liveblog->name), '_liveblogging_deleted', $id);
        }
    }
}

add_action('publish_liveblog_entry', 'live_blogging_poll_mark_to_undelete', 10, 2);
function live_blogging_poll_mark_to_undelete($id, $post)
{
    if ('liveblog_entry' == $post->post_type)
    {
        $liveblogs = wp_get_object_terms(array($id), 'liveblog');
        foreach ($liveblogs as $liveblog)
        {
            delete_post_meta(intval($liveblog->name), '_liveblogging_deleted', $id);
        }
    }
}