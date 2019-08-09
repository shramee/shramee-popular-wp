<?php
/*
 * Plugin Name: Caxton block boilerplate
 * Plugin URI: http://shramee.me/
 * Description: Roll out quick blocks for Gutenberg with Caxton
 * Author: Shramee
 * Version: 1.0.0
 * Author URI: http://shramee.me/
 */

/**
 * Class Caxton_Boilerplate
 * Enqueues scripts and styles for blocks.
 * Displays a notice to admin if Caxton is not installed.
 */
class Caxton_Boilerplate {

	/** @var self Instance */
	private static $_instance;

	/**
	 * Returns instance of current class
	 * @return self Instance
	 */
	public static function instance() {
		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Caxton_Boilerplate constructor.
	 */
	protected function __construct() {
		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Initiates hooks
	 * @action init
	 */
	public function init() {
		if ( ! class_exists( 'Caxton' ) ) {
			// Caxton not installed
			add_action( 'admin_notices', array( $this, 'caxton_required_notice' ) );
		} else {
			add_action( 'enqueue_block_editor_assets', array( $this, 'editor_enqueue' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
			// All clear! Initiate hooks
		}
	}

	/**
	 * Enqueues editor styles
	 * @action enqueue_block_editor_assets
	 */
	public function editor_enqueue() {
		$url = plugin_dir_url( __FILE__ );
		wp_enqueue_style( "sm-popular-wp-front", "$url/assets/styles.css" );
		wp_enqueue_script( "sm-popular-wp-admin", "$url/assets/blocks.min.js", array( 'caxton' ) );
	}

	/**
	 * Enqueues front end styles
	 * @action wp_enqueue_scripts
	 */
	public function enqueue() {
		$url = plugin_dir_url( __FILE__ );
		wp_enqueue_style( "sm-popular-wp-front", "$url/assets/styles.css" );
	}

	/**
	 * Adds notice if Caxton is not installed.
	 * @action admin_notices
	 */
	public function caxton_required_notice() {
		echo
			'<div class="notice is-dismissible error">' .

			'<p>' . sprintf(
				__( '%s requires that you have our free plugin %s installed and activated.', 'sm-popular-wp' ),
				'<b>Gutenberg blocks in 25 minutes</b>',
				'<a href="' . admin_url( 'plugin-install.php?s=caxton&tab=search&type=term' ) . '">Caxton</a>'
			) . '</p>' .

			'<p><a  href="' . admin_url( 'plugin-install.php?s=caxton&tab=search&type=term' ) . '" class="button-primary">' .
			__( 'Install Caxton', 'sm-popular-wp' ) . '</a></p>' .

			'</div>';
	}
}

require_once 'inc/dynamic-blocks.php';
Caxton_Boilerplate::instance();