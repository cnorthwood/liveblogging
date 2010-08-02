<?php
/*
Plugin Name: Live Blogging
Plugin URI: http://wordpress.org/extend/plugins/live-blogging/
Description: Plugin to support automatic live blogging
Version: 1.3
Author: Chris Northwood
Author URI: http://www.pling.org.uk/

Icon CC-BY-NC-SA: http://www.flickr.com/photos/64892304@N00/2073251155

The plugin has a number of "hooks" which are called when a new liveblogging
entry is made or edited. Two such hooks are for Twitter and Meteor. The plugin
simply sends a message to Meteor which then relays it to all connected clients.

From the clients view, basically, there are three scenarios:

   1. An edit to an existing update is received. I don't mind some joltyness
      here, this should be few and far between.
   2. A new update is received, this should "slide in" from the top
   3. The status changes - we have a little div at the top that starts off
      empty, and if live blogging works, gets set with some informational text
      about how they don't need to refresh. If for some reason liveblogging
      fails, it gets set to some different text. 

Messages from Meteor are handled by the liveblog_handle_event callback, state
changes by liveblog_handle_statechange.

The data received is a JSON string, which is parsed and consists of 4 members:

lbid (the ID of the liveblog this message belongs to - this is largely irrelevant),
entryid (the ID of the entry, used for determining if it's an edit, or a new entry),
entry (the body of the update itself)
dt (a string containing the time of this particular update). 

Hooks receive this as an array as its first argument. The only two hooks at the
moment are Twitter and Meteor. Meteor is kind of the point.

*/

/* Register administration menus */
function liveblog_display_menus()
{
    add_menu_page('Live Blogging', 'Live Blogging', 'publish_posts', 'live-blogging/new_entry.php', '', plugins_url('live-blogging/icon.png'));
    add_submenu_page('live-blogging/new_entry.php', 'New Entry', 'New Entry', 'publish_posts', 'live-blogging/new_entry.php');
    add_submenu_page('live-blogging/new_entry.php', 'Edit Entry', 'Edit Entry', 'edit_others_posts', 'live-blogging/edit_entry.php');
    add_submenu_page('live-blogging/new_entry.php', 'Manage Live Blogs', 'Manage Live Blogs', 'manage_categories', 'live-blogging/manage.php');
    add_submenu_page('live-blogging/new_entry.php', 'Configure Live Blogging', 'Configure Live Blogging', 'manage_categories', 'live-blogging/options.php');
    add_submenu_page('live-blogging/new_entry.php', 'Live Blogging Status', 'Live Blogging Status', 'edit_others_posts', 'live-blogging/meteorstatus.php');
}

add_action('admin_menu', 'liveblog_display_menus');

/* Add appropriate JS to edit pages */
function liveblog_admin_header()
{
    wp_enqueue_script('autosave');
    wp_enqueue_script('post');
    if ( user_can_richedit() )
        wp_enqueue_script('editor');
    add_thickbox();
    wp_enqueue_script('media-upload');
    wp_enqueue_script('word-count');
    wp_print_scripts();
    wp_print_styles();
}

add_action('admin_head-live-blogging/new_entry.php', 'liveblog_admin_header');
add_action('admin_head-live-blogging/edit_entry.php', 'liveblog_admin_header');

/* Create database on plugin load */
function liveblog_init()
{
    global $wpdb, $table_prefix;
    
    $sql = "CREATE TABLE IF NOT EXISTS `{$table_prefix}liveblogs` (`lbid` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `desc` VARCHAR( 255 ) NOT NULL, `active` BOOL NOT NULL)";
    $wpdb->query($sql);
    $sql = "CREATE TABLE IF NOT EXISTS `{$table_prefix}liveblog_entries` (`entryid` INT NOT NULL AUTO_INCREMENT PRIMARY KEY , `lbid` INT NOT NULL , `post` TEXT NOT NULL , `dt` DATETIME NOT NULL , `author` INT NOT NULL)";
    $wpdb->query($sql);
    add_option('liveblog_assigned_ids', 1);
}

register_activation_hook(__FILE__, 'liveblog_init');

/* Utility functions */
/* @return int The number of active live blogs */
function liveblog_num_active()
{
    global $wpdb, $table_prefix;
    return $wpdb->get_var("SELECT COUNT(*) FROM `{$table_prefix}liveblogs` WHERE `active`=TRUE");
}

