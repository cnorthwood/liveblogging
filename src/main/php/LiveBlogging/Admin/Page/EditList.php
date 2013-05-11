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

/**
 * Override the default WordPress custom post edit list
 */
class LiveBlogging_Admin_Page_EditList {

	public function __construct() {
		add_filter( 'manage_liveblog_entry_posts_columns', array( $this, 'add_custom_column' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'render_custom_column' ), 10, 2 );
	}

	public function register_page() {
	}

	public function add_custom_column( $columns ) {
		$columns['liveblog'] = __( 'Live Blog', 'live-blogging' );
		return $columns;
	}

	public function render_custom_column( $column_name, $post_ID ) {
		switch ( $column_name ) {
		case 'liveblog':
			$blog = LiveBlogging_LiveBlogEntry::live_blogs_for_entry_id( $post_ID );
			if ( ! empty( $blog ) ) : ?>
				<a href="post.php?post=<?php echo esc_attr( $blog->post_id() ); ?>&amp;action=edit">
					<?php echo esc_html( $blog->name() ); ?>
				</a><?php
			endif;
			break;
		}
	}

}