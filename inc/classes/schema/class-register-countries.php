<?php
/**
 * Register_Countries class.
 *
 * @package headless-cms
 */

namespace Headless_CMS\Features\Inc\Schema;

use Headless_CMS\Features\Inc\Traits\Singleton;

/**
 * Class Register_Countries
 */
class Register_Countries {

	use Singleton;

	/**
	 * Construct method.
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * To setup action/filter.
	 *
	 * @return void
	 */
	protected function setup_hooks() {

		/**
		 * Action
		 */

		// Register Countries Field.
		add_action( 'graphql_register_types', [ $this, 'register_countries_fields' ] );

	}

	/**
	 * Register field.
	 */
	public function register_countries_fields() {

		register_graphql_object_type( 'WooCountries', [
			'description' => __( 'Countries Type', 'headless-cms' ),
			'fields'      => [
				'billingCountries'  => [ 'type' => 'String' ],
				'shippingCountries' => [ 'type' => 'String' ],
			],
		] );

		register_graphql_field(
			'RootQuery',
			'wooCountries',
			[
				'description' => __( 'Countries', 'headless-cms' ),
				'type'        => 'WooCountries',
				'resolve'     => function () {

					// All countries with states for billing.
					$all_countries                 = class_exists( 'WooCommerce' ) ? WC()->countries : [];
					$all_countries                 = ! empty( $all_countries->countries ) ? $all_countries->countries : [];
					$billing_countries_with_states = $this->get_countries_having_states( $all_countries );

					// All countries with states for shipping.
					$shipping_countries = class_exists( 'WooCommerce' ) ? WC()->countries->get_shipping_countries() : [];;
					$shipping_countries_with_states = $this->get_countries_having_states( $shipping_countries );

					/**
					 * Here you need to return data that matches the shape of the "WooCountries" type. You could get
					 * the data from the WP Database, an external API, or static values.
					 * For example in this case we are getting it from WordPress database.
					 */
					return [
						'billingCountries'  => wp_json_encode( $billing_countries_with_states ),
						'shippingCountries' => wp_json_encode( $shipping_countries_with_states ),
					];

				},
			]
		);
	}

	/**
	 * Filters countries that have states.
	 *
	 * Excludes the one's that don't have states.
	 */
	public function get_countries_having_states( $all_countries ) {

		$countries_with_states = [];

		if ( ! class_exists( 'WooCommerce' ) || empty( $all_countries ) || !is_array($all_countries) ) {
			return $countries_with_states;
		}

		$all_countries_with_states = WC()->countries->get_allowed_countries();

		if ( empty( $all_countries_with_states ) && !is_array( $all_countries_with_states ) ) {
			return $countries_with_states;
		}

		foreach ( $all_countries_with_states as $country_code => $states ) {
			if ( ! empty( $states ) ) {
				$countries_with_states[$country_code] = $all_countries[$country_code];
			}
		}

		return $countries_with_states;

	}

}