/* Echoes a list of <option> elements for active liveblogs */
function liveblog_list_active()
{
    global $wpdb, $table_prefix;
    $sql = "SELECT `lbid`,`desc` FROM `{$table_prefix}liveblogs` WHERE `active`=TRUE ORDER BY `lbid` DESC";
    $liveblogs = $wpdb->get_results($sql);
    foreach ($liveblogs as $liveblog)
    {
        echo '<option value="' . $liveblog->lbid . '">' . htmlspecialchars($liveblog->desc) . '</option>';
    }
}

/* @param int $lbid The ID of the liveblog we're checking
 * @return bool Whether or not the specified liveblog is empty */
function liveblog_is_active($lbid)
{
    global $wpdb, $table_prefix;
    $query = $wpdb->prepare("SELECT `active` FROM `{$table_prefix}liveblogs` WHERE `lbid`=%d", $lbid);
    return $wpdb->get_var($query);
}

/* @return int The number of liveblogs in total */
function liveblog_num_all()
{
    global $wpdb, $table_prefix;
    return $wpdb->get_var("SELECT COUNT(*) FROM `{$table_prefix}liveblogs`");
}

/* Echoes a list of all liveblogs in a nice form with activate/deactivate options */
function liveblog_list_all()
{
    global $wpdb, $table_prefix;
    
    $sql = "SELECT `lbid`,`desc`,`active` FROM `{$table_prefix}liveblogs` ORDER BY `lbid` DESC";
    $liveblogs = $wpdb->get_results($sql);
    foreach ($liveblogs as $liveblog)
    {
?>
<form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="post">
    <input type="hidden" name="act" value="switch" />
    <input type="hidden" name="lbid" value="<?php echo $liveblog->lbid; ?>" />
    <li>
        <?php echo htmlspecialchars($liveblog->desc); ?> (ID: <?php echo $liveblog->lbid; ?>)
        <input type="submit" name="dact" value="<?php echo $liveblog->active ? 'Deactivate' : 'Reactivate'; ?>" tabindex="4" class="button button-highlighted" />
    </li>
</form>
<?php
    }
}

/* Echoes a list of <options> for hours/minutes to be used for time overrides
 * @param int $hour The hour to be selected
 * @param int $min The minute to be selected
 */
function liveblog_dt_selects($hour = -1, $min = -1)
{
?>
<select name="hour">
    <option value="blank"></option>
<?php
    for ($i = 0; $i < 24; $i++)
    {
        echo '<option';
        if ($i == $hour)
        {
            echo ' selected="selected"';
        }
        echo ">$i</option>\n";
    }
?>
</select>
<select name="minute">
    <option value="blank"></option>
<?php
    for ($i = 0; $i < 60; $i++)
    {
        echo '<option';
        if ($i == $min)
        {
            echo ' selected="selected"';
        }
        echo ">$i</option>\n";
    }
?>
</select>
<?php
}

/* @param int $eid The ID of the entry
 * @return string A MySQL DATETIME string representing the time of that entry */
function liveblog_time($eid)
{
    global $wpdb, $table_prefix;
    
    $sql = $wpdb->prepare("SELECT `dt` FROM `{$table_prefix}liveblog_entries` WHERE `entryid`=%d", $eid);
    return $wpdb->get_var($sql); 
}

/* @param int $lbid The ID of the liveblog
 * @return string The description/name of the liveblog */
function liveblog_get_lbname($lbid)
{
    global $wpdb, $table_prefix;
    
    $sql = $wpdb->prepare("SELECT `desc` FROM `{$table_prefix}liveblogs` WHERE `lbid`=%d", $lbid);
    return $wpdb->get_var($sql);
}

/* Echoes a list of <option> elements representing all the entries, with a short excerpt at the beginning */
function liveblog_list_entries()
{
    global $wpdb, $table_prefix;
    $sql = "SELECT `entryid`,SUBSTRING(`post`,1,80) AS `postx` FROM `{$table_prefix}liveblog_entries` ORDER BY `entryid` DESC";
    $entries = $wpdb->get_results($sql);
    foreach ($entries as $entry)
    {
        echo '<option value="' . $entry->entryid . '">' . htmlspecialchars($entry->postx) . '&hellip;</option>';
    }
}

/* @param string $description Create a new liveblog with the name specified */
function liveblog_create($description)
{
    global $wpdb, $table_prefix;
    $query = $wpdb->prepare("INSERT INTO `{$table_prefix}liveblogs` ( `desc` , `active` ) VALUES( %s , TRUE )", $description);
    $wpdb->query($query);
}

/* @param int $lbid Mark a liveblog as active */
function liveblog_activate($lbid)
{
    global $wpdb, $table_prefix;
    $query = $wpdb->prepare("UPDATE `{$table_prefix}liveblogs` SET `active`=TRUE WHERE `lbid`=%d", $lbid);
    $wpdb->query($query);
}

