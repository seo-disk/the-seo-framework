<?php
/**
 * @package The_SEO_Framework\Classes
 */
namespace The_SEO_Framework;

defined( 'THE_SEO_FRAMEWORK_PRESENT' ) or die;

/**
 * The SEO Framework plugin
 * Copyright (C) 2015 - 2018 Sybre Waaijer, CyberWire (https://cyberwire.nl/)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 3 as published
 * by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Class The_SEO_Framework\Profile
 *
 * Outputs Profile fields and saves metadata.
 *
 * @since 3.0.0
 */
class Profile extends Doing_It_Right {

	/**
	 * @since 3.0.0
	 * @var array|object The profile setting fields.
	 */
	public $profile_settings = [];

	/**
	 * Constructor, loads parent constructor.
	 */
	protected function __construct() {
		parent::__construct();
	}

	/**
	 * Outputs profile fields and prepares saving thereof.
	 *
	 * @since 3.0.0
	 */
	protected function init_profile_fields() {

		//= No need to load anything if the user can't even publish posts.
		if ( ! \current_user_can( 'publish_posts' ) )
			return;

		$this->profile_settings = (object) [
			'keys' => [
				'facebook_page' => 'tsf_facebook_page',
				'twitter_page'  => 'tsf_twitter_page',
			],
			'sanitation' => [
				'facebook_page' => 's_facebook_profile',
				'twitter_page'  => 's_twitter_name',
			],
		];

		\add_action( 'show_user_profile', [ $this, '_add_user_author_fields' ], 0, 1 );
		\add_action( 'edit_user_profile', [ $this, '_add_user_author_fields' ], 0, 1 );

		\add_action( 'personal_options_update', [ $this, '_update_user_settings' ], 10, 1 );
		\add_action( 'edit_user_profile_update', [ $this, '_update_user_settings' ], 10, 1 );
	}

	/**
	 * Outputs user profile fields.
	 *
	 * @since 3.0.0
	 * @access private
	 *
	 * @param WP_User $user WP_User object.
	 */
	public function _add_user_author_fields( \WP_User $user ) {

		if ( ! $user->has_cap( 'publish_posts' ) )
			return;

		$fields = [
			$this->profile_settings->keys['facebook_page'] => (object) [
				'name'        => \__( 'Facebook profile page', 'autodescription' ),
				'type'        => 'url',
				'placeholder' => \_x( 'https://www.facebook.com/YourPersonalProfile', 'Example Facebook Personal URL', 'autodescription' ),
				'value'       => $this->get_user_option( $user->ID, 'facebook_page' ),
				'class'       => '',
			],
			$this->profile_settings->keys['twitter_page'] => (object) [
				'name'        => \__( 'Twitter profile name', 'autodescription' ),
				'type'        => 'text',
				'placeholder' => \_x( '@your-personal-username', 'Twitter @username', 'autodescription' ),
				'value'       => $this->get_user_option( $user->ID, 'twitter_page' ),
				'class'       => 'ltr',
			],
		];

		$this->get_view( 'profile/author', get_defined_vars() );
	}

	/**
	 * Saves user profile fields.
	 *
	 * @since 3.0.0
	 * @securitycheck 3.0.0 OK. NOTE: Nonces and refer(r)ers have been checked prior
	 *                          to the actions bound to this method.
	 * @access private
	 *
	 * @param int $user_id The user ID.
	 */
	public function _update_user_settings( $user_id ) {

		\check_admin_referer( 'update-user_' . $user_id );
		if ( ! \current_user_can( 'edit_user', $user_id ) ) return;

		if ( empty( $_POST ) )
			return;

		$user = new \WP_User( $user_id );

		if ( ! $user->has_cap( 'publish_posts' ) )
			return;

		$success = [];
		$defaults = $this->get_default_user_data();

		foreach ( $this->profile_settings->keys as $option => $post_key ) {
			if ( isset( $_POST[ $post_key ] ) ) { // CSRF ok: profile_settings->keys are static.
				//= Sanitizes value from $_POST.
				$value = $this->{$this->profile_settings->sanitation[ $option ]}( $_POST[ $post_key ] ) // Sanitization OK.
					   ?: $defaults[ $option ];

				$success[] = (bool) $this->update_user_option( $user_id, $option, $value );
			}
		}

		//? Do something with $success?
	}
}
