<?php
/* Generate an RSS feed for a liveblog */

global $wpdb, $table_prefix, $wp_query;

$lbid = get_query_var('liveblog');
$sql = $wpdb->prepare("SELECT `entryid`,`post`,`dt` FROM ${table_prefix}liveblog_entries WHERE `lbid`=%d ORDER BY entryid DESC", $lbid);
$query = $wpdb->get_results($sql);

if (count($query))
{
    // If we have some entries in this liveblog, show them
    header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
    echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'
?>
<rss version="2.0"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:atom="http://www.w3.org/2005/Atom"
    <?php do_action('rss2_ns'); ?>
>
    <channel>
        <title><?php bloginfo_rss('name'); ?> - <?php echo liveblog_get_lbname($lbid); ?></title>
        <atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
        <link><?php bloginfo_rss('url') ?></link>
        <description><?php bloginfo_rss("description") ?></description>
        <pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></pubDate>
        <?php the_generator( 'rss2' ); ?>
        <language><?php echo get_option('rss_language'); ?></language>
<?php   do_action('rss2_head');
        foreach ($query as $entry)
        {
?>
        <item>
            <pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', $entry->dt, false); ?></pubDate>
            <guid isPermaLink="false"><?php bloginfo_rss('url'); echo '-' . $entry->entryid; ?></guid>
            <description><![CDATA[<?php echo nl2br(wptexturize($entry->post)); ?>]]></description>
            <content:encoded><![CDATA[<?php echo nl2br(wptexturize($entry->post)); ?>]]></content:encoded>
        </item>
<?php   } ?>
    </channel>
</rss>
<?php
}
else
{
    // If no results, 404
   $wp_query->set_404();
   status_header(404);
}

?>