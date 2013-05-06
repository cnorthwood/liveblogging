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

class LiveBlogging_Setting_PostStyle extends LiveBlogging_Setting
{
	public static $setting_name     = 'liveblogging_style';
	protected static $default_value = '<p><strong>$DATE</strong></p>$CONTENT<div style="width:100%; height:1px; background-color:#6f6f6f; margin-bottom:3px;"></div>';

	public static function admin_label() {
		_e( 'Style for live blogging entries ($DATE gets replaced with the entry date/time, in the format specified above, $CONTENT with the body of the entry and $AUTHOR with the name of that author)', 'live-blogging' );
	}

	public static function render_admin_options() { ?>
		<textarea name="<?php echo esc_attr( self::$setting_name ); ?>" cols="60" rows="8"><?php echo esc_html( get_option( 'liveblogging_style' ) ); ?></textarea>
	<?php
	}

}