/* @param int $lbid Mark a liveblog as inactive */
function liveblog_deactivate($lbid)
{
    global $wpdb, $table_prefix;
    $query = $wpdb->prepare("UPDATE `{$table_prefix}liveblogs` SET `active`=FALSE WHERE `lbid`=%d", $lbid);
    $wpdb->query($query);
}

/* Adjust hour back to GMT from Wordpress timezone setting
 * @param string $hour The hour to override the time to, or "blank" to use now
 * @return The value of $hour, adjusted to GMT
 */
function liveblog_fixhour($hour)
{
    $gmt_offset = get_option('gmt_offset');
    $gmt_offset_hours = $gmt_offset < 0 ? ceil($gmt_offset) : floor($gmt_offset);
    $fullhour = intval($gmt_offset) == floatval($gmt_offset);

    if ($hour === "blank")
    {
        return $hour;
    }

    $hour = ((int) $hour) - $gmt_offset_hours;

    if ($hour < 0)
    {
        $hour += 24;
    }
    elseif ($hour >= 24)
    {
        $hour -= 24;
    }
    return (string) $hour;
}

/* Adjust min back to GMT from Wordpress timezone setting
 * @param string $min The min to override the time to, or "blank" to use now
 * @return The value of $min, adjusted to GMT
 */
function liveblog_fixmin($min)
{
    $gmt_offset = get_option('gmt_offset');
    $fullhour = intval($gmt_offset) == floatval($gmt_offset);

    if (($min === "blank") || $fullhour)
    {
        return $min;
    }

    $min = ((int) $min) - 30;

    if ($min < 0)
    {
        $min += 60;
    }
    elseif ($min >= 60)
    {
        $min -= 60;
    }
    return (string) $min;
}

/* Deal with edge conditions, where blog posts are set to appear a few minutes before midnight
 * @param mixed $hour The hour, set to "blank" if no override
 * @param mixed $min The minute, set to "blank" if no override
 * @param unixtime $realdate The real timestamp which we should figure out our "overridden" hour/minutes relative to
 * @return unixtime The timestamp of the overriden date/time (or the real date/time if not overriden)
 */
function liveblog_figure_time($hour, $min, $realdate)
{
    if ($hour == "blank" || $min == "blank")
    {
        // No override specified
        return $realdate;
    }
    elseif ($hour == 23 && gmdate('H', $realdate) == 0)
    {
        // If after midnight, but want it to appear before midnight, take an hour off the real date to set the correct date.
        $realdate -= 3600;
    }
    elseif ($hour == 0 && gmdate('H', $realdate) == 23)
    {
        // Inverse of above. Not sure why anyone would ever want to do this, but there we go
        $realdate += 3600;
    }
    return mktime($hour, $min, 0,
                  gmdate('n', $realdate),
                  gmdate('j', $realdate),
                  gmdate('Y',$realdate));
}

/* Same behaviour as Wordpress' date_i18n(),
 * but using Wordpress' timezone settings, not PHP's
 * @param string $format The format string, as used by PHP's date()
 * @param unixtime $timestamp The timestamp 
 * @return string String representation of the timestamp, formatted as format
 */
function liveblog_date($format, $timestamp = false)
{
    $dt = ($timestamp === false) ? time() : $timestamp;
    return date_i18n($format, $dt + (get_option('gmt_offset') * 3600), true);
}

/* Similar to mysql2date, but prints as a GMT date string
 * instead of printing under PHP's current timezone.
 * @param string $dateformatstring The format string, as used by PHP's date()
 * @param string $mysqlstring The datetime from MySQL
 * @param bool $translate Whether or not to use date_i18n instead of gmdate
 * @return string String representation of the mysqlstring, reformatted as format
 */
function liveblog_mysql2date($dateformatstring, $mysqlstring, $translate = true)
{
    $i = strtotime($mysqlstring);
    if ($translate)
    {
        return date_i18n($dateformatstring, $i, true);
    }
    else
    {
        return gmdate($dateformatstring, $i);
    }
}

/* Add a new liveblog entry
 * @param int $lbid The ID of the liveblog this entry is to be posted into
 * @param string $entry The body of the entry
 * @param int $hour The hour to override the time to, or "blank" to use now
 * @param int $min The minute to override the time to, or "blank" to use now
 * Also, triggers the "liveblog_add_entry" action which other things can hook into
 * Better description of hooks are at the top of the file
 */
