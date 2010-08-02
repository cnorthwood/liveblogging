=== Live Blogging ===
Contributors: chrisnorthwood
Tags: live, micro, JavaScript, blogging, event
Requires at least: 2.7
Tested up to: 2.9
Stable tag: 1.3

Live Blogging is a plugin to support micro blogging of live events using Comet/Push HTTP technology.

== Description ==

To see this plugin in use, it's probably easiest to watch this screencast: http://www.pling.org.uk/static/livebloggingscreencast.swf

Live Blogging is a plugin developed to support blogs that are doing live micro blogging of running events, such as major sport events or tech conferences. (an example of where the plugin was used, and what it was originally developed for: http://www.nouse.co.uk/2009/05/10/roses-2009-live-sunday/)

It provides a simple backend interface, and has multiple hooks that can be triggered when a new live blog post is made - at the moment it has a hook to push to Twitter (example, see http://twitter.com/nouselive) and also to Meteor (http://meteorserver.org).

The plugin is most effective when used alongside Meteor. When Meteor is used, the events are automatically pushed (using Comet technology) to all of your currently connected readers where they slide in at the top of the post - this is the same technology as Twitterfall, whose creators advised in the creation of this plugin. Comments on those posts also automatically appear at the end.

Using the plugin to blog these kind of events has two obvious advantages - reducing load on your server of constant pageloads from people refreshing your blog, and also to get your entries out quicker than anyone else. Also the sliding transition is cool!

== Installation ==

First of all, you must have Meteor set up and running - http://www.meteorserver.org/. You can use the plugin without Meteor, but it's not as effective as with.

Then simply install your plugin as per usual, and in the new Live Blogging part of the sidebar, go in to Configure Live Blogging and set up your Twitter and Meteor server details. If you don't want to use Twitter or Meteor, leave the fields blank.

Now, create a live blog using the Manage Live Blogs panel and make a note of the ID. Now, create a new post, and enter `[liveblog]ID[/liveblog]`, obviously replacing `ID` with the ID you just noted. The live blog will appear in that post. Now, simply use the New Entry and Edit Entry screens in the Live Blogging section to micro blog.

Once you've finished blogging that event, go back to Manage Live Blogs to Deactivate that blog - that prevents the JavaScript for Meteor getting inserted unnecessarily.

And that's it.

== Changelog ==

= 1.3 =

Fix timezone bugs in certain PHP/WordPress combinations

= 1.2 =

Correctly require jQuery