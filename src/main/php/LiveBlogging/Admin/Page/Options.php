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

class LiveBlogging_Admin_Page_Options
{
	public function register_page() {
		add_submenu_page(
			'options-general.php',
			__( 'Live Blogging', 'live-blogging' ),
			__( 'Live Blogging', 'live-blogging' ),
			'manage_options',
			'live-blogging-options',
			array( $this, 'render' )
		);
	}

	public function render()
{
?>
	<div class="wrap">
		<h2><?php _e( 'Live Blogging Options', 'live-blogging' ); ?></h2>

		<?php if ( LiveBlogging_Legacy::exists() ) : ?>
			<?php $this->render_migration_prompt(); ?>
		<?php endif; ?>


		<form method="post" action="options.php">
			<?php settings_fields( 'live-blogging' ); ?>
			<h3><?php _e( 'Automatic Updating', 'live-blogging' ); ?></h3>
			<table class="form-table">
				<?php $this->render_setting( 'LiveBlogging_Setting_UpdateMethod' ); ?>
				<?php $this->render_setting( 'LiveBlogging_Setting_Comments' ); ?>
				<?php $this->render_setting( 'LiveBlogging_Setting_TimedUpdatedFrequency' ); ?>
			</table>

			<h3><?php _e( 'Posting to Twitter', 'live-blogging' ); ?></h3>
			<table class="form-table">
				<?php if ( function_exists( 'curl_init' ) ) : ?>
					<?php LiveBlogging::get_instance()->twitter->render_admin(); ?>
				<?php else : ?>
					<tr>
						<td colspan="2">
							<strong><em>
								<?php _e( 'Twitter functionality disabled due to missing PHP CURL module', 'live-blogging' ); ?>
							</em></strong>
						</td>
					</tr>
				<?php endif; ?>
				<?php if ( LiveBlogging::get_instance()->twitter->twitter_authorised() ) : ?>
					<?php $this->render_setting( 'LiveBlogging_Setting_Twitter' ); ?>
					<?php $this->render_setting( 'LiveBlogging_Setting_TwitterComments' ); ?>
				<?php endif; ?>
			</table>

			<h3><?php _e( 'Live Blog Style', 'live-blogging' ); ?></h3>
			<table class="form-table">
				<?php $this->render_setting( 'LiveBlogging_Setting_UpdateStyle' ); ?>
				<?php $this->render_setting( 'LiveBlogging_Setting_DateStyle' ); ?>
				<?php $this->render_setting( 'LiveBlogging_Setting_PostStyle' ); ?>
			</table>

			<h3><?php _e( 'Meteor Configuration', 'live-blogging' ); ?></h3>
			<table class="form-table">
				<?php $this->render_setting( 'LiveBlogging_Setting_MeteorSubscriber' ); ?>
				<?php $this->render_setting( 'LiveBlogging_Setting_MeteorController' ); ?>
				<?php $this->render_setting( 'LiveBlogging_Setting_MeteorControllerPort' ); ?>
				<?php $this->render_setting( 'LiveBlogging_Setting_MeteorNamespace' ); ?>
			</table>

			<h3><?php _e( 'Advanced Settings', 'live-blogging' ); ?></h3>
			<table class="form-table">
				<?php $this->render_setting( 'LiveBlogging_Setting_ContentHooks' ); ?>
			</table>

			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" />
			</p>

		</form>
	</div><?php
	}

	private function render_migration_prompt() { ?>
	<div class="updated">
		<p>
			<?php _e( 'It appears you have live blogs created in version 1 of the plugin.', 'live-blogging' ); ?>
			<a href="tools.php?page=live-blogging-upgrade"><?php _e( 'Click here to update your legacy live blogs.', 'live-blogging' ); ?></a>
		</p>
	</div><?php
	}

	/**
	 * @param $setting_name string
	 */
	private function render_setting( $setting_name ) {
		/** @var LiveBlogging_Setting $setting */
		$setting = new $setting_name();
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $setting::$setting_name ); ?>"><?php $setting->admin_label(); ?></label></th>
			<td><?php $setting::render_admin_options(); ?></td>
		</tr><?php
	}

}