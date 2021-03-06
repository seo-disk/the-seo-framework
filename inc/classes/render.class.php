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
 * Class The_SEO_Framework\Render
 *
 * Puts all data into HTML valid meta tags.
 *
 * @since 2.8.0
 */
class Render extends Admin_Init {

	/**
	 * Constructor, load parent constructor
	 */
	protected function __construct() {
		parent::__construct();
	}

	/**
	 * Returns the document title.
	 *
	 * This method serves as a callback for filter `pre_get_document_title`.
	 * Use the_seo_framework()->get_title() instead.
	 *
	 * @since 3.1.0
	 * @see $this->get_title()
	 *
	 * @param string $title The filterable title.
	 * @return string The document title
	 */
	public function get_document_title( $title = '' ) {
		if ( $this->is_feed() || $this->is_post_type_disabled() ) {
			return $title;
		}
		/**
		 * @since 3.1.0
		 * @param string $title The generated title.
		 * @param int    $id    The page or term ID.
		 */
		return \apply_filters_ref_array(
			'the_seo_framework_pre_get_document_title',
			[
				$this->get_title(),
				$this->get_the_real_ID(),
			]
		);
	}

	/**
	 * Returns the document title.
	 *
	 * This method serves as a callback for filter `wp_title`.
	 * Use the_seo_framework()->get_title() instead.
	 *
	 * @since 3.1.0
	 * @see $this->get_title()
	 *
	 * @param string $title The filterable title.
	 * @return string $title
	 */
	public function get_wp_title( $title = '', $sep = '', $seplocation = '' ) {
		if ( $this->is_feed() || $this->is_post_type_disabled() ) {
			return $title;
		}
		/**
		 * @since 3.1.0
		 * @param string $title The generated title.
		 * @param int    $id    The page or term ID.
		 */
		return \apply_filters_ref_array(
			'the_seo_framework_wp_title',
			[
				$this->get_title(),
				$this->get_the_real_ID(),
			]
		);
	}

	/**
	 * Caches current Image URL in static variable.
	 * Must be called inside the loop.
	 *
	 * @since 2.2.2
	 * @since 2.7.0 $get_id parameter has been added.
	 * @staticvar string $cache
	 *
	 * @return string The image URL.
	 */
	public function get_image_from_cache() {

		static $cache = null;

		return isset( $cache ) ? $cache : $cache = $this->get_social_image( [], true );
	}

	/**
	 * Returns the current Twitter card type.
	 *
	 * @since 2.8.2
	 * @since 3.1.0 Filter has been moved to generate_twitter_card_type()
	 * @staticvar string $cache
	 *
	 * @return string The cached Twitter card.
	 */
	public function get_current_twitter_card_type() {

		static $cache = null;

		return isset( $cache ) ? $cache : $cache = $this->generate_twitter_card_type();
	}

	/**
	 * Renders the description meta tag.
	 *
	 * @since 1.3.0
	 * @since 3.0.6 No longer uses $this->description_from_cache()
	 * @since 3.1.0 No longer checks for SEO plugin presence.
	 * @uses $this->get_description()
	 *
	 * @return string The description meta tag.
	 */
	public function the_description() {

		/**
		 * @since 2.3.0
		 * @since 2.7.0 : Added output within filter.
		 * @param string $description The generated description.
		 * @param int    $id          The page or term ID.
		 */
		$description = (string) \apply_filters_ref_array(
			'the_seo_framework_description_output',
			[
				$this->get_description(),
				$this->get_the_real_ID(),
			]
		);

		if ( $description )
			return '<meta name="description" content="' . \esc_attr( $description ) . '" />' . "\r\n";

		return '';
	}

	/**
	 * Collects and returns all canonical meta tags.
	 *
	 * The return filter is guaranteed to run, regardless of used logic.
	 *
	 * @notes:
	 * The plan is to move all related output logic into this function, to perform even
	 * faster and smarter rendering, while giving more knowledge and control to the API user.
	 * For example, we'll (eventually) feed back the logical parsing object, where API users
	 * can supersede and debug the input data, so to prevent empty output.
	 *
	 * @since 3.2.0
	 *
	 * @return string $output
	 */
	public function get_all_canonical_meta_tags() {

		$output  = '';
		$output .= $this->shortlink();
		$output .= $this->canonical();
		$output .= $this->paged_urls();

		/**
		 * @since 3.2.0
		 * @param string $output
		 * @param null   $builder
		 */
		return \apply_filters_ref_array(
			'the_seo_framework_canonical_meta_tags',
			[
				$output,
				null,
			]
		);
	}

