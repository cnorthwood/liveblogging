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

class LiveBlogging_Twitter
{
	const CONSUMER_KEY    = '22fYrtLcFGqNMWNPel0Wfw';
	const CONSUMER_SECRET = 'cf7Q2vmCgXkQbuqvC0am216wfLAuotdHsuTl5UDlc';

	public function init() {
		add_filter( 'cron_schedules', array( $this, 'schedule_frequency' ) );
		if ( LiveBlogging_Setting_Twitter::is_enabled() && LiveBlogging_Setting_TwitterComments::is_enabled() ) {
			if ( $this->event_not_created() ) {
				$this->create_event();
			}
		} else if ( $this->get_schedule() ) {
			// Schedule is enabled even though Twitter comment importing is disabled, so we should clear it
			$this->clear_schedule();
		}

		// Twitter only works if Curl is enabled in PHP
		if ( function_exists( 'curl_init' ) && LiveBlogging_Setting_Twitter::is_enabled() ) {
			add_action( 'publish_liveblog_entry', array( $this, 'tweet_entry' ), 10, 2 );
			add_action( 'delete_post', array( $this, 'delete_tweet' ) );
			add_action( 'trash_post', array( $this, 'delete_tweet' ) );
			add_action( 'live_blogging_check_twitter', array( $this, 'poll_twitter_for_replies' ) );
		}
	}

	public function twitter_authorised() {
		return false !== get_option( 'liveblogging_twitter_token' );
	}

	public function schedule_frequency( $schedules ) {
		$schedules['live_blogging'] = array(
			'display'  => __( 'Every 5 minutes', 'live-blogging' ),
			'interval' => 300,
		);
		return $schedules;
	}

	protected function create_event() {
		wp_schedule_event( time(), 'live_blogging', 'live_blogging_check_twitter' );
	}

	protected function clear_schedule() {
		wp_clear_scheduled_hook( 'live_blogging_check_twitter' );
	}

	protected function get_schedule() {
		return wp_get_schedule( 'live_blogging_check_twitter' );
	}

	protected function event_not_created() {
		return false === $this->get_schedule();
	}

	public function tweet_entry( $id, $post ) {
		if ( ! $this->tweet_sent( $id ) ) {
			$connection = $this->get_twitter_connection();
			$content    = filter_var( $post->post_content, FILTER_SANITIZE_STRING );

			//get the hashtag (we need the id of the parent blog post, not $post->ID here)
			$parent_post_id = (int)$_POST['live_blogging_entry_post'];
			$hashtag        = get_post_meta( $parent_post_id, 'liveblogging_hashtag', true );

			//how much space do we have?
			if ( $hashtag ) {
				$tweetlength = 140 - strlen( $hashtag ) - 2;
			} else {
				$tweetlength = 140;
			}

			//do I need to trim the tweet?
			if ( strlen( $content ) > $tweetlength ) {
				$content = substr( $content, 0, $tweetlength - 1 )
					. html_entity_decode( '&hellip;', ENT_COMPAT, 'UTF-8' );
			}

			//add hashtag
			if ( $hashtag ) {
				$content .= ' #' . $hashtag;
			}

			//send tweet
			$tweet = $connection->post(
				'statuses/update',
				array( 'status' => $content )
			);
			if ( isset( $tweet->id ) ) {
				update_post_meta( $id, '_liveblogging_tweeted', $tweet->id );
			}
		}
	}

	public function delete_tweet( $id ) {
		if ( $this->tweet_sent( $id ) ) {
			if ( 'liveblog_entry' == get_post( $id )->post_type ) {
				$this->get_twitter_connection()->post( 'statuses/destroy/' . $this->get_tweet_id_for_entry( $id ) );
			}
		}
	}

	public function poll_twitter_for_replies() {
		$connection = $this->get_twitter_connection();
		foreach ( $connection->get( 'statuses/mentions' ) as $tweet ) {
			// Get the entry this tweet refers to
			$in_reply_to = $tweet->in_reply_to_status_id;
			if ( ! empty( $in_reply_to ) && ! $this->tweet_already_imported( $tweet ) ) {
				$this->mark_tweet_as_imported( $tweet );
				foreach ( $this->get_parent_post_ids_for_tweet( $in_reply_to ) as $post_id ) {
					$this->import_tweet_as_comment( $tweet, $post_id );
				}
			}
		}
	}

	private function get_tweet_id_for_entry( $id ) {
		return get_post_meta( $id, '_liveblogging_tweeted', true );
	}

