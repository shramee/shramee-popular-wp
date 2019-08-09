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
		register_rest_route( 'caxton-boilerplate/v1', '/demo', array(
			'methods'  => 'GET',
			'callback' => [ $this, 'rest_handler_demo'  ],
		) );
	}

	public function register_blocks() {
		register_block_type(
			'caxton-boilerplate/demo',
			[ 'render_callback' => [ $this, 'render_demo' ] ]
		);
	}

	public function rest_handler_demo() {
		//Make sure you convert all spaces ` ` to underscores when using/passing props to block renderer
		$args = $_GET;
		unset( $args['_locale'] );
		return $this->demo( $args );
	}

	public function render_demo( $args ) {
		return 'Args below: <pre>' . print_r( $args, 1 ) . '</pre>';
	}
}

Caxton_Boilerplate_Dynamic_Blocks::instance();