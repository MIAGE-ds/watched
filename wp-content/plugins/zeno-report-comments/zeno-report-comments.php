<?php
/*
Plugin Name: Zeno Report Comments
Plugin Script: zeno-report-comments.php
Plugin URI: http://wordpress.org/extend/plugins/zeno-report-comments/
Description: This script gives visitors the possibility to flag/report a comment as inapproriate.
After reaching a threshold the comment is moved to moderation. If a comment is approved once by a moderator future reports will be ignored.
Version: 1.2.4
Author: Marcel Pol
Author URI: http://zenoweb.nl
Text Domain: zeno-report-comments
Domain Path: /lang/
Forked from: http://wordpress.org/extend/plugins/safe-report-comments/
*/


/*
	Copyright 2010 - 2016  Thorsten Ott
	Copyright 2012 - 2013  Daniel Bachhuber
	Copyright 2014 - 2014  Mohammad Jangda
	Copyright 2015 - 2015  Ronald Huereca, Jason Lemieux (Postmatic)
	Copyright 2016 - 2019  Marcel Pol      (email: marcel@timelord.nl)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
 * Todo:
 * - Compatibility with https://decomments.com/ plugin.
 * - Compatibility with translation and edupro theme:
 *   https://wordpress.org/support/topic/translate-180/
 *
 */


