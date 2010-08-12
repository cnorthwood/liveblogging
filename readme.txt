=== Live Blogging ===
Contributors: chrisnorthwood
Tags: live, micro, JavaScript, blogging, event, automatic updating, auto updating
Requires at least: 3.0
Tested up to: 3.0.1
Stable tag: 2.1.1

Live Blogging is a plugin that allows you to insert micro/live blogs into posts
with automatic updating of the content.

== Description ==

To see this plugin in use, it's probably easiest to watch [this screencast](http://www.pling.org.uk/static/Live_Blogging_for_WordPress_2.0.swf).

Live Blogging is a plugin developed to support blogs that are doing live micro
blogging of running events, such as major sport events or tech conferences.
(such as the [Roses sporting tournament](http://www.nouse.co.uk/2009/05/10/roses-2009-live-sunday/)
at the University of York).

Using WordPress 3.0's custom post types, the plugin allows you to create these
micro blog entries in a stripped down version of the normal post edit screen.
These micro blog entries then get included in a post which has been activated
as the microblog.

By default, the plugin uses AJAX polling to allow your readers to instantly
update what they are viewing, including any new comments posted on the post. For
professional bloggers, Meteor is supported, which gives the smoothest updating
experience for your viewers and also significantly reduces the load on your
server.

Additionally, the plugin also supports integration with Twitter - if activated,
the first 139 characters of each live blog entry will be posted to Twitter when
they are made.

Using this plugin will give you an advantage over your competitors if you're
blogging from live events - your readers will get updates quicker, and it will
integrate into your blog better than competing systems, such as CoverItLive.

== Installation ==

Live Blogging v2, like version 1, requires PHP version 5.2 and WordPress 3.0 or
later. Please make sure you have these before you attempt to use the plugin.
If you get errors whilst activating, it is likely that you are using a version
of PHP before version 5.2.

Live Blogging is installed and activated as per normal plugins. Once it has been
activated, please read the FAQ about how to use it.

Once you have installed Live Blogging, then it is most sensible to proceed to
the Live Blogging settings, which is included in your 'Settings' panel in the
admin area as 'Live Blogging'. From here, you can enable functionality such as
posting to Twitter, changing the method used for live blog updating and
customising the style of the live blog entries.

If you have installed a Meteor server and intend to use it to stream updates,
you must also enter your Meteor server details and set the live blog update
method to 'Stream using Meteor'.

== Frequently Asked Questions ==

= How do I start live blogging? =

The very first thing you need to do is select a post which to use as the shell
for your live blog. Either create a new post, or select an existing one to edit,
and then to the right of your post screen, there should be an option entitled:
``Enable live blogging on this post''. Without this option being enabled, you
will be unable to add new live blog entries to this post!

The final step is very important. You must insert the shortcode [liveblog]
somewhere within your post which indicates where the liveblog is to appear.

Now, your live blog is set up! You can select 'Add New' from the sidebar under
'Live Blog Entries', choose the live blog you want the update to apply to from
the list in the sidebar, and then create your entry as per usual.

Once you have finished with your live blog, untick the ``Enable live blogging on
this post'' box. This will stop automatic updating and any new updates to be
added to the live blog, but will preserve any existing live blogging
entries for posterity.

= How do I upgrade my live blogs from version 1.x of the plugin? =

If the plugin detects that old data from the plugin exists, then it will prompt
you to upgrade on the options screen. Also, a 'Live Blogging Migration' option
will exist under the Tools menu. Run that tool and follow the prompts to upgrade
all your old entries to the new version.

Please note that live blogs created in previous versions of the software can
not have new entries added to them.

= If I change the time on a live blog entry to appear in the past, then it doesn't appear in the right order during automatic updating? =

Unfortunately this is a limitation on the system - any new entries must appear
at the top of the live blog. Readers who come in after the blog was posted, or
users who subsequentally refresh will see the blog posts in the correct order.

= I'm using TwentyTen and comment updating doesn't look right =

Add:

`add_filter('live_blogging_build_comments', 'twentyten_liveblogging_comments');
function twentyten_liveblogging_comments($a)
{
	$a['callback'] = 'twentyten_comment';
	return $a;
}`

to your functions.php in your theme.

= Comment updating does not appear to work on my theme =

For comment updating to work, you must be using a reasonably standard comment
setup. That is, your theme must generate comments using `wp_list_comments` and
put them in an element with an ID of `commentlist`. If your theme does not do
these things, it is recommended that you disable comment updating in the option
panel.

If your theme does support this, but comment updating still does not work
correctly, it is possible that your theme is calling `wp_list_comments` with
non-default arguments. It is possible to account for this using a filter.

Using the `live_blogging_build_comments` filter, you can return the arguments
which your theme uses to call to `wp_list_comments` to get the same effect as
your default theme. For an example of how to do this, see above.

= I imported my live blog from version 1 of the software, and am continuing to use the same live blog, but comment auto-updating isn't working? =

Sorry, this is a design issue with the software. It is recommended that you do
not re-use any imported live blogs in this way.

= What is Meteor, and how do I use it? =

Meteor is a streaming web server that allows you to instantly "push" updates out
to your readers. This is the technology Twitterfall uses, and the advice of the
Twitterfall creators was useful in developing this functionality. Using Meteor
allows you to lower your server load, as well as quicker updates for your
readers.

There is a catch, however. Meteor is a web server separate to what servers your
normal website and requires special configuration to set up, and your own server
or VPS to run it on. Shared hosting is, typically, unsuitable.

If you are a professional blogger, running your blog from a server where you
can install things as root, and are wishing to reduce server load, using the
plugin with Meteor is highly recommended.

For more about Meteor, please see the [Meteor website](http://meteorserver.org/).

= Does deleting a post from inside WordPress also delete it from Twitter? =

Yes, it does!

= Editing a post does not change the tweet the software generated =

It is impossible to edit tweets. A decision was made not to implement this
functionality, as it may result in out-of-order or duplicate tweets in a user's
timeline. If you really need to delete a tweet, it is recommended that you
either delete the tweet manually, or delete the post and create a new one.

= Can I get an RSS feed containing just my live blog entries? =

Yes - if permalinks are enabled. Navigate to: WP-URL/feed/?post_type=liveblog_entry&liveblog=ID
where WP-URL is the address of your WordPress site, and replace ID (right
at the end) with the ID of the post containing that live blog.

= I'm getting multiple bookmarking icons or similar on every live blog post =

Some bookmarking plugins work by adding their icons at the end of every post.
Obviously, when you're microblogging and adding multiple entries per page, this
can get very tiresome. The options screen allows you to workaround this.

In 'Advanced Settings' at the bottom is a list of actions which are unhooked
from the `the_content` display filter, so they do not show up. To hide a
bookmarking plugin, you must add the name of the function which is called by the
`the_content` filter to this list. The green + allows you to add more text
boxes, and then red - allows you to remove a function from being unhooked.


== Changelog ==

= 2.1.1 =

* Don't generate a warning when `the_title` filter gets called with only one
  argument - simply disable processing of the title in this case (this should
  only affect plugins doing things in an old-fashioned way).

= 2.1 =

* Allow for new live blog updates to appear in ascending or descending order.
* Import @replies to live blog tweets as comments to the live blog.
* Allow the plugin to be loaded if the PHP CURL extension isn't (although
  Twitter will be disabled).
* After publishing a new entry, return to the new entry screen, rather than the
  edit screen.

= 2.0 =

* Almost complete rewrite
* Allow updating via AJAX polling
* Supports Twitter OAuth
* Finally kills the timezone bug
* New admin area user experience
* Customisable style
* Customisable timestamp format
* i18n support
* Workarounds for unruly plugins in the admin area
* Supports deleting live blog entries

= 1.3 =

* Fix timezone bugs in certain PHP/WordPress combinations

= 1.2 =

* Correctly require jQuery

== Upgrade Notice ==

= 2.0 =

2.0 is a MAJOR upgrade to the 1.x series. Please sure you read the section about
upgrading in the readme fully to ensure your old live blogs are migrated to Live
Blogging v2.