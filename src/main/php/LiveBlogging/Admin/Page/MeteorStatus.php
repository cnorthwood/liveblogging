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

class LiveBlogging_Admin_Page_MeteorStatus
{
	public function register_page() {
		if ( LiveBlogging_Setting_UpdateMethod::is_meteor() ) {
			add_submenu_page(
				'index.php',
				__( 'Meteor Status', 'live-blogging' ),
				__( 'Meteor Status', 'live-blogging' ),
				'manage_options',
				'live-blogging-meteor-status',
				array( $this, 'render_page' )
			);
		}
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Access Denied', 'live-blogging' ) );
		} else { ?>
		<div class="wrap">
			<h2><?php _e( 'Meteor Status', 'live-blogging' ); ?></h2>
    		<pre><?php LiveBlogging::get_instance()->updater->fetch_stats(); ?></pre>
		</div><?php
		}
	}

}