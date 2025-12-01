<?php
/**
 * User Dashboard Class
 *
 * Provides a unified dashboard combining order form, file upload, and order tracking
 * in a single SPA-like interface with tab navigation.
 *
 * @package Tabesh
 * @since 1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tabesh_Dashboard
 *
 * Manages the unified user dashboard with:
 * - Smart tab selection based on user state
 * - Tab-based navigation without page reload
 * - Integration with existing Order, Upload, and User modules
 */
class Tabesh_Dashboard {

	/**
	 * User state constants
	 */
	const STATE_NEW_USER       = 'new_user';
	const STATE_PENDING_UPLOAD = 'pending_upload';
	const STATE_TRACKING       = 'tracking';

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialize dashboard.
	}

	/**
	 * Get current user's state for smart tab selection
	 *
	 * @return string User state constant
	 */
	private function get_user_state() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return self::STATE_NEW_USER;
		}

		// Get user's orders.
		$orders = $this->get_user_orders( $user_id );

		// If no orders, show order form.
		if ( empty( $orders ) ) {
			return self::STATE_NEW_USER;
		}

		// Check last order for pending files.
		$last_order = $orders[0];
		$files      = $this->get_order_files( $last_order->id );

		// Check if required files are uploaded.
		if ( ! $this->has_required_files( $files ) ) {
			return self::STATE_PENDING_UPLOAD;
		}

		return self::STATE_TRACKING;
	}

	/**
	 * Get user orders
	 *
	 * @param int $user_id User ID.
	 * @return array Orders.
	 */
	private function get_user_orders( $user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'tabesh_orders';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM $table WHERE user_id = %d AND archived = 0 ORDER BY created_at DESC",
				$user_id
			)
		);
	}

	/**
	 * Get order files
	 *
	 * @param int $order_id Order ID.
	 * @return array Files.
	 */
	private function get_order_files( $order_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'tabesh_files';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM $table WHERE order_id = %d AND deleted_at IS NULL",
				$order_id
			)
		);
	}

	/**
	 * Check if order has required files
	 *
	 * @param array $files Order files.
	 * @return bool True if required files exist.
	 */
	private function has_required_files( $files ) {
		if ( empty( $files ) ) {
			return false;
		}

		$has_text  = false;
		$has_cover = false;

		foreach ( $files as $file ) {
			if ( $file->file_category === 'text' ) {
				$has_text = true;
			}
			if ( $file->file_category === 'cover' ) {
				$has_cover = true;
			}
		}

		return $has_text && $has_cover;
	}

	/**
	 * Get default tab based on user state
	 *
	 * @return string Default tab ID
	 */
	private function get_default_tab() {
		$state = $this->get_user_state();

		switch ( $state ) {
			case self::STATE_NEW_USER:
				return 'order-form';
			case self::STATE_PENDING_UPLOAD:
				return 'upload-manager';
			case self::STATE_TRACKING:
			default:
				return 'user-orders';
		}
	}

	/**
	 * Render the user dashboard shortcode
	 *
	 * @param array $atts Shortcode attributes (unused but kept for WordPress shortcode API).
	 * @return string HTML output.
	 */
	public function render_dashboard( $atts = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			return '<div class="tabesh-dashboard-login-required" dir="rtl">' .
					'<div class="login-message">' .
					'<span class="login-icon">🔐</span>' .
					'<h3>' . esc_html__( 'برای دسترسی به داشبورد باید وارد سیستم شوید', 'tabesh' ) . '</h3>' .
					'<p>' . esc_html__( 'لطفاً ابتدا وارد حساب کاربری خود شوید.', 'tabesh' ) . '</p>' .
					'<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" class="tabesh-btn tabesh-btn-primary">' .
					esc_html__( 'ورود به سیستم', 'tabesh' ) . '</a>' .
					'</div></div>';
		}

		// Enqueue dashboard assets.
		$this->enqueue_dashboard_assets();

		// Get default tab.
		$default_tab = $this->get_default_tab();

		// Start output buffering.
		ob_start();

		// Include main dashboard template.
		include TABESH_PLUGIN_DIR . 'templates/dashboard/user-dashboard.php';

		return ob_get_clean();
	}

	/**
	 * Enqueue dashboard-specific assets
	 *
	 * @return void
	 */
	private function enqueue_dashboard_assets() {
		// Helper function for file versioning.
		$get_file_version = function ( $file_path ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && file_exists( $file_path ) ) {
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				$mtime = @filemtime( $file_path );
				return $mtime !== false ? $mtime : TABESH_VERSION;
			}
			return TABESH_VERSION;
		};

		// Enqueue dashboard CSS.
		wp_enqueue_style(
			'tabesh-dashboard',
			TABESH_PLUGIN_URL . 'assets/css/dashboard.css',
			array(),
			$get_file_version( TABESH_PLUGIN_DIR . 'assets/css/dashboard.css' )
		);

		// Enqueue dashboard JS.
		wp_enqueue_script(
			'tabesh-dashboard',
			TABESH_PLUGIN_URL . 'assets/js/dashboard.js',
			array( 'jquery' ),
			$get_file_version( TABESH_PLUGIN_DIR . 'assets/js/dashboard.js' ),
			true
		);

		// Pass data to JavaScript.
		wp_localize_script(
			'tabesh-dashboard',
			'tabeshDashboardData',
			array(
				'restUrl'    => rest_url( TABESH_REST_NAMESPACE ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'defaultTab' => $this->get_default_tab(),
				'userState'  => $this->get_user_state(),
				'strings'    => array(
					'loading'       => __( 'در حال بارگذاری...', 'tabesh' ),
					'error'         => __( 'خطا در بارگذاری', 'tabesh' ),
					'orderForm'     => __( 'ثبت سفارش', 'tabesh' ),
					'uploadManager' => __( 'مدیریت فایل', 'tabesh' ),
					'userOrders'    => __( 'پیگیری سفارش', 'tabesh' ),
				),
			)
		);
	}

	/**
	 * Get user orders summary for display
	 *
	 * @return array Summary data
	 */
	public function get_user_summary() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return array(
				'total_orders'     => 0,
				'active_orders'    => 0,
				'completed_orders' => 0,
				'pending_uploads'  => 0,
			);
		}

		global $wpdb;
		$orders_table = $wpdb->prefix . 'tabesh_orders';
		$files_table  = $wpdb->prefix . 'tabesh_files';

		// Get orders counts.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$summary = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status IN ('pending', 'confirmed', 'processing', 'ready') THEN 1 ELSE 0 END) as active_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders
            FROM $orders_table 
            WHERE user_id = %d AND archived = 0",
				$user_id
			)
		);
		// phpcs:enable

		// Get orders pending uploads.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$pending_uploads = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT o.id) 
            FROM $orders_table o
            LEFT JOIN (
                SELECT order_id, 
                    SUM(CASE WHEN file_category = 'text' AND deleted_at IS NULL THEN 1 ELSE 0 END) as text_count,
                    SUM(CASE WHEN file_category = 'cover' AND deleted_at IS NULL THEN 1 ELSE 0 END) as cover_count
                FROM $files_table 
                GROUP BY order_id
            ) f ON o.id = f.order_id
            WHERE o.user_id = %d 
            AND o.archived = 0 
            AND (f.text_count IS NULL OR f.text_count = 0 OR f.cover_count IS NULL OR f.cover_count = 0)",
				$user_id
			)
		);
		// phpcs:enable

		return array(
			'total_orders'     => intval( $summary->total_orders ?? 0 ),
			'active_orders'    => intval( $summary->active_orders ?? 0 ),
			'completed_orders' => intval( $summary->completed_orders ?? 0 ),
			'pending_uploads'  => intval( $pending_uploads ?? 0 ),
		);
	}
}
