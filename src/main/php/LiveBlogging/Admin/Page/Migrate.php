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

class LiveBlogging_Admin_Page_Migrate
{
	public function register_page() {
		if ( LiveBlogging_Legacy::exists() )
		{
			add_submenu_page(
				'tools.php',
				__( 'Migrate Live Blogging', 'live-blogging' ),
				__( 'Migrate Live Blogging', 'live-blogging' ),
				'manage_options',
				'live-blogging-upgrade',
				array( $this, 'render' )
			);
		}
	}

	public function render() { ?>
		<div class="wrap">
			<?php $this->render_header(); ?>
			<?php
			if ( isset( $_POST['live_blogging_start_migration'] ) ) :
				check_admin_referer( 'live-blogging-upgrade' );
				LiveBlogging_Legacy::migrate();
			elseif ( LiveBlogging_Legacy::exists() ) :
				$this->render_start_migration_message();
			else :
				$this->render_no_legacy_message();
			endif;
			?>
		</div>
	<?php
	}

	private function render_header() {
		?><h2><?php _e( 'Migrate Live Blogging', 'live-blogging' ); ?></h2><?php
	}

	private function render_start_migration_message() {
		?><form method="post" action="tools.php?page=live-blogging-upgrade">
		<?php wp_nonce_field( 'live-blogging-upgrade' ); ?>
		<p><label for="live_blogging_start_migration">
				<?php _e( 'Click the button below to upgrade legacy live blogs to the new system. To avoid timeout errors, only 25 entries at a time will be translated. For blogs with large numbers of live blog entries, it may be necessary to run this multiple times to convert them all.', 'live-blogging' ); ?>
			</label></p>

		<p><input type="submit" class="button-primary" name="live_blogging_start_migration" value="<?php _e( 'Start', 'live-blogging' ) ?>"/></p>
		</form><?php
	}

	private function render_no_legacy_message() {
		?><p><strong>Unable to find legacy live blog tables.</strong></p><?php
	}
}