	/**
	 * Collects and returns all open graph meta tags.
	 *
	 * The return filter is guaranteed to run, regardless of used logic.
	 *
	 * Used by:
	 *  - Facebook
	 *  - Pinterest
	 *  - Google+
	 *  -
	 *
	 * @since 3.2.0
	 * @see @notes of $this->get_all_canonical_meta_tags()
	 *
	 * @return string $output
	 */
	public function get_all_og_meta_tags() {

		$builder = new Builders\OG;

		if ( $builder->build() )
			$builder->create_metatags();

		/**
		 * @since 3.2.0
		 * @param string                         $output
		 * @param \The_SEO_Framework\Builders\OG $builder
		 */
		return \apply_filters_ref_array(
			'the_seo_framework_og_meta_tags',
			[
				$builder->__toString(),
				$builder,
			]
		);
	}

	/**
	 * Collects and returns all Facebook meta tags.
	 *
	 * The return filter is guaranteed to run, regardless of used logic.
	 *
	 * @since 3.1.0
	 * @see @notes of $this->get_all_canonical_meta_tags()
	 *
	 * @return string $output
	 */
	public function get_all_facebook_meta_tags() {

		$output = '';

		if ( $this->get_option( 'facebook_tags' ) ) {
			$output .= $this->facebook_publisher();
			$output .= $this->facebook_author();
			$output .= $this->facebook_app_id();
		} else {
			// $fail_reason = 'DISABLED_VIA_OPTIONS';
		}

		/**
		 * @since 3.2.0
		 * @param string $output
		 * @param null   $builder
		 */
		return \apply_filters_ref_array(
			'the_seo_framework_facebook_meta_tags',
			[
				$output,
				null,
			]
		);
	}

	/**
	 * Collects and returns all open graph meta tags.
	 *
	 * The return filter is guaranteed to run, regardless of used logic.
	 *
	 * @since 3.1.0
	 * @see @notes of $this->get_all_canonical_meta_tags()
	 *
	 * @return string $output
	 */
	public function get_all_twitter_meta_tags() {

		$output = '';

		if ( $this->get_option( 'twitter_tags' ) ) {

			if ( ! $this->get_twitter_title() ) {
				// $fail_reason = 'NO_TITLE';
				goto ret;
			}
			if ( ! $this->get_twitter_description() ) {
				// $fail_reason = 'NO_DESCRIPTION';
				goto ret;
			}

			$this->twitter_card();
			$this->twitter_site();
			$this->twitter_creator();
			$this->twitter_title();
			$this->twitter_description();
			$this->twitter_image();
		} else {
			// $fail_reason = 'DISABLED_VIA_OPTIONS';
		}

		ret:;

		/**
		 * @since 3.2.0
		 * @param string $output
		 * @param null   $builder
		 */
		return \apply_filters_ref_array(
			'the_seo_framework_twitter_meta_tags',
			[
				$output,
				null,
			]
		);
	}

	/**
	 * Collects and returns all open graph meta tags.
	 *
	 * The return filter is guaranteed to run, regardless of used logic.
	 *
	 * @since 3.1.0
	 * @see @notes of $this->get_all_canonical_meta_tags()
	 *
	 * @return array {
	 *     @param string 'output'
	 *     ... // more to come.
	 * }
	 */
	public function get_all_verification_meta_tags() {

		$output = '';

		$output .= $this->google_site_output();
		$output .= $this->bing_site_output();
		$output .= $this->yandex_site_output();
		$output .= $this->pint_site_output();

		/**
		 * @since 3.2.0
		 * @param string $output
		 * @param null   $builder
		 */
		return \apply_filters_ref_array(
			'the_seo_framework_verification_meta_tags',
			[
				$output,
				null,
			]
		);
	}

