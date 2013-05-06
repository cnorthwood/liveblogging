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

abstract class LiveBlogging_Setting
{

	public static $setting_name;
	protected static $default_value;

	public function register_setting() {
		register_setting( 'live-blogging', self::$setting_name, array( $this, 'sanitise_setting' ) );
	}

	public function activate_setting() {
		if ( self::$default_value && false === get_option( self::$setting_name ) ) {
			add_option( self::$setting_name, self::$default_value );
		}
	}

	public function sanitise_setting( $value ) {
		return $value;
	}

	public static function get() {
		return get_option( self::$setting_name );
	}

	protected static function is( $value ) {
		return $value == self::get();
	}

	public static function is_enabled() {
		return self::is( '1' );
	}

	public static function admin_label() {}

	public static function render_admin_options() {
		?><input type="text" name="<?php echo esc_attr( self::$setting_name ); ?>" value="<?php echo esc_attr( self::get() ); ?>" /><?php
	}

}