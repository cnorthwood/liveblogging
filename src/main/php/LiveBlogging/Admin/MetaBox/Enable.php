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

class LiveBlogging_Admin_MetaBox_Enable extends LiveBlogging_Admin_MetaBox
{
	public function register_meta_box() {
		foreach ( array( 'post', 'page' ) as $post_type ) {
			add_meta_box(
				'live_blogging_post_enable',
				__( 'Enable Live Blog', 'live-blogging' ),
				array( $this, 'render' ),
				$post_type,
				'side'
			);
			add_action( 'save_' . $post_type, array( $this, 'handle_save' ) );
		}
	}

	public function render() {
		global $post;
		wp_nonce_field( 'live_blogging_post_meta', 'live_blogging_post_nonce' );
		?>

		<p><input type="checkbox"
				  id="live_blogging_post_enable_box"
				  name="live_blogging_post_enable"
				  value="enabled"
				<?php checked( get_post_meta( $post->ID, '_liveblog_active', true ), '1' ); ?>
				/>
			<label for="live_blogging_post_enable_box"><?php _e(
					'Enable live blogging on this post', 'live-blogging'
				); ?></label>
		</p>
		<p><em><?php _e(
					'Please remember to include the [liveblog] shortcode where you want the liveblog in this post to appear.',
					'live-blogging'
				); ?></em></p>
	<?php
	}

	public function handle_save( $post_id ) {
		if ( $this->okay_to_save( $post_id, 'post' ) )
		{
			$this->mark_post_as_liveblog( $post_id, $this->body_contains_shortcode() );
			$this->mark_post_as_active_liveblog( $post_id, $this->active_checkbox_enabled() );
		}
	}


	private function body_contains_shortcode() {
		return preg_match( '/\[liveblog\]/ism', $_POST['post_content'] );
	}

	private function active_checkbox_enabled() {
		return isset( $_POST['live_blogging_post_enable'] ) && 'enabled' == $_POST['live_blogging_post_enable'];
	}

	/**
	 * @param $post_id
	 * @param $flag boolean indicates whether or not this is a liveblog
	 */
	private function mark_post_as_liveblog( $post_id, $flag ) {

		update_post_meta( $post_id, '_liveblog', $flag ? '1' : '0' );
	}

	/**
	 * @param $post_id
	 * @param $flag boolean indicates whether or not this is an active liveblog
	 */
	private function mark_post_as_active_liveblog( $post_id, $flag ) {
		update_post_meta( $post_id, '_liveblog_active', $flag ? '1' : '0' );
	}

}