function liveblog_add_entry($lbid, $entry, $hour, $min)
{
    global $wpdb, $table_prefix, $current_user;
    $hour = liveblog_fixhour($hour);
    $min = liveblog_fixmin($min);
    $dt = liveblog_figure_time($hour, $min, time());
    $query = $wpdb->prepare("INSERT INTO `{$table_prefix}liveblog_entries` (`lbid`,`post`,`dt`,`author`) VALUES( %d, %s, FROM_UNIXTIME(%d), %d) ", $lbid, $entry, $dt, $current_user->ID);
    $wpdb->query($query);
    // We don't want sociable appended after every thing
    if (function_exists('sociable_display_hook'))
    {
        remove_filter('the_content', 'sociable_display_hook');
    }
    do_action('liveblog_add_entry', array('lbid' => $lbid, 'entryid' => $wpdb->insert_id, 'dt' => liveblog_date('H.i', $dt), 'entry' => apply_filters('the_content',$entry)));
    if (function_exists('sociable_display_hook'))
    {
        add_filter('the_content', 'sociable_display_hook');
    }
}

/* Edits an existing liveblog entry
 * @param int $eid The ID of the entry to be updated
 * @param string $new The new body of the entry
 * @param int $hour The hour to override the time to, or "blank" to use the original dt
 * @param int $min The minute to override the time to, or "blank" to use the original dt
 * Also, triggers the "liveblog_edit_entry" action which other things can hook into
 * Better description of hooks are at the top of the file
 */
function liveblog_edit_entry($eid, $new, $hour, $min, $origdt)
{
    global $wpdb, $table_prefix;
    $dt = liveblog_figure_time($hour, $min, $origdt);
    $query = $wpdb->prepare("UPDATE `{$table_prefix}liveblog_entries` SET `post`=%s, `dt`=%s WHERE `entryid`=%d", $new, gmdate('Y-m-d H:i:s', $dt), $eid);
    $wpdb->query($query);
    $query = $wpdb->prepare("SELECT lbid FROM `{$table_prefix}liveblog_entries` WHERE `entryid`=%d", $eid);
    if (function_exists('sociable_display_hook'))
    {
        remove_filter('the_content', 'sociable_display_hook');
    }
    do_action('liveblog_edit_entry', array('lbid' => $wpdb->get_var($query), 'entryid' => $eid, 'dt' => date_i18n('H.i', $dt, true), 'entry' => apply_filters('the_content',$new)));
    if (function_exists('sociable_display_hook'))
    {
        add_filter('the_content', 'sociable_display_hook');
    }
}

/* Add a [liveblog] short code */
function liveblog_shortcode($atts, $id)
{
    return liveblog_static($id);
}

add_shortcode('liveblog', 'liveblog_shortcode');

/* Set cookie for host ID for Meteor */
function liveblog_cookie()
{
    global $liveblog_client_id;
    if (!isset($_COOKIE['liveblog-client-id']))
    {
        $liveblog_client_id = get_option('liveblog_assigned_ids');
        update_option('liveblog_assigned_ids', ++$liveblog_client_id);
        setcookie('liveblog-client-id', $liveblog_client_id, 0, COOKIEPATH);
    }
    else
    {
        $liveblog_client_id = $_COOKIE['liveblog-client-id'];
    }
}

add_action('init', 'liveblog_cookie');

/* Generate static HTML for shortcode */
function liveblog_static($id)
{
    global $wpdb, $table_prefix, $liveblog_client_id, $post;
    // Load push code if liveblog is currently active
    if (liveblog_is_active($id) && strlen(get_option('liveblogging_meteor_controller')) > 0)
    {
        $output = '
<script type="text/javascript" src="http://' . get_option('liveblogging_meteor_host') . '/meteor.js"></script>
<script type="text/javascript" src="' . plugins_url() . '/live-blogging/liveblog.js"></script>
<script type="text/javascript">
<!--
    // Configure Meteor
    Meteor.hostid = "' . $liveblog_client_id . '";
    Meteor.host = "' . get_option('liveblogging_meteor_host') . '";
    Meteor.registerEventCallback("process", liveblog_handle_event);
    Meteor.registerEventCallback("statuschanged", liveblog_handle_statechange);
    Meteor.joinChannel("' . get_option('liveblogging_id') . '-liveblog-' . $id . '", 2);
    Meteor.joinChannel("' . get_option('liveblogging_id') . '-comments-' . $post->ID . '", 2);
    Meteor.mode = "stream";
    jQuery(document).ready(function() {
        Meteor.connect();
    });
-->
</script>';
    }
    $query = $wpdb->prepare("SELECT `entryid`,`post`,`dt` FROM `{$table_prefix}liveblog_entries` WHERE `lbid`=%d ORDER BY `dt` DESC", $id);
    $entries = $wpdb->get_results($query);
    if (function_exists('sociable_display_hook'))
    {
        remove_filter('the_content', 'sociable_display_hook');
    }
    $output .= '<div id="liveblog-status"></div><div id="liveblog">';
    foreach ($entries as $entry)
    {
        /* TODO: Improve style of this */
        $output .= '<div id="liveblog-entry-' . $entry->entryid . '"><p><strong>' . liveblog_mysql2date('H.i', $entry->dt) . '</strong></p>' . apply_filters('the_content',$entry->post) . '<div style="width:620px; height:1px; background-color:#6f6f6f; margin-bottom:3px;"></div></div>';
    }
    if (function_exists('sociable_display_hook'))
    {
        add_filter('the_content', 'sociable_display_hook');
    }
    $output .= '</div>';
    return $output;
}