	private function tweet_sent( $id ) {
		return '' != $this->get_tweet_id_for_entry( $id );
	}

	/**
	 * @return TwitterOAuth
	 */
	private function get_twitter_connection() {
		return new TwitterOAuth(
			self::CONSUMER_KEY,
			self::CONSUMER_SECRET,
			get_option( 'liveblogging_twitter_token' ),
			get_option( 'liveblogging_twitter_secret' )
		);
	}

	/**
	 * @param $tweet
	 *
	 * @return bool
	 */
	private function tweet_already_imported( $tweet ) {
		$comments = get_comments(
			array( 'author_email' => $tweet->id . '@twitter.com' )
		);
		return ! empty ( $comments ) && ! in_array( $tweet->id,  $this->get_imported_tweets() );
	}

	private function get_imported_tweets() {
		return get_option( 'liveblogging_imported_tweets', array() );
	}

	private function mark_tweet_as_imported( $tweet ) {
		$imported_tweets   = $this->get_imported_tweets();
		$imported_tweets[] = $tweet->id;
		update_option( 'liveblogging_imported_tweets', $imported_tweets );
	}

	private function get_parent_post_ids_for_tweet( $in_reply_to ) {
		$query = new WP_Query(
			array(
				'meta_key'   => '_liveblogging_tweeted',
				'meta_value' => $in_reply_to,
				'post_type'  => 'liveblog_entry',
			)
		);
		$post_ids = array();
		while ( $query->have_posts() ) {
			$query->next_post();
			// Get the post that this entry is in
			foreach ( wp_get_object_terms( array( $query->post->ID ), 'liveblog' ) as $b ) {
				$post_ids[] = intval( $b->name );
			}
		}
		return $post_ids;
	}

	private function import_tweet_as_comment( $tweet, $post_id ) {
		wp_insert_comment(
			array(
				'comment_post_ID'      => $post_id,
				'comment_author'       => $tweet->user->name . ' (@' . $tweet->user->screen_name . ')',
				'comment_author_email' => sprintf( '%0.0f', $tweet->id ) . '@twitter.com',
				'comment_author_url'   => 'http://twitter.com/' . $tweet->user->screen_name . '/status/' . sprintf( '%0.0f', $tweet->id ),
				'comment_content'      => $tweet->text,
				'user_id'              => 0,
				'comment_agent'        => 'Live Blogging for WordPress Twitter Importer',
				'comment_date'         => strftime( '%Y-%m-%d %H:%M:%S', strtotime( $tweet->created_at ) + ( get_option( 'gmt_offset' ) * 3600 ) ),
				'comment_approved'     => ( get_option( 'comment_moderation' ) == 1 ) ? 0 : 1,
			)
		);
	}

	public function render_admin() {
		$authorise_url = $this->get_twitter_authorisation_url();
	?><tr valign="top">
			<th scope="row"><?php _e( 'Connect with Twitter', 'live-blogging' ); ?></th>
			<td>
				<?php if ( $this->twitter_authorised() ) : ?>
					<?php _e( 'You are already connected to Twitter. Click the image below to change the account you are connected to.', 'live-blogging' ); ?>
					<br />
				<?php else : ?>
					<?php _e( 'You are not yet connected to Twitter. Click the image below to connect.', 'live-blogging' ); ?>
					<br/>
				<?php endif; ?>
				<?php if ( ! empty( $authorise_url ) ) : ?>
					<a href="<?php echo esc_attr( $authorise_url ); ?>">
						<img src="<?php esc_attr( plugins_url( 'twitteroauth/signin.png', __FILE__ ) ); ?>" alt="<?php _e( 'Connect with Twitter', 'live-blogging' ); ?>" />
					</a>
				<?php else : ?>
					<?php _e( 'Unable to connect to Twitter at this time.', 'live-blogging' ); ?>
				<?php endif; ?>
			</td>
		</tr><?php
	}

	/**
	 * @return string
	 */
	private function get_twitter_authorisation_url() {
		$connection    = $this->get_twitter_connection();
		$request_token = $connection->getRequestToken(
			plugins_url( 'twittercallback.php', __FILE__ )
		);
		update_option(
			'liveblogging_twitter_request_token',
			$request_token['oauth_token']
		);
		update_option(
			'liveblogging_twitter_request_secret',
			$request_token['oauth_token_secret']
		);
		if ( 200 == $connection->http_code ) {
			return $connection->getAuthorizeURL( $request_token['oauth_token'] );
		} else {
			return false;
		}
	}

}