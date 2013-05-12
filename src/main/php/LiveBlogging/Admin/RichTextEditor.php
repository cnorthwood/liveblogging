<?php
/*
	Live Blogging for WordPress
	Copyright (C) 2010-2013 Chris Northwood <chris@pling.org.uk>

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

class LiveBlogging_Admin_RichTextEditor {

	public function __construct() {
		add_action( 'media_buttons_context', array( $this, 'insert_shortcode_button' ) );
		add_action( 'admin_head', array( $this, 'insert_shortcode_js' ) );
	}

	public function insert_shortcode_button( $context ) {
		$additional = '<a class="button" style="cursor:pointer;" onclick="live_blog_insert_shortcode();" id="insert-live-blog-shortcode">Live Blog</a>';
		return $context . $additional;
	}

	public function insert_shortcode_js() {
		?>
		<script type="text/javascript">
			function live_blog_insert_shortcode() {
				var win = window.dialogArguments || opener || parent || top;
				win.send_to_editor("[liveblog]");
			}
		</script>
	<?php
	}

}