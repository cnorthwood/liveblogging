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

class LiveBlogging_Admin_MetaBox_QuickUpload extends LiveBlogging_Admin_MetaBox
{
	public function register_meta_box() {
		add_meta_box(
			'live_blogging_quick_upload',
			__( 'Upload Image', 'live-blogging' ),
			array( $this, 'render' ),
			'liveblog_entry',
			'side'
		);
	}

	public function render() { ?>
		<p id="async-upload-wrap">
			<label class="screen-reader-text" for="async-upload">Upload</label>
			<input type="file" name="async-upload" id="async-upload"/>
			<input type="submit" class="button" name="html-upload" id="quick-image-upload" value="Upload"/>
		</p>
	<?php
	}
}