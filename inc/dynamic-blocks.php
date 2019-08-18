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

	public function popular_plugins_today( $limit = 50 ) {
		$date = date( 'Y-m-d', strtotime( '1 day ago' ) );

		$return_plugins = get_option( "popular_plugins_$date" );

		if ( ! $return_plugins ) {

			$all_plugins = $this->popular_plugins_all_time( $limit * 2 );
			$plugin_dls = [];

			foreach ( $all_plugins as $slug => $plugin ) {
				$downlaods = json_decode( wp_remote_retrieve_body(
					wp_remote_request( "http://api.wordpress.org/stats/plugin/1.0/downloads.php?slug=$slug&limit=2" )
				), 'array' );
				if ( ! empty( $downlaods[ $date ] ) ) {
					$plugin_dls[ $slug ] = $downlaods[ $date ];
				}
			}

			arsort( $plugin_dls );
			$top_downloads = array_slice( $plugin_dls, 0, 50 );

			$return_plugins = [];
			foreach ( $top_downloads as $slug => $dls ) {
				$return_plugins[ $slug ] = $all_plugins[ $slug ];
				$return_plugins[ $slug ]['downloads_today'] = $dls;
			}

			update_option( "popular_plugins_$date", $return_plugins );
		}

		return $return_plugins;
	}

	public function popular_plugins_all_time( $top = 120, $force = false ) {
		$plugins = get_option( "shramee_popular_plugins-$top" );

		if ( ! $plugins || $force ) {
			$resp = wp_remote_retrieve_body(
				wp_remote_request( "https://api.wordpress.org/plugins/info/1.2/?action=query_plugins&request[per_page]=$top" )
			);

			if ( $resp ) {
				$response = json_decode( $resp, 'array' );
				$plugins = [];

				foreach ( $response['plugins'] as $plugin ) {
					$plugins[ $plugin['slug'] ] = $plugin;
				}
				update_option( "shramee_popular_plugins-$top", $plugins, 'no' );
			}
		}

		return $plugins;
	}

	public function render_plugins( $args ) {

		$plugins = $this->popular_plugins_today();

		ob_start();

		$layout_classes = 'flex flex-wrap flex-nowrap-ns items-center mv3 pa2 ba';

		foreach ( $plugins as $plugin ) {
			$icon = (array) $plugin['icons'];

			$icon = array_shift( $icon );
			?>
			<div class="shramee-plugin shramee-plugin-<?php echo $plugin['slug'] ?> <?php echo $layout_classes ?>">

				<div class="shramee-plugin-content flex-ns items-center justify-center">
					<img width="128" height="128" src="<?php echo $icon ?>" class="fl fn-ns mr3">
					<div class="shramee-plugin-meta">
						<h4 class="cn">
							<a href="https://wordpress.org/plugins/<?php echo $plugin['slug'] ?>">
								<?php echo $plugin['name'] ?>
							</a>
						</h4>
						<p><?php echo $plugin['short_description'] ?></p>
					</div>
				</div>
				<div class="shramee-plugin-stats ml-auto flex items-center flex-row flex-column-m justify-center w-100 w-auto-ns">
					<div class="shramee-plugin-downloads ma3">
						<?php echo $plugin['downloads_today'] ?>
						<small>Downloads</small>
					</div>
					<div class="shramee-plugin-rating">
						<?php echo $this->percentage_to_circle( $plugin['rating'] ) ?>
						<div class="plugin-rating-percentage">
							<small>Rating</small>
							<?php echo $plugin['rating'] ?>%
						</div>
						<?php //echo $plugin['num_ratings'] ?>
					</div>
				</div>
			</div>
			<?php
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