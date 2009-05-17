<?php

// Limit new entries to users who can edit
if (!current_user_can('manage_categories'))
{
    die('Access Denied');
}

// Handle form submissions and set the return message
if (isset($_POST['act']))
{
    switch ($_POST['act'])
    {
        case 'create':
            liveblog_create($_POST['desc']);
            $message = 'New live blog created.';
            break;
        case 'switch':
            if ($_POST['dact'] == 'Deactivate')
            {
                liveblog_deactivate($_POST['lbid']);
                $message = 'Live blog deactivated.';
            }
            else
            {
                liveblog_activate($_POST['lbid']);
                $message = 'Live blog reactivated.';
            }
            break;
    }
}
?>
<div class="wrap">
    <h2>Manage Live Blogs</h2>
<?php
    if (isset($message))
    {
        echo '<div id="message" class="updated fade below-h2"><p>' . $message . '</p></div>';
    }
    // Show form to blog creation
?>
    <h3>Create New Live Blog</h3>
    <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="post">
        <input type="hidden" name="act" value="create" />
        <p><label for="desc">Blog description</label> <input type="text" name="desc" id="desc" /> <input type="submit" name="save" value="Create New" tabindex="4" class="button button-highlighted" /></p>
    </form>
    <h3>Live Blog Status</h3>
<?php   // List live blogs and activation/deactivation options
        if (liveblog_num_all())
        {
            liveblog_list_all();
        }
        else
        {
?>
        <p><em>No live blogs exist.</em></p>
<?php
        }
?>
</div>