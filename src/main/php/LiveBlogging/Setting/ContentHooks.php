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

class LiveBlogging_Setting_ContentHooks extends LiveBlogging_Setting
{
	public static $setting_name     = 'liveblogging_unhooks';
	protected static $default_value = array( 'sociable_display_hook' );

	public static function admin_label() {
		_e( 'Actions to be unhooked when displaying live blog content (advanced feature that can be used to work around badly coded plugins that mangle individual live blog entries in undesirable ways, e.g., some bookmarking plugins)', 'live-blogging' );
	}

	public static function render_admin_options() {
		$i = 0;
		$unhooks = self::get();
		if ( ! empty( $unhooks ) ) :
			foreach ( $unhooks as $unhook ) :
				++$i;
				?><div id="liveblogging_unhook_<?php echo esc_attr( $i ); ?>">
					<input type="text" name="liveblogging_unhooks[]" value="<?php echo esc_attr( $unhook ); ?>"/>
					<input type="button" id="liveblogging_unhook_delete_<?php echo esc_attr( $i ); ?>"
						   title="<?php _e( 'Delete Unhook', 'live-blogging' ); ?>"
						   style="width:16px;height:16px;background:url('<?php echo esc_attr( plugins_url( '../../img/delete.png', dirname( dirname( __FILE__ ) ) ) ); ?>');cursor:pointer;border:0;padding:0;margin:0;"
						/>
					<script type="text/javascript">
						/*<![CDATA[ */
						jQuery('#liveblogging_unhook_delete_<?php echo esc_attr( $i ); ?>').click(function () {
							jQuery('#liveblogging_unhook_<?php echo esc_attr( $i ); ?>').remove()
						});
						/*]]>*/
					</script>
				</div><br/><?php
			endforeach;
		endif;
		?>
		<input type="button" id="liveblogging_unhook_add"
			   title="<?php _e( 'Add Unhook', 'live-blogging' ); ?>"
			   style="width:16px;height:16px;background:url('<?php echo esc_attr( plugins_url( 'img/add.png', dirname( dirname( __FILE__ ) ) ) ); ?>');cursor:pointer;border:0;padding:0;margin:0;"
			/>
		<script type="text/javascript">
			/*<![CDATA[ */
			var unhook_i = <?php echo esc_attr( $i ); ?>;
			jQuery('#liveblogging_unhook_add').click(function () {
				jQuery('<div id="liveblogging_unhook_' + unhook_i + '"> \
										<input type="text" name="liveblogging_unhooks[]"  /> \
										<input type="button" id="liveblogging_unhook_delete_' + unhook_i + '" \
										title="<?php _e( 'Delete Unhook', 'live-blogging' ); ?>" \
										style="width:16px;height:16px;background:url(\'<?php echo esc_attr( plugins_url( '../../img/delete.png', dirname( dirname( __FILE__ ) ) ) ); ?>\');cursor:pointer;border:0;padding:0;margin:0;" /> \
										</div>').insertBefore('#liveblogging_unhook_add');
				jQuery('#liveblogging_unhook_delete_' + unhook_i).click(function () {
					jQuery(this.parentNode).remove()
				});
				unhook_i++;
			});
			/*]]>*/
		</script>
	<?php
	}

	public static function apply_unhooks() {
		$unhooks = self::get();
		if ( ! empty( $unhooks ) ) {
			foreach ( $unhooks as $unhook ) {
				if ( function_exists( $unhook ) ) {
					remove_filter( 'the_content', $unhook );
				}
			}
		}
	}

	public static function apply_hooks() {
		$unhooks = self::get();
		if ( ! empty( $unhooks ) ) {
			foreach ( $unhooks as $unhook ) {
				if ( function_exists( $unhook ) ) {
					add_filter( 'the_content', $unhook );
				}
			}
		}
	}
}