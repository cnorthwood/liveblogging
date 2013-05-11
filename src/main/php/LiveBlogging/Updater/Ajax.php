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
	}

	public function handle_ajax() {
		header( 'Content-Type: application/json' );
		$liveblog_id = intval( $_POST['liveblog_id'] );
		$liveblog    = new LiveBlogging_LiveBlogPost( $liveblog_id );
		$response    = array();

		foreach ( $liveblog->get_liveblog_entries() as $entry ) {
			$response[] = array(
				'liveblog' => $liveblog_id,
				'id'       => $entry->id,
				'type'     => 'entry',
				'html'     => $entry->build_body(),
			);
		}

		foreach ( get_post_meta( $liveblog_id, '_liveblogging_deleted' ) as $deleted ) {
			$response[] = array(
				'liveblog' => $liveblog_id,
				'id'       => $deleted,
				'type'     => 'delete-entry',
			);
		}
		if ( LiveBlogging_Setting_Comments::is_enabled() ) {
			$liveblog   = new LiveBlogging_LiveBlogPost( $liveblog_id );
			$response[] = array(
				'liveblog' => $liveblog_id,
				'type'     => 'comments',
				'html'     => $liveblog->build_comments_html(),
			);
		}
		echo json_encode( $response );
		die();
	}

	public function javascript( $liveblog_id ) {  ?>
		<script type="text/javascript">
			/*<![CDATA[ */
			setTimeout(function(){live_blogging_poll("<?php echo esc_attr( $liveblog_id ); ?>");}, 15000);
			/*]]>*/
		</script><?php
	}


}