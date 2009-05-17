<?php

// Limit new entries to users who can edit
if (!current_user_can('edit_others_posts'))
{
    die('Access Denied');
}
?>
<div class="wrap">
    <h2>Live Blogging Status</h2>
    <pre>
<?php
    if (strlen(get_option('liveblogging_meteor_controller')) > 0)
    {
        // If Meteor is configured, connect to it and get the output of SHOWSTATS
        $fp = fsockopen(get_option('liveblogging_meteor_controller'), get_option('liveblogging_meteor_controller_port'), $errno, $errstr, 30);
        if (!$fp)
        {
            echo "Unable to establish connection to Meteor: $errstr ($errno)\n";
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
        echo "Connection to Meteor not configured.";
    }

?>
    </pre>
</div>