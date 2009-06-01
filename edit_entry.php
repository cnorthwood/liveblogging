<?php

// Limit new entries to users who can edit
if (!current_user_can('edit_others_posts'))
{
    die('Access Denied');
}

?>
<div class="wrap">
<?php
    if (isset($_POST['eid-to-edit']))
    {
        // Show the edit form once we know which post we're supposed to be editing.
?>
    <h2>Edit Entry</h2>
    <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="post">
        <input type="hidden" id="eid-to-save" name="eid-to-save" value="<?php echo $_POST['eid-to-edit']; ?>" />
        <?php the_editor(liveblog_entry($_POST['eid-to-edit'])); ?>
        <p>Change the time this entry was posted at:</p>
<?php
        $dt = liveblog_time($_POST['eid-to-edit']);
        liveblog_dt_selects(mysql2date('H', $dt), mysql2date('i', $dt));
?>
        <input type="hidden" name="dt" value="<?php echo mysql2date('U', $dt); ?>" />
        <input type="submit" name="save" id="save-post" value="Post" tabindex="4" class="button button-highlighted" />
    </form>
<?php
    }
    else
    {
?>
    <h2>Edit Entry</h2>
<?php
        if (isset($_POST['eid-to-save']))
        {
            //If we're getting an updated post, handle it here
            liveblog_edit_entry($_POST['eid-to-save'], stripslashes($_POST['content']), $_POST['hour'], $_POST['minute'], $_POST['dt']);
?>
        <div id="message" class="updated fade below-h2"><p>Post edited.</p></div>
<?php
        }
        // Show the list of entries to be selected from.
?>
    <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="post">
        <p><label for="eid-to-edit">Select entry to edit:</label> <select id="eid-to-edit" name="eid-to-edit"><?php liveblog_list_entries(); ?></select></p>
        <input type="submit" name="save" id="save-post" value="Edit" tabindex="4" class="button button-highlighted" />
    </form>
<?php
    }
?>
</div>