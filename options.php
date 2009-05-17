<?php

// Limit option settings
if (!current_user_can('manage_categories'))
{
    die('Access Denied');
}

$updated = false;

// Handle post responses
if (isset($_POST['twitter_username']))
{
    update_option('twitter_username', $_POST['twitter_username']);
    $updated = true;
}

if (isset($_POST['twitter_password']))
{
    update_option('twitter_password', $_POST['twitter_password']);
    $updated = true;
}

if (isset($_POST['liveblogging_meteor_host']))
{
    update_option('liveblogging_meteor_host', $_POST['liveblogging_meteor_host']);
    $updated = true;
}

if (isset($_POST['liveblogging_meteor_controller']))
{
    update_option('liveblogging_meteor_controller', $_POST['liveblogging_meteor_controller']);
    $updated = true;
}

if (isset($_POST['liveblogging_meteor_controller_port']))
{
    update_option('liveblogging_meteor_controller_port', $_POST['liveblogging_meteor_controller_port']);
    $updated = true;
}

if (isset($_POST['liveblogging_id']))
{
    update_option('liveblogging_id', str_replace(' ', '_', $_POST['liveblogging_id']));
    $updated = true;
}

?>
<div class="wrap">
<h2>Configure Live Blogging</h2>
<?php
    if ($updated)
    {
        echo '<div id="message" class="updated fade below-h2"><p>Options updated.</p></div>';
    }
?>

<form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="post">
<table class="form-table">

<tr valign="top">
<th scope="row">Twitter Username</th>
<td><input type="text" name="twitter_username" value="<?php echo get_option('twitter_username'); ?>" /></td>
</tr>

<tr valign="top">
<th scope="row">Twitter Password</th>
<td><input type="password" name="twitter_password" value="<?php echo get_option('twitter_password'); ?>" /></td>
</tr>

<tr valign="top">
<th scope="row">Meteor Subscriber Host</th>
<td><input type="text" name="liveblogging_meteor_host" value="<?php echo get_option('liveblogging_meteor_host'); ?>" /></td>
</tr>

<tr valign="top">
<th scope="row">Meteor Controller Host</th>
<td><input type="text" name="liveblogging_meteor_controller" value="<?php echo get_option('liveblogging_meteor_controller'); ?>" /></td>
</tr>

<tr valign="top">
<th scope="row">Meteor Controller Port</th>
<td><input type="text" name="liveblogging_meteor_controller_port" value="<?php echo get_option('liveblogging_meteor_controller_port'); ?>" /></td>
</tr>

<tr valign="top">
<th scope="row">Unique ID to use for Meteor</th>
<td><input type="text" name="liveblogging_id" value="<?php echo get_option('liveblogging_id'); ?>" /></td>
</tr>

</table>


<p class="submit">
<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>

</form>