	/**
	 * Collects and returns all open graph meta tags.
	 *
	 * The return filter is guaranteed to run, regardless of used logic.
	 *
	 * @since 3.1.0
	 * @see @notes of $this->get_all_canonical_meta_tags()
	 *
	 * @return array {
	 *     @param string 'output'
	 *     ... // more to come.
	 * }
	 */
	public function get_all_structured_data_scripts() {
		return $this->ld_json();
	}

	/**
	 * Renders WooCommerce Product Gallery OG images.
	 *
	 * @since 2.6.0
	 * @since 2.7.0 : Added image dimensions if found.
	 * @since 2.8.0 : Checks for featured ID internally, rather than using a far-off cache.
	 * @TODO move this to wc compat file.
	 *
	 * @return string The rendered OG Image.
	 */
	public function render_woocommerce_product_og_image() {

		$output = '';

		if ( $this->is_wc_product() ) {

			$images = $this->get_image_from_woocommerce_gallery();

			if ( $images && is_array( $images ) ) {

				$post_id = $this->get_the_real_ID();
				$post_manual_og = $this->get_custom_field( '_social_image_id', $post_id );
				$featured_id = $post_manual_og ? (int) $post_manual_og : (int) \get_post_thumbnail_id( $post_id );

				foreach ( $images as $id ) {

					if ( $id === $featured_id )
						continue;

					//* Parse 4096px url.
					$img = $this->parse_og_image( $id, [], true );

					if ( $img ) {
						$output .= '<meta property="og:image" content="' . \esc_attr( $img ) . '" />' . "\r\n";

						if ( ! empty( $this->image_dimensions[ $id ]['width'] ) && ! empty( $this->image_dimensions[ $id ]['height'] ) ) {
							$output .= '<meta property="og:image:width" content="' . \esc_attr( $this->image_dimensions[ $id ]['width'] ) . '" />' . "\r\n";
							$output .= '<meta property="og:image:height" content="' . \esc_attr( $this->image_dimensions[ $id ]['height'] ) . '" />' . "\r\n";
						}
					}
				}
			}
		}

		return $output;
	}

	/**
	 * Renders the Twitter Card type meta tag.
	 *
	 * @since 2.2.2
	 *
	 * @return string The Twitter Card meta tag.
	 */
	public function twitter_card() {

		if ( ! $this->use_twitter_tags() )
			return '';

		$card = $this->get_current_twitter_card_type();

		if ( $card )
			return '<meta name="twitter:card" content="' . \esc_attr( $card ) . '" />' . "\r\n";

		return '';
	}

	/**
	 * Renders the Twitter Site meta tag.
	 *
	 * @since 2.2.2
	 *
	 * @return string The Twitter Site meta tag.
	 */
	public function twitter_site() {

		if ( ! $this->use_twitter_tags() )
			return '';

		/**
		 * @since 2.3.0
		 * @since 2.7.0 Added output within filter.
		 * @param string $site The Twitter site owner tag.
		 * @param int    $id   The current page or term ID.
		 */
		$site = (string) \apply_filters_ref_array(
			'the_seo_framework_twittersite_output',
			[
				$this->get_option( 'twitter_site' ),
				$this->get_the_real_ID(),
			]
		);

		if ( $site )
			return '<meta name="twitter:site" content="' . \esc_attr( $site ) . '" />' . "\r\n";

		return '';
	}

	/**
	 * Renders The Twitter Creator meta tag.
	 *
	 * @since 2.2.2
	 * @since 2.9.3 No longer has a fallback to twitter:site:id
	 *              @link https://dev.twitter.com/cards/getting-started
	 * @since 3.0.0 Now uses author meta data.
	 *
	 * @return string The Twitter Creator or Twitter Site ID meta tag.
	 */
	public function twitter_creator() {

		if ( ! $this->use_twitter_tags() )
			return '';

		/**
		 * @since 2.3.0
		 * @since 2.7.0 Added output within filter.
		 * @param string $twitter_page The Twitter page creator.
		 * @param int    $id           The current page or term ID.
		 */
		$twitter_page = (string) \apply_filters_ref_array(
			'the_seo_framework_twittercreator_output',
			[
				$this->get_current_author_option( 'twitter_page' ) ?: $this->get_option( 'twitter_creator' ),
				$this->get_the_real_ID(),
			]
		);

		if ( $twitter_page )
			return '<meta name="twitter:creator" content="' . \esc_attr( $twitter_page ) . '" />' . "\r\n";

		return '';
	}

