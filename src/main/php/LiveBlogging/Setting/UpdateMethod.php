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

class LiveBlogging_Setting_UpdateMethod extends LiveBlogging_Setting
{
	public static $setting_name     = 'liveblogging_method';
	protected static $default_value = 'poll';

	public function sanitise_setting( $input ) {
		switch ( $input )
		{
			case 'poll':
			case 'meteor':
			case 'timed':
			case 'disabled':
				return $input;
			default:
				return 'disabled';
		}
	}

	public static function is_enabled() {
		return ! self::is( 'disabled' );
	}

	public static function is_ajax() {
		return self::is( 'poll' );
	}

	public static function is_meteor() {
		return self::is( 'meteor' );
	}

	public static function is_timed() {
		return self::is( 'timed' );
	}

	public static function admin_label() {
		_e( 'Method to use for live blog reader updating', 'live-blogging' );
	}

	public static function render_admin_options() { ?>
		<select name='liveblogging_method'>
			<option value="poll" <?php selected( self::is_ajax() ); ?>>
				<?php _e( 'Poll (default, requires no special configuration)', 'live-blogging' ); ?>
			</option>
			<option value="meteor" <?php selected( self::is_meteor() ); ?>>
				<?php _e( 'Stream using Meteor (lower server load and faster updates, but needs special configuration)', 'live-blogging' ); ?>
			</option>
			<option value="timed" <?php selected( self::is_timed() ); ?>>
				<?php _e( 'Do a full page refresh every X seconds', 'live-blogging' ); ?>
			</option>
			<option value="disabled" <?php selected( ! self::is_enabled() ); ?>>
				<?php _e( 'Disable automatic updates', 'live-blogging' ); ?>
			</option>
		</select><?php
	}

}