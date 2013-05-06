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

class LiveBlogging_Updater_Meteor
{
	private $last_error_code;
	private $last_error_str;
	private $liveblog_client_id;

	public function __construct() {
		$this->liveblog_client_id = $this->set_client_cookie();
		add_action( 'publish_liveblog_entry', array( $this, 'publish_entry' ), 100, 2 );
		add_action( 'delete_post', array( $this, 'delete_entry' ) );
		add_action( 'trash_post', array( $this, 'delete_entry' ) );
		if ( LiveBlogging_Setting_Comments::is_enabled() ) {
			add_action( 'edit_comment', array( $this, 'publish_comments' ) );
			add_action( 'comment_post', array( $this, 'publish_comments' ) );
			add_action( 'wp_set_comment_status', array( $this, 'publish_comments' ) );
		}
	}

	private function set_client_cookie() {
		if ( ! isset($_COOKIE['liveblog-client-id'] ) ) {
			$liveblog_client_id = get_option( 'liveblog_assigned_ids' );
			if ( false === $liveblog_client_id ) {
				$liveblog_client_id = 0;
			}
			update_option( 'liveblog_assigned_ids', ++$liveblog_client_id );
			setcookie( 'liveblog-client-id', $liveblog_client_id, 0, COOKIEPATH );
		} else {
			$liveblog_client_id = $_COOKIE['liveblog-client-id'];
		}
		return $liveblog_client_id;
	}

	public function fetch_stats() {
		if ( strlen( LiveBlogging_Setting_MeteorController::get() ) > 0 ) {
			// If Meteor is configured, connect to it and get the output of SHOWSTATS
			$stats = $this->run_meteor_command( array( 'SHOWSTATS' ) );
			if ( $stats === false ) {
				_e( 'Unable to establish connection to Meteor:', 'live-blogging' );
				echo esc_html( " ($this->last_error_code)$this->last_error_str \n" );
			} else {
				echo esc_html( $stats );
			}
		} else {
			_e( 'Connection to Meteor not configured.', 'live-blogging' );
		}
	}

	private function run_meteor_command( $commands ) {
		$fp = fsockopen(
			get_option( LiveBlogging_Setting_MeteorController::get() ),
			get_option( LiveBlogging_Setting_MeteorControllerPort::get() ),
			$this->last_error_code, $this->last_error_str, 30
		);
		if ( ! $fp ) {
			return false;
		} else {
			$response = '';
			foreach ( $commands as $command ) {
				fwrite( $fp, "$command\n" );
			}
			fwrite( $fp, "QUIT\n" );
			while ( ! feof( $fp ) ) {
				$response .= fgets( $fp );
			}
			fclose( $fp );
		}
		return $response;
	}

	public function publish_entry( $id, $entry ) {
		$liveblogs = wp_get_object_terms( array( $id ), 'liveblog' );
		if ( isset( $_POST['live_blogging_entry_post'] ) ) {
			$liveblogs[] = (object)array( 'name' => $_POST['live_blogging_entry_post'] );
		}
		$entries_to_submit = array();
		foreach ( $liveblogs as $liveblog ) {
			$message_body = array(
				'liveblog' => $liveblog->name,
				'id' => $id,
				'type' => 'entry',
				'html' => live_blogging_get_entry( $entry ),
			);
			$entries_to_submit[] = 'ADDMESSAGE ' . get_option( 'liveblogging_id' ) . '-liveblog-' . $liveblog->name
				. ' ' . addslashes( json_encode( $message_body ) );
		}
		$this->run_meteor_command( $entries_to_submit );
	}

	public function delete_entry( $id ) {
		$entries_to_submit = array();
		$post              = get_post( $id );
		if ( 'liveblog_entry' == $post->post_type ) {
			$liveblogs = wp_get_object_terms( array( $id ), 'liveblog' );
			foreach ( $liveblogs as $liveblog ) {
				$e = array(
					'liveblog' => $liveblog->name,
					'id'       => $id,
					'type'     => 'delete-entry',
				);
				$entries_to_submit[] = 'ADDMESSAGE '
						. get_option( 'liveblogging_id' ) . '-liveblog-' . $liveblog->name
						. ' ' . addslashes( json_encode( $e ) );
			}
		}
		$this->run_meteor_command( $entries_to_submit );
	}

	public function publish_comments( $id ) {
		$comment      = get_comment( $id );
		$liveblog     = new LiveBlogging_LiveBlog( $comment->comment_post_ID );
		$message_body = array(
			'liveblog' => $comment->comment_post_ID,
			'type'     => 'comments',
			'html'     => $liveblog->build_comments_html(),
		);
		$this->run_meteor_command(
			array(
				'ADDMESSAGE '
				. get_option( 'liveblogging_id' ) . '-liveblog-' . $comment->comment_post_ID
				. ' ' . addslashes( json_encode( $message_body ) )
			)
		);
	}

	public function javascript( $liveblog_id ) {
		global $liveblog_client_id; ?>
		<script type="text/javascript" src="http://<?php echo esc_attr( LiveBlogging_Setting_MeteorSubscriber::get() );?>/meteor.js"></script>
		<script type="text/javascript">
			/*<![CDATA[ */
			// Configure Meteor
			Meteor.hostid = "<?php echo esc_attr( $liveblog_client_id ); ?>";
			Meteor.host = "<?php echo esc_attr( LiveBlogging_Setting_MeteorSubscriber::get() ); ?>";
			Meteor.registerEventCallback("process", live_blogging_handle_data);
			Meteor.joinChannel("<?php echo esc_attr( get_option( 'liveblogging_id' ) ); ?>-liveblog-<?php echo esc_attr( $liveblog_id ); ?>", 2);
			Meteor.mode = "stream";
			jQuery(document).ready(function() {
				Meteor.connect();
			});
			/*]]>*/
		</script><?php
	}
}