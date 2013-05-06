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

class LiveBlogging_Admin_MetaBox_Select extends LiveBlogging_Admin_MetaBox
{
	public function register_meta_box() {
		add_meta_box(
			'live_blogging_post_select',
			__( 'Select Live Blog', 'live-blogging' ),
			array( $this, 'render' ),
			'liveblog_entry',
			'side'
		);
		add_action( 'save_post', array( $this, 'handle_save' ) );
	}

	public function render() {
		wp_nonce_field( 'live_blogging_entry_meta', 'live_blogging_entry_nonce' );
		$liveblogs = array_merge(
			$this->get_active_liveblogs( 'post' ),
			$this->get_active_liveblogs( 'page' ),
			$this->get_liveblogs_for_current_post()
		);

		if ( count( $liveblogs ) == 0 ) {
			echo '<p>' . __( 'There are no currently active live blogs.', 'live-blogging' ) . '</p>';
		} else {
			// Sort by key in reverse order, puts the more recent items at the top of the list
			krsort( $liveblogs );
			$this->render_select_list( $liveblogs, $this->guess_default_liveblog_id() );
		}
	}

	private function render_select_list( $liveblogs, $active_id ) { ?>
		<label for="live_blogging_entry_post"><?php _e( 'Select live blog', 'live-blogging' ); ?></label><br/>
		<select id="live_blogging_entry_post" name="live_blogging_entry_post" onchange="live_blogging_update_chatbox()">
			<?php foreach ( $liveblogs as $liveblog_id => $liveblog_name ) : ?>
				<option value="<?php echo esc_html( $liveblog_id ); ?>" <?php selected( $liveblog_id, $active_id ); ?>>
					<?php echo esc_html( $liveblog_name ); ?>
				</option>
			<?php endforeach; ?>
		</select><?php
	}

	/**
	 * @param $post_type
	 * @return array
	 */
	public function get_active_liveblogs( $post_type ) {
		$liveblogs = array();

		$query = new WP_Query(
			array(
				'meta_key'       => '_liveblog',
				'meta_value'     => '1',
				'post_type'      => $post_type,
				'posts_per_page' => - 1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		// Get all active live blogs
		while ( $query->have_posts() ) {
			$query->next_post();
			$liveblogs[$query->post->ID] = esc_attr( $query->post->post_title );
		}
		return $liveblogs;
	}

	public function get_liveblogs_for_current_post() {
		global $post;
		$liveblogs = array();
		$associated_live_blogs = wp_get_object_terms( array( $post->ID ), 'liveblog' );
		foreach ( $associated_live_blogs as $liveblog_id ) {
			$liveblogs[$liveblog_id->name] = live_blogging_taxonomy_name( $liveblog_id );
		}
		return $liveblogs;
	}

	/**
	 * Attempts to figure out which liveblog the user wants to use - either the one sent in the last save, or
	 * one of the ones associated with this page (there should only be 1)
	 *
	 * @return int
	 */
	private function guess_default_liveblog_id() {
		if ( isset( $_GET['live_blogging_entry_post'] ) ) {
			return $_GET['live_blogging_entry_post'];
		} else {
			$associated_liveblogs = $this->get_liveblogs_for_current_post();
			if ( ! empty( $associated_liveblogs ) ) {
				return $associated_liveblogs[0];
			} else {
				return 0;
			}
		}
	}

	public function handle_save( $post_id ) {
		if ( $this->okay_to_save( $post_id, 'entry' ) ) {
			wp_set_post_terms( $post_id, $_POST['live_blogging_entry_post'], 'liveblog' );
		}
	}

	protected function okay_to_save( $post_id, $nonce_key ) {
		if ( ! isset( $_POST['live_blogging_entry_post'] ) ) {
			wp_delete_post( $post_id, true );
			wp_die( '<p>' . __( 'There are no currently active live blogs.', 'live-blogging' ) . '</p>' );
		}
		return parent::okay_to_save( $post_id, $nonce_key );
	}

}