	/**
	 * Renders Twitter Title meta tag.
	 *
	 * @since 2.2.2
	 * @since 3.0.4 No longer uses $this->title_from_cache()
	 * @uses $this->get_twitter_title()
	 *
	 * @return string The Twitter Title meta tag.
	 */
	public function twitter_title() {

		if ( ! $this->use_twitter_tags() )
			return '';

		/**
		 * @since 2.3.0
		 * @since 2.7.0 Added output within filter.
		 * @param string $title The generated Twitter title.
		 * @param int    $id    The current page or term ID.
		 */
		$title = (string) \apply_filters_ref_array(
			'the_seo_framework_twittertitle_output',
			[
				$this->get_twitter_title(),
				$this->get_the_real_ID(),
			]
		);

		if ( $title )
			return '<meta name="twitter:title" content="' . \esc_attr( $title ) . '" />' . "\r\n";

		return '';
	}

	/**
	 * Renders Twitter Description meta tag.
	 *
	 * @since 2.2.2
	 * @since 3.0.4 No longer uses $this->description_from_cache()
	 * @uses $this->get_twitter_description()
	 *
	 * @return string The Twitter Descritpion meta tag.
	 */
	public function twitter_description() {

		if ( ! $this->use_twitter_tags() )
			return '';

		/**
		 * @since 2.3.0
		 * @since 2.7.0 Added output within filter.
		 * @param string $description The generated Twitter description.
		 * @param int    $id          The current page or term ID.
		 */
		$description = (string) \apply_filters_ref_array(
			'the_seo_framework_twitterdescription_output',
			[
				$this->get_twitter_description(),
				$this->get_the_real_ID(),
			]
		);

		if ( $description )
			return '<meta name="twitter:description" content="' . \esc_attr( $description ) . '" />' . "\r\n";

		return '';
	}

	/**
	 * Renders Twitter Image meta tag.
	 *
	 * @since 2.2.2
	 *
	 * @return string The Twitter Image meta tag.
	 */
	public function twitter_image() {

		if ( ! $this->use_twitter_tags() )
			return '';

		$id = $this->get_the_real_ID();

		/**
		 * @since 2.3.0
		 * @since 2.7.0 Added output within filter.
		 * @param string $image The generated Twitter image URL.
		 * @param int    $id    The current page or term ID.
		 */
		$image = (string) \apply_filters_ref_array(
			'the_seo_framework_twitterimage_output',
			[
				$this->get_image_from_cache(),
				$id,
			]
		);

		$output = '';

		if ( $image ) {
			$output = '<meta name="twitter:image" content="' . \esc_attr( $image ) . '" />' . "\r\n";

			if ( ! empty( $this->image_dimensions[ $id ]['width'] ) && ! empty( $this->image_dimensions[ $id ]['height'] ) ) {
				$output .= '<meta name="twitter:image:width" content="' . \esc_attr( $this->image_dimensions[ $id ]['width'] ) . '" />' . "\r\n";
				$output .= '<meta name="twitter:image:height" content="' . \esc_attr( $this->image_dimensions[ $id ]['height'] ) . '" />' . "\r\n";
			}
		}

		return $output;
	}

	/**
	 * Renders Facebook Author meta tag.
	 *
	 * @since 2.2.2
	 * @since 2.8.0 Returns empty on og:type 'website' or 'product'
	 * @since 3.0.0 Fetches Author meta data.
	 *
	 * @return string The Facebook Author meta tag.
	 */
	public function facebook_author() {

		if ( ! $this->use_facebook_tags() )
			return '';

		if ( 'article' !== $this->get_og_type() )
			return '';

		/**
		 * @since 2.3.0
		 * @since 2.7.0 Added output within filter.
		 * @param string $facebook_page The generated Facebook author page URL.
		 * @param int    $id            The current page or term ID.
		 */
		$facebook_page = (string) \apply_filters_ref_array(
			'the_seo_framework_facebookauthor_output',
			[
				$this->get_current_author_option( 'facebook_page' ) ?: $this->get_option( 'facebook_author' ),
				$this->get_the_real_ID(),
			]
		);

		if ( $facebook_page )
			return '<meta property="article:author" content="' . \esc_attr( \esc_url_raw( $facebook_page, [ 'http', 'https' ] ) ) . '" />' . "\r\n";

		return '';
	}