/* Get a specific entry */
function liveblog_entry($eid)
{
    global $wpdb, $table_prefix;
    $query = $wpdb->prepare("SELECT `post` FROM `{$table_prefix}liveblog_entries` WHERE `entryid`=%d", $eid);
    return $wpdb->get_var($query);
}

/* A hook for pushing live updates to Twitter */

require_once('twitter.php');

function liveblogging_send_to_twitter($lbe)
{
    // Only do this if the Twitter username is set
    if (strlen(get_option('twitter_username')) > 0)
    {
        try
        {
            $t = new Twitter(get_option('twitter_username'), get_option('twitter_password'));
            $m = html_entity_decode(strip_tags($lbe['entry']));
            if (strlen($m) > 140)
            {
                $t->updateStatus(substr($m, 0, 139) . '…');
            }
            else
            {
                $t->updateStatus($m);
            }
        } catch (TwitterException $e) {}
    }
}

add_action('liveblog_add_entry', 'liveblogging_send_to_twitter');

/* A hook for pushing live updates to Meteor */
function liveblogging_send_to_meteor($lbe)
{
    if (strlen(get_option('liveblogging_meteor_controller')) > 0)
    {
        $lbe['type'] = 'liveblog';
        $fd = fsockopen(get_option('liveblogging_meteor_controller'), get_option('liveblogging_meteor_controller_port'));
        fwrite($fd, 'ADDMESSAGE ' . get_option('liveblogging_id') . '-liveblog-' . $lbe['lbid'] . ' ' . addslashes(json_encode($lbe)) . "\n");
        fwrite($fd, "QUIT\n");
        fclose($fd);
    }
}

add_action('liveblog_add_entry', 'liveblogging_send_to_meteor');
add_action('liveblog_edit_entry', 'liveblogging_send_to_meteor');

/* Push new comments to Meteor */
function liveblogging_comments_to_meteor_check($comment_id)
{
    global $comment;
    $old_comment = $comment;
    $comment = get_comment($comment_id);
    if ($comment->comment_approved == 1)
    {
        liveblogging_comments_to_meteor($comment_id);
    }
    $comment = $old_comment;
}

function liveblogging_comments_to_meteor($comment_id)
{
    global $comment;
    $old_comment = $comment;
    $comment = get_comment($comment_id);
    if (strlen(get_option('liveblogging_meteor_controller')) > 0)
    {
        $c['type'] = 'comment';
        $c['newid'] = $comment_id;
        $c['author'] = get_comment_author_link();
        $c['url'] = get_comment_link();
        $c['date'] = get_comment_date() . ' at ' . get_comment_time();
        $c['text'] = '<p>' . get_comment_text() . '</p>';
        $fd = fsockopen(get_option('liveblogging_meteor_controller'), get_option('liveblogging_meteor_controller_port'));
        fwrite($fd, 'ADDMESSAGE ' . get_option('liveblogging_id') . '-comments-' . $comment->comment_post_ID . ' ' . addslashes(json_encode($c)) . "\n");
        fwrite($fd, "QUIT\n");
        fclose($fd);
    }
    $comment = $old_comment;
}

add_action('comment_approved_', 'liveblogging_comments_to_meteor');
add_action('comment_post', 'liveblogging_comments_to_meteor_check');

/* Generate an RSS feed for a live blog */

add_filter('query_vars', 'liveblogging_queryvars' );

function liveblogging_queryvars( $qvars )
{
  $qvars[] = 'liveblog';
  return $qvars;
}

function liveblogging_rss_feed()
{
    include('feed.php');
}

add_action('do_feed_liveblog', 'liveblogging_rss_feed');

?>
