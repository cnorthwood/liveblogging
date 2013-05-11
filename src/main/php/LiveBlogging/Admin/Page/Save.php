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
 * Override the default WordPress post saving behaviour
 */
class LiveBlogging_Admin_Page_Save {

	public function __construct() {
		add_filter( 'post_updated_messages', array( $this, 'remove_post_saved_message' ) );
		add_filter( 'redirect_post_location', array( $this, 'redirect_to_new_entry_page' ), 10, 2 );
		add_action( 'delete_post', array( $this, 'mark_to_delete' ) );
		add_action( 'trash_post', array( $this, 'mark_to_delete' ) );
		add_action( 'publish_liveblog_entry', array( $this, 'mark_to_undelete' ), 10, 2 );
	}

	public function register_page() {
	}

	/**
	 * Overloading a filter to remove the "Post saved" message
	 */
	public function remove_post_saved_message( $messages ) {
		if ( LiveBlogging_LiveBlogEntry::is_being_edited() ) {
			if ( isset( $_GET['message'] ) && 6 == $_GET['message'] ) {
				unset( $_GET['message'] );
			}
		}
		return $messages;
	}

	public function redirect_to_new_entry_page( $location, $post_id ) {
		$url  = explode( '?', $location, 2 );
		$args = wp_parse_args( $url[1] );
		$url  = explode( '/', $url[0] );
		$url  = $url[count( $url ) - 1];

		if ( $url == 'post.php' && isset( $_POST['publish'] ) ) {
			$post = get_post( $post_id );
			if ( 'liveblog_entry' == $post->post_type ) {
				return
					'post-new.php?post_type=liveblog_entry&live_blogging_entry_post=' . $_POST['live_blogging_entry_post']
					. '&message=' . $args['message'];
			}
		}

		return $location;
	}

	public function mark_to_delete( $id ) {
		$post = get_post( $id );
		if ( 'liveblog_entry' == $post->post_type ) {
			$liveblogs = wp_get_object_terms( array( $id ), 'liveblog' );
			foreach ( $liveblogs as $liveblog ) {
				add_post_meta( intval( $liveblog->name ), '_liveblogging_deleted', $id );
			}
		}
	}

	public function mark_to_undelete( $id, $post ) {
		if ( 'liveblog_entry' == $post->post_type ) {
			$liveblogs = wp_get_object_terms( array( $id ), 'liveblog' );
			foreach ( $liveblogs as $liveblog ) {
				delete_post_meta( intval( $liveblog->name ), '_liveblogging_deleted', $id );
			}
		}
	}

}