	/**
	 * Renders Facebook Publisher meta tag.
	 *
	 * @since 2.2.2
	 * @since 3.0.0 No longer outputs tag when "og:type" isn't 'article'.
	 *
	 * @return string The Facebook Publisher meta tag.
	 */
	public function facebook_publisher() {

		if ( ! $this->use_facebook_tags() )
			return '';

		if ( 'article' !== $this->get_og_type() )
			return '';

		/**
		 * @since 2.3.0
		 * @since 2.7.0 Added output within filter.
		 * @param string $publisher The Facebook publisher page URL.
		 * @param int    $id        The current page or term ID.
		 */
		$publisher = (string) \apply_filters_ref_array(
			'the_seo_framework_facebookpublisher_output',
			[
				$this->get_option( 'facebook_publisher' ),
				$this->get_the_real_ID(),
			]
		);

		if ( $publisher )
			return '<meta property="article:publisher" content="' . \esc_attr( \esc_url_raw( $publisher, [ 'http', 'https' ] ) ) . '" />' . "\r\n";

		return '';
	}

	/**
	 * Renders Facebook App ID meta tag.
	 *
	 * @since 2.2.2
	 *
	 * @return string The Facebook App ID meta tag.
	 */
	public function facebook_app_id() {

		if ( ! $this->use_facebook_tags() )
			return '';

		/**
		 * @since 2.3.0
		 * @since 2.7.0 Added output within filter.
		 * @param string $app_id The Facebook app ID.
		 * @param int    $id     The current page or term ID.
		 */
		$app_id = (string) \apply_filters_ref_array(
			'the_seo_framework_facebookappid_output',
			[
				$this->get_option( 'facebook_appid' ),
				$this->get_the_real_ID(),
			]
		);

		if ( $app_id )
			return '<meta property="fb:app_id" content="' . \esc_attr( $app_id ) . '" />' . "\r\n";

		return '';
	}

	/**
	 * Renders Article Publishing Time meta tag.
	 *
	 * @since 2.2.2
	 * @since 2.8.0 Returns empty on product pages.
	 * @since 3.0.0: 1. Now checks for 0000 timestamps.
	 *               2. Now uses timestamp formats.
	 *               3. Now uses GMT time.
	 *
	 * @return string The Article Publishing Time meta tag.
	 */
	public function article_published_time() {

		if ( ! $this->output_published_time() )
			return '';

		$id = $this->get_the_real_ID();

		$post = \get_post( $id );
		$post_date_gmt = $post->post_date_gmt;

		if ( '0000-00-00 00:00:00' === $post_date_gmt )
			return '';

		/**
		 * @since 2.3.0
		 * @since 2.7.0 Added output within filter.
		 * @param string $time The article published time.
		 * @param int    $id   The current page or term ID.
		 */
		$time = (string) \apply_filters_ref_array(
			'the_seo_framework_publishedtime_output',
			[
				$this->gmt2date( $this->get_timestamp_format(), $post_date_gmt ),
				$id,
			]
		);

		if ( $time )
			return '<meta property="article:published_time" content="' . \esc_attr( $time ) . '" />' . "\r\n";

		return '';
	}

