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
 Showing Live blogging beneath a post
 New JavaScript/PHP for polling and pushing
 Custom design (possibly using get_template_part/locate_template or similar)
 New documentation
 RSS feed functionality (can this be done in plain WordPress, so just needs documenting?)
 Meteor Status
 Meteor client ID setting
 Allow disabling share this, sociable, etc, functionality in the front-end
*/

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

add_action('init', 'live_blogging_init');

function live_blogging_register_settings()
{
    register_setting('live-blogging', 'liveblogging_method');
    register_setting('live-blogging', 'liveblogging_meteor_host');
    register_setting('live-blogging', 'liveblogging_meteor_controller');
    register_setting('live-blogging', 'liveblogging_meteor_controller_port');
    register_setting('live-blogging', 'liveblogging_id');
}

add_action('admin_init', 'live_blogging_register_settings');

function live_blogging_add_menu()
{
    add_submenu_page('options-general.php', __('Live Blogging Options', 'live-blogging'), __('Live Blogging Options', 'live-blogging'), 'manage_options', 'live-blogging-options', 'live_blogging_options');
    add_meta_box('live_blogging_post_enable', __('Enable Live Blog', 'live-blogging'), 'live_blogging_post_meta', 'post', 'side');
    add_meta_box('live_blogging_post_select', __('Select Live Blog', 'live-blogging'), 'live_blogging_entry_meta', 'liveblog_entry', 'side');
}

add_action('admin_menu', 'live_blogging_add_menu');

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
            <th scope="row"><label for="liveblogging_meteor_host"><?php _e('Meteor Subscriber Host (the publicly accessible URL of your Meteor server - can be left blank if not using Meteor)', 'live-blogging'); ?></label></th>
            <td><input type="text" name="liveblogging_meteor_host" value="<?php echo get_option('liveblogging_meteor_host'); ?>" /></td>
        </tr>
         
        <tr valign="top">
            <th scope="row"><label for="liveblogging_meteor_controller"><?php _e('Meteor Controller Host (the private IP of the Meteor server - can be left blank if not using Meteor)', 'live-blogging'); ?></label></th>
            <td><input type="text" name="liveblogging_meteor_controller" value="<?php echo get_option('liveblogging_meteor_controller'); ?>" /></td>
        </tr>
         
        <tr valign="top">
            <th scope="row"><label for="liveblogging_meteor_controller_port"><?php _e('Meteor Controller Port (can be left blank if not using Meteor)', 'live-blogging'); ?></label></th>
            <td><input type="text" name="liveblogging_meteor_controller_port" value="<?php echo get_option('liveblogging_meteor_controller_port'); ?>" /></td>
        </tr>
         
        <tr valign="top">
            <th scope="row"><label for="liveblogging_id"><?php _e('Meteor Identifier (a unique string to identify this WordPress install - can be left blank if not using Meteor)', 'live-blogging'); ?></label></th>
            <td><input type="text" name="liveblogging_id" value="<?php echo get_option('liveblogging_id'); ?>" /></td>
        </tr>
        
    </table>
    
    <p class="submit">
        <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
<?php
}

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

  <label for="live_blogging_post_enable"><?php _e('Enable live blogging on this post', 'live-blogging' ); ?></label>
  <input type="checkbox" name="live_blogging_post_enable" value="enabled"<?php echo $checked; ?> />
<?php
}

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

add_action('save_post', 'live_blogging_save_post_meta');
add_action('save_post', 'live_blogging_save_entry_meta');

function live_blogging_fix_title($title, $id)
{
    $post = get_post($id);
    if ('liveblog_entry' == $post->post_type)
    {
        $title = apply_filters('get_the_excerpt', $post->post_excerpt);
    }
    return $title;
}

add_filter('the_title', 'live_blogging_fix_title', 10, 2);

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

add_action('manage_posts_custom_column', 'live_blogging_custom_column', 10, 2);

function live_blogging_add_custom_column($columns)
{
    $columns['liveblog'] = __('Live Blog', 'live-blogging');
    return $columns;
}

add_filter('manage_liveblog_entry_posts_columns', 'live_blogging_add_custom_column');