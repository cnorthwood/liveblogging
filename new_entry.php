<?php

// Limit new entries to users who can publish
if (!current_user_can('publish_posts'))
{
    die('Access Denied');
}

if (isset($_POST['lbid']))
{
    liveblog_add_entry($_POST['lbid'], stripslashes($_POST['content']), $_POST['hour'], $_POST['minute']);
    $message = 'Entry posted.';
}

?>
<div class="wrap">
    <h2>Post New Entry</h2>
<?php
    if (isset($message))
    {
        echo '<div id="message" class="updated fade below-h2"><p>' . $message . '</p></div>';
    }
    if (liveblog_num_active() > 0)
    {
        // If some liveblogs are active, show the post form (liveblog selector, editor and time override)
?>
    <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="post">
        <p><label for="lbid">Post in blog:</label> <select id="lbid" name="lbid"><?php liveblog_list_active(); ?></select></p>
        <?php the_editor(''); ?>
        <p>Change the time this entry was posted at (leave blank for "now"):</p>
        <?php liveblog_dt_selects(); ?>
        <input type="submit" name="save" id="save-post" value="Post" tabindex="4" class="button button-highlighted" />
    </form>
    <?php } else { ?>
        <p><em>There are no live blogs currently active.</em></p>
    <?php } ?>
</div>