	/**
	 * Renders Article Modified Time meta tag.
	 * Also renders the Open Graph Updated Time meta tag if Open Graph tags are enabled.
	 *
	 * @since 2.2.2
	 * @since 2.7.0 Listens to $this->get_the_real_ID() instead of WordPress Core ID determination.
	 * @since 2.8.0 Returns empty on product pages.
	 * @since 3.0.0: 1. Now checks for 0000 timestamps.
	 *               2. Now uses timestamp formats.
	 *
	 * @return string The Article Modified Time meta tag, and optionally the Open Graph Updated Time.
	 */
	public function article_modified_time() {

		if ( ! $this->output_modified_time() )
			return '';

		$id = $this->get_the_real_ID();

		$post = \get_post( $id );
		$post_modified_gmt = $post->post_modified_gmt;

		if ( '0000-00-00 00:00:00' === $post_modified_gmt )
			return '';

		/**
		 * @since 2.3.0
		 * @since 2.7.0 Added output within filter.
		 * @param string $time The article modified time.
		 * @param int    $id   The current page or term ID.
		 */
		$time = (string) \apply_filters_ref_array(
			'the_seo_framework_modifiedtime_output',
			[
				$this->gmt2date( $this->get_timestamp_format(), $post_modified_gmt ),
				$id,
			]
		);

		if ( $time ) {
			$output = '<meta property="article:modified_time" content="' . \esc_attr( $time ) . '" />' . "\r\n";

			if ( $this->use_og_tags() )
				$output .= '<meta property="og:updated_time" content="' . \esc_attr( $time ) . '" />' . "\r\n";

			return $output;
		}

		return '';
	}

	/**
	 * Renders Canonical URL meta tag.
	 *
	 * @since 2.0.6
	 * @since 3.0.0 Deleted filter `the_seo_framework_output_canonical`.
	 * @uses $this->get_current_canonical_url()
	 *
	 * @return string The Canonical URL meta tag.
	 */
	public function canonical() {

		/**
		 * @since 2.6.5
		 * @param string $url The canonical URL. Must be escaped.
		 * @param int    $id  The current page or term ID.
		 */
		$url = (string) \apply_filters_ref_array(
			'the_seo_framework_rel_canonical_output',
			[
				$this->get_current_canonical_url(),
				$this->get_the_real_ID(),
			]
		);

		/**
		 * @since 2.7.0 Listens to the second filter.
		 */
		if ( $url )
			return '<link rel="canonical" href="' . $url . '" />' . PHP_EOL;

		return '';
	}

	/**
	 * Renders LD+JSON Schema.org scripts.
	 *
	 * @uses $this->render_ld_json_scripts()
	 *
	 * @since 1.2.0
	 * @since 3.1.0 No longer returns early on search, 404 or preview.
	 * @return string The LD+json Schema.org scripts.
	 */
	public function ld_json() {
		/**
		 * @since 2.6.0
		 * @param string $json The JSON output. Must be escaped.
		 * @param int    $id   The current page or term ID.
		 */
		$json = (string) \apply_filters_ref_array(
			'the_seo_framework_ldjson_scripts',
			[
				$this->render_ld_json_scripts(),
				$this->get_the_real_ID(),
			]
		);

		return $json;
	}

	/**
	 * Renders Google Site Verification Code meta tag.
	 *
	 * @since 2.2.4
	 *
	 * @return string The Google Site Verification code meta tag.
	 */
	public function google_site_output() {

		/**
		 * @since 2.6.0
		 * @param string $code The Google verification code.
		 * @param int    $id   The current post or term ID.
		 */
		$code = (string) \apply_filters_ref_array(
			'the_seo_framework_googlesite_output',
			[
				$this->get_option( 'google_verification' ),
				$this->get_the_real_ID(),
			]
		);

		if ( $code )
			return '<meta name="google-site-verification" content="' . \esc_attr( $code ) . '" />' . PHP_EOL;

		return '';
	}

	/**
	 * Renders Bing Site Verification Code meta tag.
	 *
	 * @since 2.2.4
	 *
	 * @return string The Bing Site Verification Code meta tag.
	 */
	public function bing_site_output() {

		/**
		 * @since 2.6.0
		 * @param string $code The Bing verification code.
		 * @param int    $id   The current post or term ID.
		 */
		$code = (string) \apply_filters_ref_array(
			'the_seo_framework_bingsite_output',
			[
				$this->get_option( 'bing_verification' ),
				$this->get_the_real_ID(),
			]
		);

		if ( $code )
			return '<meta name="msvalidate.01" content="' . \esc_attr( $code ) . '" />' . PHP_EOL;

		return '';
	}