if ( ! class_exists( 'Zeno_Report_Comments' ) ) {

	class Zeno_Report_Comments {

		private $_plugin_version = '1.2.4';
		private $_plugin_prefix  = 'zrcmnt';
		private $_admin_notices  = array();
		private $_nonce_key      = 'zeno_flag_comment_nonce';
		private $_auto_init      = true;
		private $_storagecookie  = 'zfrc_flags';

		public $plugin_url = false;

		public $thank_you_message;
		public $invalid_nonce_message;
		public $invalid_values_message;
		public $already_flagged_message;
		public $already_flagged_note; // displayed instead of the report link when a comment was flagged.
		public $already_moderated_message;
		public $moderated_message;

		public $filter_vars = array( 'thank_you_message', 'invalid_nonce_message', 'invalid_values_message', 'already_flagged_message', 'already_flagged_note', 'already_moderated_message', 'moderated_message' );

		// amount of possible attempts transient hits per comment before a COOKIE enabled negative check is considered invalid
		// transient hits will be counted up per ip any time a user flags a comment
		// this number should be always lower than your threshold to avoid manipulation
		public $no_cookie_grace    = 3;
		public $cookie_lifetime    = 604800; // lifetime of the cookie ( 1 week ). After this duration a user can report a comment again
		public $transient_lifetime = 86400; // lifetime of fallback transients. lower to keep things usable and c


		public function __construct( $auto_init = true ) {

			$this->thank_you_message         = __( 'Thank you for your feedback. We will look into it.', 'zeno-report-comments' ) . '<!-- flagged -->';
			$this->invalid_nonce_message     = __( 'The Nonce was invalid. Please refresh and try again.', 'zeno-report-comments' ) . ' <!-- nonce invalid -->';
			$this->invalid_values_message    = __( 'Cheating huh?', 'zeno-report-comments' ) . ' <!-- invalid values -->';
			$this->already_flagged_message   = __( 'It seems you already reported this comment.', 'zeno-report-comments' ) . ' <!-- already flagged -->';
			$this->already_flagged_note      = '<!-- already flagged -->'; // displayed instead of the report link when a comment was flagged.
			$this->already_moderated_message = '<!-- already moderated -->';
			$this->moderated_message         = '0 ' . __( 'moderated', 'zeno-report-comments' ) . '<!-- moderated -->';

			$this->_admin_notices = get_transient( $this->_plugin_prefix . '_notices' );
			if ( ! is_array( $this->_admin_notices ) ) {
				$this->_admin_notices = array();
			}
			$this->_admin_notices = array_unique( $this->_admin_notices );
			$this->_auto_init = $auto_init;

			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && true === DOING_AJAX ) ) {
				add_action( 'init', array( $this, 'frontend_init' ) );
			} else if ( is_admin() ) {
				add_action( 'admin_init', array( $this, 'backend_init' ) );
				add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
			}
			add_action( 'wp_set_comment_status', array( $this, 'mark_comment_moderated' ), 10, 2 );

			// apply some filters to easily alter the frontend messages
			// add_filter( 'zeno_report_comments_thank_you_message', 'alter_message' ); // this or similar will do the job
			foreach( $this->filter_vars as $var ) {
				$this->{$var} = apply_filters( 'zeno_report_comments_' . $var , $this->{$var} );
			}

			add_action( 'plugins_loaded', array( $this, 'load_language' ) );
		}


		public function __destruct() {

		}


		/*
		 * Initialize backend functions
		 * - admin_header
		 */
		public function backend_init() {
			do_action( 'zeno_report_comments_backend_init' );

			add_settings_field( $this->_plugin_prefix . '_enabled', __( 'Allow comment flagging', 'zeno-report-comments' ), array( $this, 'comment_flag_enable' ), 'discussion', 'default' );
			register_setting( 'discussion', $this->_plugin_prefix . '_enabled' );

			if ( ! $this->is_enabled() ) {
				return;
			}

			add_settings_field( $this->_plugin_prefix . '_threshold', __( 'Flagging threshold', 'zeno-report-comments' ) , array( $this, 'comment_flag_threshold' ), 'discussion', 'default' );
			register_setting( 'discussion', $this->_plugin_prefix . '_threshold', array( $this, 'check_threshold' ) );

			add_settings_field( $this->_plugin_prefix . '_admin_notification', __( 'Administrator notifications', 'zeno-report-comments' ), array( $this, 'comment_admin_notification_setting' ), 'discussion', 'default' );
			register_setting( 'discussion', $this->_plugin_prefix . '_admin_notification' );

			add_settings_field( $this->_plugin_prefix . '_admin_notification_each', __( 'Administrator notifications', 'zeno-report-comments' ), array( $this, 'comment_admin_notification_each_setting' ), 'discussion', 'default' );
			register_setting( 'discussion', $this->_plugin_prefix . '_admin_notification_each' );

			add_filter('manage_edit-comments_columns', array( $this, 'add_comment_reported_column' ) );
			add_filter('manage_edit-comments_sortable_columns', array( $this, 'add_comment_reported_column' ) );

			add_action('manage_comments_custom_column', array( $this, 'manage_comment_reported_column' ), 10, 2);

			add_action( 'admin_head', array( $this, 'admin_header' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		}


		/*
		 * Initialize frontend functions
		 */
		public function frontend_init() {

			if ( ! $this->is_enabled() ) {
				return;
			}

			if ( ! $this->plugin_url ) {
				$this->plugin_url = plugins_url( false, __FILE__ );
			}

			do_action( 'zeno_report_comments_frontend_init' );

			add_action( 'wp_ajax_zeno_report_comments_flag_comment', array( $this, 'flag_comment' ) );
			add_action( 'wp_ajax_nopriv_zeno_report_comments_flag_comment', array( $this, 'flag_comment' ) );

			// Admin, but AJAX
			add_action( 'wp_ajax_zeno_report_comments_moderate_comment', array( $this, 'moderate_comment' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'action_enqueue_scripts' ) );

			if ( $this->_auto_init ) {
				// Hooks into reply links, works only on threaded comments and not on the max threaded comment in the thread.
				add_filter( 'comment_reply_link', array( $this, 'add_flagging_link_to_reply_link' ) );
				// Hooks into comment content, but only if threading and replies are disabled.
				add_filter( 'get_comment_text', array( $this, 'add_flagging_link_to_content' ) );
			}
			add_action( 'comment_report_abuse_link', array( $this, 'print_flagging_link' ) );
			add_action( 'template_redirect', array( $this, 'add_test_cookie' ) ); // need to do this at template_redirect because is_feed isn't available yet.

			add_action( 'zeno_report_comments_mark_flagged', array( $this, 'admin_notification' ) );
			add_action( 'zeno_report_comments_add_report', array( $this, 'admin_notification_each' ) );

		}


		public function action_enqueue_scripts() {

			// Use home_url() if domain mapped to avoid cross-domain issues
			if ( home_url() != site_url() ) {
				$ajaxurl = home_url( '/wp-admin/admin-ajax.php' );
			} else {
				$ajaxurl = admin_url( 'admin-ajax.php' );
			}
			$ajaxurl = apply_filters( 'zeno_report_comments_ajax_url', $ajaxurl );

			wp_enqueue_script( $this->_plugin_prefix . '-ajax-request', $this->plugin_url . '/js/ajax.js', array( 'jquery' ), $this->_plugin_version, true );

			$nonce = wp_create_nonce( $this->_plugin_prefix . '_' . $this->_nonce_key );
			$dataToBePassed = array(
				'ajaxurl' => $ajaxurl,
				'nonce'   => $nonce,
			);
			wp_localize_script( $this->_plugin_prefix . '-ajax-request', 'ZenoCommentsAjax', $dataToBePassed );

		}


		public function admin_enqueue_scripts() {

			// Use home_url() if domain mapped to avoid cross-domain issues
			if ( home_url() != site_url() ) {
				$ajaxurl = home_url( '/wp-admin/admin-ajax.php' );
			} else {
				$ajaxurl = admin_url( 'admin-ajax.php' );
			}
			$ajaxurl = apply_filters( 'zeno_report_comments_ajax_url', $ajaxurl );

			wp_enqueue_script( $this->_plugin_prefix . '-admin-ajax-request', plugins_url( '/js/admin-ajax.js', __FILE__ ), array( 'jquery' ), $this->_plugin_version, true );

			$nonce = wp_create_nonce( $this->_plugin_prefix . '_' . $this->_nonce_key );
			$dataToBePassed = array(
				'ajaxurl' => $ajaxurl,
				'nonce'   => $nonce,
			);
			wp_localize_script( $this->_plugin_prefix . '-admin-ajax-request', 'ZenoCommentsAjax', $dataToBePassed );

		}


		public function add_test_cookie() {
			//Set a cookie now to see if they are supported by the browser.
			// Don't add cookie if it's already set; and don't do it for feeds
			if ( ! is_feed() && ! isset( $_COOKIE[ TEST_COOKIE ] ) ) {
				@setcookie(TEST_COOKIE, 'WP Cookie check', 0, COOKIEPATH, COOKIE_DOMAIN);
				if ( SITECOOKIEPATH != COOKIEPATH ) {
					@setcookie(TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, COOKIE_DOMAIN);
				}
			}
		}


		/*
		 * Add necessary header scripts
		 * Currently only used for admin notices
		 */
		public function admin_header() {
			// print admin notice in case of notice strings given
			if ( ! empty( $this->_admin_notices ) ) {
				add_action('admin_notices' , array( $this, 'print_admin_notice' ) );
			}
?>
<style type="text/css">
.column-comment_reported {
	width: 8em;
}
</style>
<?php
		}


		/*
		 * Add admin error messages
		 */
		protected function add_admin_notice( $message ) {
			$this->_admin_notices[] = $message;
			set_transient( $this->_plugin_prefix . '_notices', $this->_admin_notices, 3600 );
		}


		/*
		 * Print a notification / error msg
		 */
		public function print_admin_notice() {
			?>
			<div id="message" class="updated fade notice is-dismissible">
				<h3><?php _e('Zeno Comments:', 'zeno-report-comments'); ?></h3>
				<?php

				foreach( (array) $this->_admin_notices as $notice ) {
					?>
					<p><?php echo wp_kses_post( $notice ); ?></p>
					<?php
				}
				?>
			</div>
			<?php

			$this->_admin_notices = array();
			delete_transient( $this->_plugin_prefix . '_notices' );
		}


		/*
		 * Callback for settings field
		 */
		public function comment_flag_enable() {
			$enabled = $this->is_enabled();
			?>
			<label for="<?php echo esc_attr( $this->_plugin_prefix ); ?>_enabled">
				<input name="<?php echo esc_attr( $this->_plugin_prefix ); ?>_enabled" id="<?php echo esc_attr( $this->_plugin_prefix ); ?>_enabled" type="checkbox" value="1" <?php if ( $enabled === true ) echo ' checked="checked"'; ?> />
				<?php _e( 'Allow your visitors to flag a comment as inappropriate.', 'zeno-report-comments' ); ?>
			</label>
			<?php
		}


		/*
		 * Callback for settings field
		 */
		public function comment_flag_threshold() {
			$threshold = (int) get_option( $this->_plugin_prefix . '_threshold' );
			?>
			<label for="<?php echo esc_attr( $this->_plugin_prefix ); ?>_threshold">
				<input size="2" name="<?php echo esc_attr( $this->_plugin_prefix ); ?>_threshold" id="<?php echo esc_attr( $this->_plugin_prefix ); ?>_threshold" type="text" value="<?php echo esc_attr( $threshold ); ?>" />
				<?php _e( 'Amount of user reports needed to send a comment to moderation?', 'zeno-report-comments' ); ?>
			</label>
			<?php
		}


		/*
		 * comment_admin_notification_setting - Discussions setting
		 *
		 * Discussions setting
		 *
		 * @since 1.0
		 *
		 * @access public
		 *
		 */
		public function comment_admin_notification_setting() {
			$enabled = $this->is_admin_notification_enabled();
			?>
			<label for="<?php echo $this->_plugin_prefix; ?>_admin_notification">
				<input name="<?php echo $this->_plugin_prefix; ?>_admin_notification"
				       id="<?php echo $this->_plugin_prefix; ?>_admin_notification" type="checkbox"
				       value="1" <?php checked( true, $enabled ); ?> />
				<?php _e( 'Send administrators an email when a user has sent a comment to moderation.', 'zeno-report-comments' ); ?>
			</label>
			<?php
		}


		/*
		 * comment_admin_notification_each_setting - Discussions setting
		 *
		 * Discussions setting
		 *
		 * @since 1.0
		 *
		 * @access public
		 *
		 */
		public function comment_admin_notification_each_setting() {
			$enabled = $this->is_admin_notification_each_enabled();
			?>
			<label for="<?php echo $this->_plugin_prefix; ?>_admin_notification_each">
				<input name="<?php echo $this->_plugin_prefix; ?>_admin_notification_each"
				       id="<?php echo $this->_plugin_prefix; ?>_admin_notification_each" type="checkbox"
				       value="1" <?php checked( true, $enabled ); ?> />
				<?php _e( 'Send administrators an email each time a user has reported on a comment.', 'zeno-report-comments' ); ?>
			</label>
			<?php
		}


		/*
		 * Check if the functionality is enabled or not
		 */
		public function is_enabled() {
			$enabled = get_option( $this->_plugin_prefix . '_enabled' );
			if ( $enabled == 1 ) {
				$enabled = true;
			} else {
				$enabled = false;
			}
			return $enabled;
		}


		/*
		 * Validate threshold, callback for settings field
		 */
		public function check_threshold( $value ) {
			if ( (int) $value <= 0 || (int) $value > 100 ) {
				$this->add_admin_notice( __('Please revise your flagging threshold and enter a number between 1 and 100', 'zeno-report-comments' ) );
			}
			return (int) $value;
		}


		/*
		 * is_admin_notification_enabled - Is the admin notification or not
		 *
		 * Is the admin notification or not
		 *
		 * @since 1.0
		 *
		 * @access public
		 *
		 * @returns true if yes, false if not
		 */
		public function is_admin_notification_enabled() {
			$enabled = get_option( $this->_plugin_prefix . '_admin_notification', 1 );
			if ( $enabled == 1 )
				$enabled = true;
			else
				$enabled = false;
			return $enabled;
		}


		/*
		 * is_admin_notification_each_enabled - Is the admin notification or not
		 *
		 * Is the admin notification or not
		 *
		 * @since 1.0
		 *
		 * @access public
		 *
		 * @returns true if yes, false if not
		 */
		public function is_admin_notification_each_enabled() {
			$enabled = get_option( $this->_plugin_prefix . '_admin_notification_each', 1 );
			if ( $enabled == 1 )
				$enabled = true;
			else
				$enabled = false;
			return $enabled;
		}


		/*
		 * Helper functions to (un)/serialize cookie values
		 */
		private function serialize_cookie( $value ) {
			$value = $this->clean_cookie_data( $value );
			return base64_encode( json_encode( $value ) );
		}


		private function unserialize_cookie( $value ) {
			$data = json_decode( base64_decode( $value ) );
			return $this->clean_cookie_data( $data );
		}


		private function clean_cookie_data( $data ) {
			$clean_data = array();

			if ( is_object( $data ) ) {
				// json_decode decided to make an object. Turn it into an array.
				$data = get_object_vars( $data );
			}

			if ( ! is_array( $data ) ) {
				$data = array();
			}

			foreach ( $data as $comment_id => $count ) {
				if ( is_numeric( $comment_id ) && is_numeric( $count ) ) {
					$clean_data[ $comment_id ] = $count;
				}
			}

			return $clean_data;
		}


		/*
		 * Mark a comment as being moderated so it will not be autoflagged again.
		 * Remove the reports to clean up the database. Moderator decided already anyway.
		 */
		public function mark_comment_moderated( $comment_id, $status ) {
			if ( $status == 'approve' ) {
				update_comment_meta( $comment_id, $this->_plugin_prefix . '_moderated', true );
				delete_comment_meta( $comment_id, $this->_plugin_prefix . '_reported' );
			}
		}


		/*
		 * Check if this comment was flagged by the user before
		 */
		public function already_flagged( $comment_id ) {
			// check if cookies are enabled and use cookie store
			if ( isset( $_COOKIE[ TEST_COOKIE ] ) ) {
				if ( isset( $_COOKIE[ $this->_storagecookie ] ) ) {
					$data = $this->unserialize_cookie( $_COOKIE[ $this->_storagecookie ] );
					if ( is_array( $data ) && isset( $data[ $comment_id ] ) ) {
						return true;
					}
				}
			}


			// in case we don't have cookies. fall back to transients, block based on IP/User Agent
			if ( $transient = get_transient( md5( $this->_storagecookie . $this->get_user_ip() ) ) ) {
				if (
					// check if no cookie and transient is set
					 ( !isset( $_COOKIE[ TEST_COOKIE ] ) && isset( $transient[ $comment_id ] ) ) ||
					// or check if cookies are enabled and comment is not flagged but transients show a relatively high number and assume fraud
					 ( isset( $_COOKIE[ TEST_COOKIE ] )  && isset( $transient[ $comment_id ] ) && $transient[ $comment_id ] >= $this->no_cookie_grace )
					) {
						return true;
				}
			}
			return false;
		}


		/*
		 * Validate user IP, include known proxy headers if needed
		 */
		function get_user_ip() {
			$include_proxy = apply_filters( 'zeno_report_comments_include_proxy_ips', false );
			if ( true === $include_proxy ) {
				$proxy_headers = array(
					'HTTP_VIA',
					'HTTP_X_FORWARDED_FOR',
					'HTTP_FORWARDED_FOR',
					'HTTP_X_FORWARDED',
					'HTTP_FORWARDED',
					'HTTP_CLIENT_IP',
					'HTTP_FORWARDED_FOR_IP',
					'VIA',
					'X_FORWARDED_FOR',
					'FORWARDED_FOR',
					'X_FORWARDED',
					'FORWARDED',
					'CLIENT_IP',
					'FORWARDED_FOR_IP',
					'HTTP_PROXY_CONNECTION',
					'REMOTE_ADDR'
				);
				$remote_ip = false;
				foreach ( $proxy_headers as $header ) {
					if ( isset( $_SERVER[ $header ] ) ) {
						$remote_ip = $_SERVER[ $header ];
						break;
					}
				}
				return $remote_ip;
			}

			$remote_ip = $_SERVER[ 'REMOTE_ADDR' ];
			return $remote_ip;
		}


		/*
		 * Report a comment and send it to moderation if threshold is reached
		 */
		public function mark_flagged( $comment_id ) {
			$data = array();
			if( isset( $_COOKIE[ TEST_COOKIE ] ) ) {
				if ( isset( $_COOKIE[ $this->_storagecookie ] ) ) {
					$data = $this->unserialize_cookie( $_COOKIE[ $this->_storagecookie ] );
					if ( ! isset( $data[ $comment_id ] ) ) {
						$data[ $comment_id ] = 0;
					}
					$data[ $comment_id ]++;
					$cookie = $this->serialize_cookie( $data );
					@setcookie( $this->_storagecookie, $cookie, time()+$this->cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN );
					if ( SITECOOKIEPATH != COOKIEPATH ) {
						@setcookie( $this->_storagecookie, $cookie, time()+$this->cookie_lifetime, SITECOOKIEPATH, COOKIE_DOMAIN);
					}
				} else {
					if ( ! isset( $data[ $comment_id ] ) ) {
						$data[ $comment_id ] = 0;
					}
					$data[ $comment_id ]++;
					$cookie = $this->serialize_cookie( $data );
					@setcookie( $this->_storagecookie, $cookie, time()+$this->cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN );
					if ( SITECOOKIEPATH != COOKIEPATH ) {
						@setcookie( $this->_storagecookie, $cookie, time()+$this->cookie_lifetime, SITECOOKIEPATH, COOKIE_DOMAIN);
					}
				}
			}
			// in case we don't have cookies. fall back to transients, block based on IP, shorter timeout to keep mem usage low and don't lock out whole companies
			$transient = get_transient( md5( $this->_storagecookie . $this->get_user_ip() ) );
			if ( ! $transient ) {
				set_transient( md5( $this->_storagecookie . $this->get_user_ip() ), array( $comment_id => 1), $this->transient_lifetime );
			} else {
				$transient[ $comment_id ]++;
				set_transient( md5( $this->_storagecookie . $this->get_user_ip() ), $transient, $this->transient_lifetime );
			}


			$threshold = (int) get_option( $this->_plugin_prefix . '_threshold' );
			$current_reports = get_comment_meta( $comment_id, $this->_plugin_prefix . '_reported', true );
			$current_reports++;
			update_comment_meta( $comment_id, $this->_plugin_prefix . '_reported', $current_reports );
			do_action( 'zeno_report_comments_add_report', $comment_id );


			// we will not flag a comment twice. the moderator is the boss here.
			$already_reported = get_comment_meta( $comment_id, $this->_plugin_prefix . '_reported', true );
			$already_moderated = get_comment_meta( $comment_id, $this->_plugin_prefix . '_moderated', true );
			if ( true == $already_reported && true == $already_moderated ) {
				// But maybe the boss wants to allow comments to be reflagged
				if ( ! apply_filters( 'zeno_report_comments_allow_moderated_to_be_reflagged', false ) ) {
					return $this->already_moderated_message;
				}
			}

			if ( $current_reports >= $threshold ) {
				do_action( 'zeno_report_comments_mark_flagged', $comment_id );
				wp_set_comment_status( $comment_id, 'hold' );
			}
		}


		/*
		 * Die() with or without screen based on JS availability
		 */
		private function cond_die( $message ) {
			if ( isset( $_REQUEST['no_js'] ) && true == (boolean) $_REQUEST['no_js'] ) {
				wp_die( $message, __( 'Zeno Report Comments Notice', 'zeno-report-comments' ), array( 'response' => 200 ) );
			} else {
				die( $message );
			}
		}


		/*
		 * Ajax callback to flag/report a comment.
		 * AJAX action: zeno_report_comments_flag_comment
		 */
		public function flag_comment() {
			if ( (int) $_REQUEST[ 'comment_id' ] != $_REQUEST[ 'comment_id' ] || empty( $_REQUEST[ 'comment_id' ] ) ) {
				$this->cond_die( $this->invalid_values_message );
			}
			$comment_id = (int) $_REQUEST[ 'comment_id' ];
			if ( $this->already_flagged( $comment_id ) ) {
				$this->cond_die( $this->already_flagged_message );
			}
			$nonce = $_REQUEST[ 'sc_nonce' ];
			// Check for Nonce.
			if ( ! wp_verify_nonce( $nonce, $this->_plugin_prefix . '_' . $this->_nonce_key ) ) {
				$this->cond_die( $this->invalid_nonce_message );
			} else {
				$this->mark_flagged( $comment_id );
				$this->cond_die( $this->thank_you_message );
			}
		}


		/*
		 * Ajax callback on admin to moderate a comment.
		 * AJAX action: zeno_report_comments_moderate_comment
		 */
		public function moderate_comment() {
			if ( function_exists('current_user_can') && ! current_user_can('moderate_comments') ) {
				echo 'error';
				die();
			}

			if ( (int) $_REQUEST[ 'comment_id' ] != $_REQUEST[ 'comment_id' ] || empty( $_REQUEST[ 'comment_id' ] ) ) {
				$this->cond_die( $this->invalid_values_message );
			}
			$comment_id = (int) $_REQUEST[ 'comment_id' ];
			$nonce = $_REQUEST[ 'sc_nonce' ];
			// Check for Nonce.
			if ( ! wp_verify_nonce( $nonce, $this->_plugin_prefix . '_' . $this->_nonce_key ) ) {
				$this->cond_die( $this->invalid_nonce_message );
			} else {
				update_comment_meta( $comment_id, $this->_plugin_prefix . '_moderated', true );
				delete_comment_meta( $comment_id, $this->_plugin_prefix . '_reported' );
				wp_set_comment_status( $comment_id, 'approve' );
				$this->cond_die( $this->moderated_message );
			}
		}


		public function print_flagging_link( $comment_id='', $result_id='', $text='' ) {
			if ( empty( $text ) ) {
				$text = __( 'Report comment', 'zeno-report-comments' );
			}
			echo $this->get_flagging_link( $comment_id='', $result_id='', $text ); // XSS set via get_flagging_link. Needs flexible HTML input.
		}


		/*
		 * Output Link to report a comment
		 */
		public function get_flagging_link( $comment_id='', $result_id='', $text='' ) {
			global $in_comment_loop;
			if ( empty( $comment_id ) && ! $in_comment_loop ) {
				return __( 'Wrong usage of print_flagging_link().', 'zeno-report-comments' );
			}
			if ( empty( $comment_id ) ) {
				$comment_id = get_comment_ID();
			} else {
				$comment_id = (int) $comment_id;
			}
			$comment = get_comment( $comment_id );
			if ( ! $comment ) {
				return __( 'This comment does not exist.', 'zeno-report-comments' );
			}
			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();
				if ( $user_id == $comment->user_id ) {
					return '<!-- author comment -->';
				}
			}
			if ( empty( $result_id ) ) {
				$result_id = 'zeno-comments-result-' . $comment_id;
			}
			$result_id = apply_filters( 'zeno_report_comments_result_id', $result_id );
			if ( empty( $text ) ) {
				$text = __('Report comment', 'zeno-report-comments' );
			}
			$text = apply_filters( 'zeno_report_comments_flagging_link_text', $text );

			// This user already flagged this comment. Don't show the link.
			if ( $this->already_flagged( $comment_id ) ) {
				return $this->already_flagged_note;
			}

			// we will not flag a comment twice. the moderator is the boss here.
			$already_reported = get_comment_meta( $comment_id, $this->_plugin_prefix . '_reported', true );
			$already_moderated = get_comment_meta( $comment_id, $this->_plugin_prefix . '_moderated', true );
			if ( (true == $already_reported && true == $already_moderated) || true == $already_moderated ) {
				// But maybe the boss wants to allow comments to be reflagged
				if ( ! apply_filters( 'zeno_report_comments_allow_moderated_to_be_reflagged', false ) ) {
					return;
				}
			}

			return apply_filters( 'zeno_report_comments_flagging_link', '
				<span id="' . $result_id . '">
					<a class="hide-if-no-js" href="#" data-zeno-comment-id="' . $comment_id . '" rel="nofollow">' . $text . '</a>
				</span>
				' );
		}


		/*
		 * Callback function to automatically hook in the report link after the comment reply link.
		 * If you want to control the placement on your own define no_autostart_safe_report_comments in your functions.php file and initialize the class
		 * with $zeno_report_comments = new Zeno_Report_Comments( $auto_init = false );
		 */
		public function add_flagging_link_to_reply_link( $comment_reply_link ) {
			if ( !preg_match_all( '#^(.*)(<a.+class=["|\']comment-(reply|login)-link["|\'][^>]+>)(.+)(</a>)(.*)$#msiU', $comment_reply_link, $matches ) ) {
				return '<!-- zeno-comments add_flagging_link not matching -->' . $comment_reply_link;
			}
			$comment_reply_link =  $matches[1][0] . $matches[2][0] . $matches[4][0] . $matches[5][0] . '<span class="zeno-comments-report-link">' . $this->get_flagging_link() . '</span>' . $matches[6][0];
			return apply_filters( 'zeno_report_comments_comment_reply_link', $comment_reply_link );
		}


		/*
		 * Callback function to automatically hook in the comment content if threading is disabled.
		 * If you want to control the placement on your own define no_autostart_safe_report_comments in your functions.php file and initialize the class
		 * with $zeno_report_comments = new Zeno_Report_Comments( $auto_init = false );
		 *
		 * @since 1.2.0
		 */
		public function add_flagging_link_to_content( $comment_content ) {
			if ( get_option('thread_comments') ) {
				return $comment_content; // threaded
			}
			if ( is_admin() ) {
				return $comment_content;
			}
			$flagging_link = $this->get_flagging_link();
			if ( $flagging_link ) {
				$comment_content .=  '<br /><span class="zeno-comments-report-link">' . $flagging_link . '</span>';
			}
			return $comment_content;
		}

		/*
		 * Callback function to add the report counter to comments screen. Remove action manage_edit-comments_columns if not desired
		 */
		public function add_comment_reported_column( $comment_columns ) {
			$comment_columns['comment_reported'] = _x('Reported', 'column name', 'zeno-report-comments');
			return $comment_columns;
		}


		/*
		 * Callback function to handle custom column. remove action manage_comments_custom_column if not desired
		 */
		public function manage_comment_reported_column( $column_name, $comment_id ) {
			switch( $column_name ) {
			case 'comment_reported':
				$reports = 0;
				$already_reported = (int) get_comment_meta( $comment_id, $this->_plugin_prefix . '_reported', true );
				if ( $already_reported > 0 ) {
					$reports = $already_reported;
				}
				$result_id = 'zeno-comments-result-' . $comment_id;
				echo '<span class="zeno-comments-report-moderate" id="' . $result_id . '">';
				echo esc_attr( $reports );
				if ( $already_reported > 0 ) {
					echo '
					<span class="row-actions">
						<a href="#" aria-label="' . __( 'Moderate and remove reports.', 'zeno-report-comments' ) . '" title="' . __( 'Moderate and remove reports.', 'zeno-report-comments' ) . '" data-zeno-comment-id="' . $comment_id . '">(' . __( 'allow and remove reports', 'zeno-report-comments' ) . ')</a>
					</span>';
				}
				echo '</span>';
				break;
			default:
				break;
			}
		}


		/*
		 * admin_notification - Alert admin via email
		 *
		 * Alert admin via email when comment has been sent into moderation.
		 *
		 * @since 1.0
		 *
		 * @param int $comment_id
		 *
		 */
		public function admin_notification( $comment_id ) {

			if ( ! $this->is_admin_notification_enabled() ) return;

			$comment = get_comment( $comment_id );

			$admin_email = get_option( 'admin_email' );
			$subject = sprintf( __( 'A comment by %s %s', 'zeno-report-comments' ), esc_html( $comment->comment_author ), esc_html__( 'has been flagged by users and sent back to moderation', 'zeno-report-comments' ) );
			$headers = sprintf( 'From: %s <%s>', esc_html( get_bloginfo( 'site' ) ), get_option( 'admin_email' ) ) . "\r\n\r\n";
			$message = __( 'Users of your site have flagged a comment and it has been sent to moderation.', 'zeno-report-comments' ) . "\r\n";
			$message .= __( 'You are welcome to view the comment yourself at your earliest convenience.', 'zeno-report-comments' ) . "\r\n\r\n";
			$message .= esc_url_raw( add_query_arg( array( 'action' => 'editcomment', 'c' => absint( $comment_id ) ), admin_url( 'comment.php' ) ) );

			wp_mail( $admin_email, $subject, $message, $headers );
		}


		/*
		 * admin_notification_each - Alert admin via email
		 *
		 * Alert admin via email when comment has been reported.
		 *
		 * @since 1.0
		 *
		 * @param int $comment_id
		 *
		 */
		public function admin_notification_each( $comment_id ) {
			if ( ! $this->is_admin_notification_each_enabled() ) return;

			$comment = get_comment( $comment_id );

			$admin_email = get_option( 'admin_email' );
			$subject = sprintf( __( 'A comment by %s %s', 'zeno-report-comments' ), esc_html( $comment->comment_author ), esc_html__( 'has been flagged by a user', 'zeno-report-comments' ) );
			$headers = sprintf( 'From: %s <%s>', esc_html( get_bloginfo( 'site' ) ), get_option( 'admin_email' ) ) . "\r\n\r\n";
			$message = __( 'A user of your site has flagged a comment.', 'zeno-report-comments' ) . "\r\n";
			$message .= __( 'You are welcome to view the comment yourself at your earliest convenience.', 'zeno-report-comments' ) . "\r\n\r\n";
			$message .= esc_url_raw( add_query_arg( array( 'action' => 'editcomment', 'c' => absint( $comment_id ) ), admin_url( 'comment.php' ) ) );
			$reporter_ip = $_SERVER['REMOTE_ADDR'];
			$message .= "\r\n\r\n" . __( 'Reporter IP address:', 'zeno-report-comments' ) . ' ' . $reporter_ip . "\r\n";

			wp_mail( $admin_email, $subject, $message, $headers );
		}


		/*
		 * Load Language files for frontend and backend.
		 */
		public function load_language() {
			load_plugin_textdomain( 'zeno-report-comments', false, plugin_basename(dirname(__FILE__)) . '/lang' );
		}


		/*
		 * Add example text to the privacy policy.
		 *
		 * @since 1.1.2
		 */
		public function add_privacy_policy_content() {
			if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
				return;
			}

			$content = '<p>' . __( 'When visitors report a comment, the comment ID will be stored in a cookie in the browser. Also, the IP address will be saved temporarily in the database together with the number of reports.', 'zeno-report-comments' ) . '</p>';

			wp_add_privacy_policy_content(
				'Zeno Report Comments',
				wp_kses_post( wpautop( $content, false ) )
			);
		}

	}
}


if ( ! defined( 'no_autostart_zeno_report_comments' ) ) {
	$zeno_report_comments = new Zeno_Report_Comments;
}
