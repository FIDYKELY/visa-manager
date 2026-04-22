<?php
defined('ABSPATH') || exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Visa_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'visa_request',
            'plural'   => 'visa_requests',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
			'id'   		  => 'N°',
            'full_name'   => 'Nom complet',
            'email'       => 'Email',
            'visa_type'   => 'Type de visa',
            'arrival'     => 'Arrivée',
            'departure'   => 'Départ',
            'action'      => 'Action'
        ];
    }

    public function prepare_items() {
		$columns            = $this->get_columns();
		$hidden             = [];
		$sortable           = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable ];

        $per_page = 10;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        $orderby = !empty($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'date';
        $order   = !empty($_GET['order']) ? sanitize_key($_GET['order']) : 'DESC';
        $search  = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';

		// ← Initialisation en dur pour éviter l'undefined variable
		$meta_query = [];

		// filtrage “du…au” sur date d’arrivée
		$from = sanitize_text_field( $_GET['from_date'] ?? '' );
		$to   = sanitize_text_field( $_GET['to_date']   ?? '' );

		if ( $from ) {
			$meta_query[] = [
				'key'     => 'visa_arrival_date',
				'value'   => $from,
				'compare' => '>=',
				'type'    => 'DATE',
			];
		}
		if ( $to ) {
			$meta_query[] = [
				'key'     => 'visa_arrival_date',
				'value'   => $to,
				'compare' => '<=',
				'type'    => 'DATE',
			];
		}

        // 1) Récupère tous les IDs de commandes « completed »
        if ( function_exists('wc_get_orders') ) {
            $completed_orders = wc_get_orders([
                'status' => 'completed',
                'limit'  => -1,
                'fields' => 'ids',
            ]);
        } else {
            $completed_orders = [];
        }

        // 2) Extrait les request_id stockés dans chaque commande
        $request_ids = [];
        if ( ! empty( $completed_orders ) ) {
            foreach ( $completed_orders as $order_id ) {
                $order = wc_get_order( $order_id );
                if ( ! $order ) {
                    continue;
                }
                foreach ( $order->get_items() as $item ) {
                    $rid = $item->get_meta( 'request_id' );
                    if ( $rid ) {
                        $request_ids[] = absint( $rid );
                    }
                }
            }
            $request_ids = array_unique( $request_ids );
        }

        // 3) Si aucune request n’est liée à une commande terminée, on bloque tout
        if ( empty( $request_ids ) ) {
            $this->items = [];
            $this->set_pagination_args([
                'total_items' => 0,
                'per_page'    => $per_page,
                'total_pages' => 0,
            ]);
            return;
        }

        // 4) Construit votre query en n’incluant QUE ces visa_request
        $args = [
            'post_type'      => 'visa_request',
            'post_status'    => 'any',
            'posts_per_page' => $per_page,
            'offset'         => $offset,
            'orderby'        => $orderby,
            'order'          => $order,
            's'              => $search,
            'post__in'       => $request_ids,
        ];

		
		if ( ! empty( $meta_query ) ) {
			// on peut préciser une relation AND si plusieurs conditions
			$args['meta_query'] = array_merge( [ 'relation' => 'AND' ], $meta_query );
		}

		error_log(print_r($args, true));

        $query = new WP_Query($args);
        $this->items = $query->posts;

        $this->set_pagination_args([
            'total_items' => $query->found_posts,
            'per_page'    => $per_page,
            'total_pages' => ceil($query->found_posts / $per_page)
        ]);
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
			case 'id':
                return $item->ID;
            case 'full_name':
                return esc_html(get_post_meta($item->ID, 'visa_full_name', true));
            case 'email':
                return esc_html(get_post_meta($item->ID, 'level1_email', true));
            case 'visa_type':
                return esc_html(get_post_meta($item->ID, 'visa_type', true));
            case 'arrival':
                return esc_html(get_post_meta($item->ID, 'visa_arrival_date', true));
            case 'departure':
                return esc_html(get_post_meta($item->ID, 'visa_departure_date', true));
            case 'action':
                $link = get_edit_post_link($item->ID);
                return '<a href="' . esc_url($link) . '" class="button">Voir</a>';
            default:
                return '';
        }
    }

    public function get_sortable_columns() {
        return [
			'id'		=> ['id', true],
            'full_name' => ['title', false],
            'visa_type' => ['visa_type', false],
            'arrival'   => ['visa_arrival_date', false],
        ];
    }
}