	/**
	 * Renders Yandex Site Verification code meta tag.
	 *
	 * @since 2.6.0
	 *
	 * @return string The Yandex Site Verification code meta tag.
	 */
	public function yandex_site_output() {

		/**
		 * @since 2.6.0
		 * @param string $code The Yandex verification code.
		 * @param int    $id   The current post or term ID.
		 */
		$code = (string) \apply_filters_ref_array(
			'the_seo_framework_yandexsite_output',
			[
				$this->get_option( 'yandex_verification' ),
				$this->get_the_real_ID(),
			]
		);

		if ( $code )
			return '<meta name="yandex-verification" content="' . \esc_attr( $code ) . '" />' . PHP_EOL;

		return '';
	}

	/**
	 * Renders Pinterest Site Verification code meta tag.
	 *
	 * @since 2.5.2
	 *
	 * @return string The Pinterest Site Verification code meta tag.
	 */
	public function pint_site_output() {

		/**
		 * @since 2.6.0
		 * @param string $code The Pinterest verification code.
		 * @param int    $id   The current post or term ID.
		 */
		$code = (string) \apply_filters_ref_array(
			'the_seo_framework_pintsite_output',
			[
				$this->get_option( 'pint_verification' ),
				$this->get_the_real_ID(),
			]
		);

		if ( $code )
			return '<meta name="p:domain_verify" content="' . \esc_attr( $code ) . '" />' . PHP_EOL;

		return '';
	}

	/**
	 * Renders Robots meta tags.
	 * Returns early if blog isn't public. WordPress Core will then output the meta tags.
	 *
	 * @since 2.0.0
	 *
	 * @return string The Robots meta tags.
	 */
	public function robots() {

		//* Don't do anything if the blog isn't set to public.
		if ( false === $this->is_blog_public() )
			return '';

		/**
		 * @since 2.6.0
		 * @param array $meta The robots meta.
		 * @param int   $id   The current post or term ID.
		 */
		$meta = (array) \apply_filters_ref_array(
			'the_seo_framework_robots_meta',
			[
				$this->robots_meta(),
				$this->get_the_real_ID(),
			]
		);

		if ( empty( $meta ) )
			return '';

		return sprintf( '<meta name="robots" content="%s" />' . PHP_EOL, implode( ',', $meta ) );
	}

	/**
	 * Renders Shortlink meta tag
	 *
	 * @since 2.2.2
	 * @since 2.9.3 : Now work when home page is a blog.
	 * @uses $this->get_shortlink()
	 *
	 * @return string The Shortlink meta tag.
	 */
	public function shortlink() {

		/**
		 * @since 2.6.0
		 * @param string $url The generated shortlink URL.
		 * @param int    $id  The current post or term ID.
		 */
		$url = (string) \apply_filters_ref_array(
			'the_seo_framework_shortlink_output',
			[
				$this->get_shortlink(),
				$this->get_the_real_ID(),
			]
		);

		if ( $url )
			return '<link rel="shortlink" href="' . $url . '" />' . PHP_EOL;

		return '';
	}

	/**
	 * Renders Prev/Next Paged URL meta tags.
	 *
	 * @since 2.2.2
	 * @uses $this->get_paged_url()
	 *
	 * @return string The Prev/Next Paged URL meta tags.
	 */
	public function paged_urls() {

		$id = $this->get_the_real_ID();

		/**
		 * @since 2.6.0
		 * @param string $next The next-page URL.
		 * @param int    $id   The current post or term ID.
		 */
		$next = (string) \apply_filters_ref_array(
			'the_seo_framework_paged_url_output_next',
			[
				$this->get_paged_url( 'next' ),
				$id,
			]
		);

		/**
		 * @since 2.6.0
		 * @param string $next The previous-page URL.
		 * @param int    $id   The current post or term ID.
		 */
		$prev = (string) \apply_filters_ref_array(
			'the_seo_framework_paged_url_output_prev',
			[
				$this->get_paged_url( 'prev' ),
				$id,
			]
		);

		$output = '';

		if ( $prev )
			$output .= '<link rel="prev" href="' . $prev . '" />' . PHP_EOL;

		if ( $next )
			$output .= '<link rel="next" href="' . $next . '" />' . PHP_EOL;

		return $output;
	}

