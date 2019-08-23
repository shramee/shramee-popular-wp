<?php

class Top_50_WP_Dynamic_Blocks {
	/** @var self Instance */
	private static $_instance;

	protected function __construct() {
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );

		add_action( 'init', array( $this, 'register_blocks' ) );

	}

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

	public function rest_api_init() {
		register_rest_route( 'sm-popular-wp/v1', '/plugins', array(
			'methods'  => 'GET',
			'callback' => [ $this, 'rest_handler_plugins' ],
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

		$args = wp_parse_args( $args, [
			'display' => 'downloads_per_day',
		] );

		$method = "qry_by_$args[display]";

		if ( ! method_exists( $this, $method ) ) {
			$method = 'qry_by_downloads_per_day';
		}

		$display_options = [
			'downloads_per_day' => [
				'label'   => 'Downloads yesterday',
				'field'   => 'downloads_per_day',
				'data_cb' => 'qry_by_downloads_per_day'
			],
			'active_installs'   => [
				'label'   => 'Installs',
				'field'   => 'active_installs',
				'data_cb' => 'qry_by_active_installs'
			],
		];

		if ( isset( $display_options[ $args['display'] ] ) ) {
			$display_params = $display_options[ $args['display'] ];
		} else {
			$display_params = $display_options['downloads_per_day'];
		}

		$plugins = $this->$method();

		ob_start();

		$layout_classes = 'flex flex-wrap flex-nowrap-ns items-center mv3 pa2 ba';

		$serial_number = 1;
		?>
		<div class="shramee-popular-plugins">
			<?php
			foreach ( $plugins as $plugin ) {
				$icon = (array) $plugin['icons'];

				$icon = array_shift( $icon );
				?>
				<div class="shramee-plugin shramee-plugin-<?php echo $plugin['slug'] ?> <?php echo $layout_classes ?>">

					<div class="shramee-plugin-content flex-ns items-center justify-center">
						<div class="serial_number mr2-ns"><?php echo $serial_number ++ ?></div>
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
					<div
						class="shramee-plugin-stats ml-auto flex items-center flex-row flex-column-m justify-center w-100 w-auto-ns">
						<div class="shramee-plugin-downloads ma3">
							<?php echo $plugin[ $display_params['field'] ] ?>
							<small>
								<?php echo $display_params['label'] ?>
							</small>
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
			?>

			<div class="shramee-plugins-footer flex flex-wrap justify-center">
				<div class="shramee-plugin-credits mr-auto w-100 w-auto-ns tc mv2">
					Powered by <a href="https://pootlepress.com/">PootlePress</a>
				</div>
				<?php
				$page_url      = get_the_permalink();
				$facebook_url  = 'https://www.facebook.com/sharer/sharer.php?u=' . $page_url;
				$twitter_url   = 'https://twitter.com/intent/tweet?status=' . rawurlencode( get_the_title() ) . '+' . $page_url;
				$pinterest_url = 'https://pinterest.com/pin/create/bookmarklet/?url=' . $page_url . '&is_video=false&description=' . rawurlencode( get_the_title() );
				?>
				<a class="ml3-ns db no-underline" style="color:#3b5998" title="Share on Facebook"
					 href="<?php echo $facebook_url ?>">
					<i class="f3 fab fa-facebook"></i>
				</a>
				<a class="ml3 db no-underline" style="color:#00aced" title="Tweet on Twitter" href="<?php echo $twitter_url ?>">
					<i class="f3 fab fa-twitter"></i>
				</a>
				<a class="ml3 db no-underline" style="color:#cb2027" title="Share on Instagram" href="<?php echo $pinterest_url ?>">
					<i class="f3 fab fa-pinterest"></i>
				</a>
			</div>
		</div>
		<?php
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
			$args                     = $defaults;
		} else {
			$args = wp_parse_args( $defaults, $defaults );
		}

		$r      = 15.9154; // 100/2PI
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

	public function qry_by_downloads_per_day( $limit = 50 ) {
		$date = date( 'Y-m-d', strtotime( '1 day ago' ) );

		$return_plugins = get_option( "popular_plugins_$date" );

		if ( ! $return_plugins ) {

			$all_plugins = $this->_popular_all_time( $limit * 2 );
			$plugin_dls  = [];

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
				$return_plugins[ $slug ]                      = $all_plugins[ $slug ];
				$return_plugins[ $slug ]['downloads_per_day'] = $dls;
			}

			update_option( "popular_plugins_$date", $return_plugins );
		}

		return $return_plugins;
	}

	public function _popular_all_time( $top = 120, $force = false ) {
		$plugins = get_option( "shramee_popular_plugins-$top" );

		if ( ! $plugins || $force ) {
			$resp = wp_remote_retrieve_body(
				wp_remote_request( "https://api.wordpress.org/plugins/info/1.2/?action=query_plugins&request[per_page]=$top" )
			);

			if ( $resp ) {
				$response = json_decode( $resp, 'array' );
				$plugins  = [];

				foreach ( $response['plugins'] as $plugin ) {
					$plugins[ $plugin['slug'] ] = $plugin;
				}
				update_option( "shramee_popular_plugins-$top", $plugins, 'no' );
			}
		}

		return $plugins;
	}

	public function qry_by_active_installs() {
		return $this->_popular_all_time( 50 );
	}
}

Top_50_WP_Dynamic_Blocks::instance();