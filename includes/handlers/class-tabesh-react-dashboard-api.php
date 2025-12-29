<?php
/**
 * React Dashboard API Handler
 * Provides REST API endpoints for the React admin dashboard
 *
 * @package Tabesh
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tabesh_React_Dashboard_API
 *
 * Handles REST API endpoints specifically for the React admin dashboard.
 */
class Tabesh_React_Dashboard_API {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register REST API routes
	 */
	public function register_rest_routes() {
		// Get orders list with pagination and filters.
		register_rest_route(
			TABESH_REST_NAMESPACE,
			'/orders',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_orders' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Get single order details.
		register_rest_route(
			TABESH_REST_NAMESPACE,
			'/orders/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_order' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Get dashboard statistics.
		register_rest_route(
			TABESH_REST_NAMESPACE,
			'/statistics',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_statistics' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Get FTP connection status.
		register_rest_route(
			TABESH_REST_NAMESPACE,
			'/ftp/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_ftp_status' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Refresh FTP connection.
		register_rest_route(
			TABESH_REST_NAMESPACE,
			'/ftp/refresh',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'refresh_ftp' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Get print substeps for an order.
		register_rest_route(
			TABESH_REST_NAMESPACE,
			'/print-substeps/(?P<order_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_print_substeps' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Archive order.
		register_rest_route(
			TABESH_REST_NAMESPACE,
			'/archive-order',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'archive_order' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Restore archived order.
		register_rest_route(
			TABESH_REST_NAMESPACE,
			'/restore-order',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'restore_order' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Check if user has permission to access React dashboard endpoints
	 *
	 * @return bool
	 */
	public function check_permission() {
		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			return false;
		}

		// Check if user has admin dashboard access.
		$admin = Tabesh()->admin;
		if ( ! $admin ) {
			return false;
		}

		return $admin->user_has_admin_dashboard_access( get_current_user_id() );
	}

	/**
	 * Get orders list with pagination and filters
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_orders( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'tabesh_orders';

		// Get parameters.
		$page        = max( 1, intval( $request->get_param( 'page' ) ?? 1 ) );
		$per_page    = min( 100, max( 1, intval( $request->get_param( 'per_page' ) ?? 20 ) ) );
		$status      = sanitize_text_field( $request->get_param( 'status' ) ?? '' );
		$search      = sanitize_text_field( $request->get_param( 'search' ) ?? '' );
		$archived    = filter_var( $request->get_param( 'archived' ) ?? false, FILTER_VALIDATE_BOOLEAN );
		$date_from   = sanitize_text_field( $request->get_param( 'date_from' ) ?? '' );
		$date_to     = sanitize_text_field( $request->get_param( 'date_to' ) ?? '' );

		// Build query.
		$where        = array();
		$where[]      = $wpdb->prepare( 'archived = %d', $archived ? 1 : 0 );

		if ( ! empty( $status ) ) {
			$where[] = $wpdb->prepare( 'status = %s', $status );
		}

		if ( ! empty( $search ) ) {
			$search_like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]     = $wpdb->prepare(
				'(id = %d OR customer_name LIKE %s OR customer_phone LIKE %s OR customer_email LIKE %s)',
				intval( $search ),
				$search_like,
				$search_like,
				$search_like
			);
		}

		if ( ! empty( $date_from ) ) {
			$where[] = $wpdb->prepare( 'DATE(created_at) >= %s', $date_from );
		}

		if ( ! empty( $date_to ) ) {
			$where[] = $wpdb->prepare( 'DATE(created_at) <= %s', $date_to );
		}

		$where_clause = implode( ' AND ', $where );

		// Get total count.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE $where_clause" );

		// Get orders for current page.
		$offset = ( $page - 1 ) * $per_page;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$orders = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);

		// Apply firewall filtering.
		$firewall = new Tabesh_Doomsday_Firewall();
		$orders   = $firewall->filter_orders_for_display( $orders, get_current_user_id(), 'admin' );

		// Format orders for React.
		$formatted_orders = array_map( array( $this, 'format_order' ), $orders );

		return rest_ensure_response(
			array(
				'orders'     => $formatted_orders,
				'pagination' => array(
					'total'       => $total,
					'per_page'    => $per_page,
					'current'     => $page,
					'total_pages' => ceil( $total / $per_page ),
				),
			)
		);
	}

	/**
	 * Get single order details
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_order( $request ) {
		global $wpdb;
		$table    = $wpdb->prefix . 'tabesh_orders';
		$order_id = intval( $request->get_param( 'id' ) );

		if ( $order_id <= 0 ) {
			return new WP_Error(
				'invalid_order_id',
				__( 'شماره سفارش معتبر نیست', 'tabesh' ),
				array( 'status' => 400 )
			);
		}

		$order = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE id = %d",
				$order_id
			)
		);

		if ( ! $order ) {
			return new WP_Error(
				'order_not_found',
				__( 'سفارش یافت نشد', 'tabesh' ),
				array( 'status' => 404 )
			);
		}

		// Apply firewall filtering.
		$firewall = new Tabesh_Doomsday_Firewall();
		$orders   = $firewall->filter_orders_for_display( array( $order ), get_current_user_id(), 'admin' );

		if ( empty( $orders ) ) {
			return new WP_Error(
				'access_denied',
				__( 'شما دسترسی به این سفارش را ندارید', 'tabesh' ),
				array( 'status' => 403 )
			);
		}

		return rest_ensure_response( $this->format_order( $orders[0] ) );
	}

	/**
	 * Get dashboard statistics
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_statistics( $request ) {
		$admin = Tabesh()->admin;
		if ( ! $admin ) {
			return new WP_Error(
				'service_unavailable',
				__( 'سرویس آماری در دسترس نیست', 'tabesh' ),
				array( 'status' => 503 )
			);
		}

		$stats = $admin->get_statistics();

		return rest_ensure_response( $stats );
	}

	/**
	 * Get FTP connection status
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_ftp_status( $request ) {
		$ftp_handler = new Tabesh_FTP_Handler();
		$result      = $ftp_handler->test_connection();

		return rest_ensure_response(
			array(
				'connected' => $result['success'],
				'message'   => $result['message'],
				'timestamp' => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Refresh FTP connection
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function refresh_ftp( $request ) {
		// Force a fresh connection test.
		$ftp_handler = new Tabesh_FTP_Handler();
		$result      = $ftp_handler->test_connection();

		return rest_ensure_response(
			array(
				'connected' => $result['success'],
				'message'   => $result['message'],
				'timestamp' => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Get print substeps for an order
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_print_substeps( $request ) {
		$order_id = intval( $request->get_param( 'order_id' ) );

		if ( $order_id <= 0 ) {
			return new WP_Error(
				'invalid_order_id',
				__( 'شماره سفارش معتبر نیست', 'tabesh' ),
				array( 'status' => 400 )
			);
		}

		$print_substeps = new Tabesh_Print_Substeps();
		$substeps       = $print_substeps->get_order_substeps( $order_id );

		return rest_ensure_response( $substeps );
	}

	/**
	 * Archive order
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function archive_order( $request ) {
		$order_id = intval( $request->get_param( 'order_id' ) );

		if ( $order_id <= 0 ) {
			return new WP_Error(
				'invalid_order_id',
				__( 'شماره سفارش معتبر نیست', 'tabesh' ),
				array( 'status' => 400 )
			);
		}

		$archive = new Tabesh_Archive();
		$result  = $archive->archive_order( $order_id );

		if ( ! $result ) {
			return new WP_Error(
				'archive_failed',
				__( 'خطا در بایگانی سفارش', 'tabesh' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'سفارش با موفقیت بایگانی شد', 'tabesh' ),
			)
		);
	}

	/**
	 * Restore archived order
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function restore_order( $request ) {
		$order_id = intval( $request->get_param( 'order_id' ) );

		if ( $order_id <= 0 ) {
			return new WP_Error(
				'invalid_order_id',
				__( 'شماره سفارش معتبر نیست', 'tabesh' ),
				array( 'status' => 400 )
			);
		}

		$archive = new Tabesh_Archive();
		$result  = $archive->unarchive_order( $order_id );

		if ( ! $result ) {
			return new WP_Error(
				'restore_failed',
				__( 'خطا در بازگردانی سفارش', 'tabesh' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'سفارش با موفقیت بازگردانی شد', 'tabesh' ),
			)
		);
	}

	/**
	 * Format order for React
	 *
	 * @param object $order Order object from database.
	 * @return array Formatted order array.
	 */
	private function format_order( $order ) {
		if ( ! $order ) {
			return array();
		}

		// Convert object to array.
		$order_array = (array) $order;

		// Ensure numeric fields are properly typed.
		$numeric_fields = array( 'id', 'user_id', 'total_price', 'pages', 'quantity', 'archived' );
		foreach ( $numeric_fields as $field ) {
			if ( isset( $order_array[ $field ] ) ) {
				$order_array[ $field ] = (int) $order_array[ $field ];
			}
		}

		// Ensure float fields are properly typed.
		$float_fields = array( 'total_price' );
		foreach ( $float_fields as $field ) {
			if ( isset( $order_array[ $field ] ) ) {
				$order_array[ $field ] = (float) $order_array[ $field ];
			}
		}

		return $order_array;
	}
}
