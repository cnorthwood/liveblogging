<?php
/*
Plugin Name: Live Blogging
Plugin URI: http://cnorthwood.github.io/liveblogging/
Description: Plugin to support automatic live blogging
Version: 2.3
Author: Chris Northwood
Author URI: http://www.pling.org.uk/
Text-Domain: live-blogging

Live Blogging for WordPress
Copyright (C) 2010-2013 Chris Northwood <chris@pling.org.uk>
Contributors: Gabriel Koen, Corey Gilmore, Juliette

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

require( 'LiveBlogging.php' );
LiveBlogging::get_instance()->init();

// Redirect new posts to the new post page
add_filter( 'redirect_post_location', 'live_blogging_redirect', 10, 2 );