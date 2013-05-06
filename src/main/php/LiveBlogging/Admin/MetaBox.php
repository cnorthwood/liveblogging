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

abstract class LiveBlogging_Admin_MetaBox {

	abstract public function register_meta_box();

	/**
	 * This method checks if the received save call is actually that of a final save, and not just an interim auto-save
	 * and that the request is valid and the user has correct permissions
	 *
	 * @param $post_id
	 * @param $nonce string the Nonce key to check
	 *
	 * @return bool
	 */
	protected function okay_to_save( $post_id, $nonce_key ) {
		return check_admin_referer( 'live_blogging_' . $nonce_key . '_meta', 'live_blogging_' . $nonce_key . '_nonce' )
			&& ! $this->doing_autosave()
			&& current_user_can( 'edit_post', $post_id );
	}

	protected function doing_autosave() {
		return ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! isset( $_POST['live_blogging_post_nonce'] );
	}
}