	/**
	 * Returns the plugin hidden HTML indicators.
	 *
	 * @since 2.9.2
	 *
	 * @param string $where Determines the position of the indicator.
	 *               Accepts 'before' for before, anything else for after.
	 * @param int $timing Determines when the output started.
	 * @return string The SEO Framework's HTML plugin indicator.
	 */
	public function get_plugin_indicator( $where = 'before', $timing = 0 ) {

		static $run, $_cache;

		if ( ! isset( $run ) ) {
			/**
			 * @since 2.0.0
			 * @param bool $run Whether to run and show the plugin indicator.
			 */
			$run = (bool) \apply_filters( 'the_seo_framework_indicator', true );
		}

		if ( false === $run )
			return '';

		if ( ! isset( $_cache ) ) {
			$_cache = [];
			/**
			 * @since 2.4.0
			 * @param bool $sybre Whether to show the author name in the indicator.
			 */
			$sybre = (bool) \apply_filters( 'sybre_waaijer_<3', true );

			// Plugin name can't be translated. Yay.
			$tsf = 'The SEO Framework';

			/**
			 * @since 2.4.0
			 * @param bool $show_timer Whether to show the generation time in the indicator.
			 */
			$_cache['show_timer'] = (bool) \apply_filters( 'the_seo_framework_indicator_timing', true );

			/* translators: %s = 'The SEO Framework' */
			$_cache['start'] = sprintf( \esc_html__( 'Start %s', 'autodescription' ), $tsf );
			/* translators: %s = 'The SEO Framework' */
			$_cache['end'] = sprintf( \esc_html__( 'End %s', 'autodescription' ), $tsf );
			$_cache['author'] = $sybre ? ' ' . \esc_html__( 'by Sybre Waaijer', 'autodescription' ) : '';
		}

		if ( 'before' === $where ) {
			$output = $_cache['start'] . $_cache['author'];
		} else {
			if ( $_cache['show_timer'] && $timing ) {
				$timer = ' | ' . number_format( microtime( true ) - $timing, 5 ) . 's';
			} else {
				$timer = '';
			}
			$output = $_cache['end'] . $_cache['author'] . $timer;
		}

		return sprintf( '<!-- %s -->', $output ) . PHP_EOL;
	}

	/**
	 * Determines if modified time should be used in the current query.
	 *
	 * @since 3.0.0
	 * @staticvar bool $cache
	 *
	 * @return bool
	 */
	public function output_modified_time() {

		static $cache;

		if ( isset( $cache ) )
			return $cache;

		if ( 'article' !== $this->get_og_type() )
			return $cache = false;

		return $cache = (bool) $this->get_option( 'post_modify_time' );
	}

	/**
	 * Determines if published time should be used in the current query.
	 *
	 * @since 3.0.0
	 * @staticvar bool $cache
	 *
	 * @return bool
	 */
	public function output_published_time() {

		static $cache;

		if ( isset( $cache ) )
			return $cache;

		if ( 'article' !== $this->get_og_type() )
			return $cache = false;

		return $cache = (bool) $this->get_option( 'post_publish_time' );
	}

	/**
	 * Determines whether we can use Open Graph tags on the front-end.
	 *
	 * @since 2.6.0
	 * @since 3.1.0 Removed cache.
	 * @TODO add facebook validation.
	 * @staticvar bool $cache
	 *
	 * @return bool
	 */
	public function use_og_tags() {
		return (bool) $this->get_option( 'og_tags' );
	}

	/**
	 * Determines whether we can use Facebook tags on the front-end.
	 *
	 * @since 2.6.0
	 * @since 3.1.0 Removed cache.
	 * @staticvar bool $cache
	 *
	 * @return bool
	 */
	public function use_facebook_tags() {
		return (bool) $this->get_option( 'facebook_tags' );
	}

	/**
	 * Determines whether we can use Twitter tags on the front-end.
	 *
	 * @since 2.6.0
	 * @since 2.8.2 : Now also considers Twitter card type output.
	 * @staticvar bool $cache
	 *
	 * @return bool
	 */
	public function use_twitter_tags() {
		static $cache = null;
		return isset( $cache )
			? $cache
			: $cache = $this->get_option( 'twitter_tags' ) && $this->get_current_twitter_card_type();
	}
}
