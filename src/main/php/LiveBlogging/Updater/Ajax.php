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

class LiveBlogging_Updater_Ajax
{
	public function __construct() {
		add_action( 'wp_ajax_live_blogging_poll', array( $this, 'handle_ajax' ) );
		add_action( 'wp_ajax_nopriv_live_blogging_poll', array( $this, 'handle_ajax' ) );
		add_action( 'delete_post', array( $this, 'mark_to_delete' ) );
		add_action( 'trash_post', array( $this, 'mark_to_delete' ) );
		add_action( 'publish_liveblog_entry', array( $this, 'mark_to_undelete' ), 10, 2 );
	}

	public function handle_ajax() {
		header( 'Content-Type: application/json' );
		$liveblog_id = $_POST['liveblog_id'];

		// Build currently active posts
		$q = new WP_Query(
			array(
				'post_type'      => 'liveblog_entry',
				'liveblog'       => $liveblog_id,
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'ASC',
				'post_status'    => 'publish',
			)
		);
		$r = array();
		while ( $q->have_posts() ) {
			$q->the_post();
			$r[] = array(
				'liveblog' => $liveblog_id,
				'id'       => $q->post->ID,
				'type'     => 'entry',
				'html'     => live_blogging_get_entry( $q->post ),
			);
		}

		foreach ( get_post_meta( $liveblog_id, '_liveblogging_deleted' ) as $deleted ) {
			$r[] = array(
				'liveblog' => $liveblog_id,
				'id'       => $deleted,
				'type'     => 'delete-entry',
			);
		}
		if ( '1' == get_option( 'liveblogging_comments' ) ) {
			$liveblog = new LiveBlogging_LiveBlog( $liveblog_id );
			$r[] = array(
				'liveblog' => $liveblog_id,
				'type'     => 'comments',
				'html'     => $liveblog->build_comments_html(),
			);
		}
		echo json_encode( $r );
		die();
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

	public function javascript( $liveblog_id ) {  ?>
		<script type="text/javascript">
			/*<![CDATA[ */
			setTimeout(function(){live_blogging_poll("<?php echo esc_attr( $liveblog_id ); ?>");}, 15000)
			/*]]>*/
		</script><?php
	}


}