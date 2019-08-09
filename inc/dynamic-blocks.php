<?php

class Caxton_Boilerplate_Dynamic_Blocks {
	/** @var self Instance */
	private static $_instance;

	/**
	 * Returns instance of current calss
	 * @return self Instance
	 */
	public static function instance() {
		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct() {
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );

		add_action( 'init',	array( $this, 'register_blocks' ) );

	}

	public function rest_api_init() {
		register_rest_route( 'sm-popular-wp/v1', '/plugins', array(
			'methods'  => 'GET',
			'callback' => [ $this, 'rest_handler_plugins'  ],
		) );
	}

	public function register_blocks() {
		register_block_type(
			'sm-popular-wp/plugins',
			[ 'render_callback' => [ $this, 'render_plugins' ] ]
		);
	}

	public function rest_handler_plugins() {
		//Make sure you convert all spaces ` ` to underscores when using/passing props to block renderer
		$args = $_GET;
		unset( $args['_locale'] );
		return $this->render_plugins( $args );
	}

	public function render_plugins( $args ) {

		$resp = wp_remote_request( 'https://api.wordpress.org/plugins/info/1.2/?action=query_plugins&browse=popular' );

		ob_start();

		if ( is_wp_error( $resp ) ) {
			echo
				'<b>Error Occurred: ' . $resp->get_error_code() . '</b>' .
				$resp->get_error_message();
		} else {
			$response = json_decode( $resp['body'] );

			if ( $response->plugins ) {

				$layout_classes = 'flex items-center mv3 pa2 ba';

				foreach ( $response->plugins as $plugin ) {
					$icon = (array) $plugin->icons;

					$icon = array_shift( $icon );
					?>
					<div class="shramee-plugin shramee-plugin-<?php echo $plugin->slug ?> <?php echo $layout_classes ?>">
						<img width="128" height="128" src="<?php echo $icon ?>">
						<div class="shramee-plugin-meta mh2">
							<h4>
								<a href="https://wordpress.org/plugins/<?php echo $plugin->slug ?>">
									<?php echo $plugin->name ?>
								</a>
							</h4>
							<p><?php echo $plugin->short_description ?></p>
						</div>
						<div class="shramee-plugin-stats ml-auto">
							<div class="shramee-plugin-rating">
								<?php echo $this->percentage_to_circle( $plugin->rating ) ?>
								<div class="plugin-rating-percentage">
									<?php echo $plugin->rating ?>%
								</div>
								<?php //echo $plugin->num_ratings ?>
							</div>
						</div>
					</div>
					<?php
				}
			}
		}

		return ob_get_clean();
	}

	public function percentage_to_circle( $percentage, $args = 'currentColor' ) {

		$defaults = [
			'track_width'  => '2',
			'track_color'  => 'rgba(0,0,0,0.1)',
			'active_color' => 'currentColor',
		];

		if ( is_string( $args ) ) {
			$defaults['active_color'] = $args;
			$args = $defaults;
		} else {
			$args = wp_parse_args( $defaults, $defaults );
		}

		$r = 15.9154; // 100/2PI
		$center = $r + $args['track_width'] / 2;

		$width = 2 * $center;


		$circle_attrs = "cx=$center cy=$center r='{$r}' stroke-width='{$args['track_width']}' " .
										"style='transform-origin:50% 50%;transform:rotate(-90deg);' fill='none'";

		return
			"<svg viewBox='0 0 $width $width'>" .
			"<circle $circle_attrs stroke='{$args['track_color']}' />" .
			"<circle $circle_attrs stroke='{$args['active_color']}' stroke-dasharray='$percentage 100'/>" .
			'</svg>';
	}
}

Caxton_Boilerplate_Dynamic_Blocks::instance();