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
            'analyse_ia'  => 'Analyse IA',
            'statut'    => 'Statut',
            'action'      => 'Action'
        ];
    }

    public function column_analyse_ia( $item ) {
        $resume = get_post_meta( $item->ID, 'visa_synthese_resume', true );
        if ( ! $resume ) {
            return '<span style="color:#999;">En attente</span>';
        }
        $color = stripos( $resume, 'Défavorable' ) !== false ? 'red' : 'green';
        return sprintf(
            '<span style="padding:2px 8px; border-radius:4px; background-color:%s; color:#fff; font-weight:bold;">%s</span>',
            esc_attr( $color ),
            esc_html( $resume )
        );
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

        // 4) Construit votre query en n'incluant QUE ces visa_request
        $args = [
            'post_type'      => 'visa_request',
            'post_status'    => 'any',
            'posts_per_page' => $per_page,
            'offset'         => $offset,
            'orderby'        => $orderby,
            'order'          => $order,
            'post__in'       => $request_ids,
        ];

		// Ajouter la recherche personnalisée sur email, nom et numéro
		if ( ! empty( $search ) ) {
			// Recherche par numéro (ID post)
			if ( is_numeric( $search ) ) {
				$args['post__in'] = array_intersect( $request_ids, [ absint( $search ) ] );
			} else {
				// Recherche par email ou nom
				$args['meta_query'] = [
					'relation' => 'OR',
					[
						'key'     => 'level1_email',
						'value'   => $search,
						'compare' => 'LIKE',
					],
					[
						'key'     => 'visa_full_name',
						'value'   => $search,
						'compare' => 'LIKE',
					],
					[
						'key'     => 'visa_prenom',
						'value'   => $search,
						'compare' => 'LIKE',
					],
				];
			}
		}
		
		if ( ! empty( $meta_query ) ) {
			// on peut préciser une relation AND si plusieurs conditions
			if ( isset( $args['meta_query'] ) ) {
				// Fusionner les deux meta_query avec relation AND
				$args['meta_query'] = [
					'relation' => 'AND',
					$args['meta_query'],
					array_merge( [ 'relation' => 'AND' ], $meta_query ),
				];
			} else {
				$args['meta_query'] = array_merge( [ 'relation' => 'AND' ], $meta_query );
			}
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
                $prenom = get_post_meta($item->ID, 'visa_prenom', true);
                $nom    = get_post_meta($item->ID, 'visa_full_name', true);
    
                // Construction du nom complet proprement
                $full_name = trim($prenom . ' ' . $nom);
    
                return esc_html($full_name);
            case 'email':
                return esc_html(get_post_meta($item->ID, 'level1_email', true));
            case 'visa_type':
                return esc_html(get_post_meta($item->ID, 'visa_type', true));
            case 'arrival':
                return esc_html(get_post_meta($item->ID, 'visa_arrival_date', true));
            case 'departure':
                return esc_html(get_post_meta($item->ID, 'visa_departure_date', true));
            case 'statut':
                $validated = get_post_meta($item->ID, 'visa_validated', true);
                $date      = get_post_meta($item->ID, 'visa_validated_date', true);

                if ($validated === 'valide') {
                    $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#28a745" viewBox="0 0 16 16">
                            <path d="M13.485 1.929a1 1 0 0 1 0 1.414L6.343 10.485 2.515 6.657a1 1 0 0 1 1.414-1.414l2.414 2.414 6.343-6.343a1 1 0 0 1 1.414 0z"/>
                            </svg>';
                    $text = $date ? '<br><small>Validé le ' . esc_html(date_i18n('d/m/Y H:i', strtotime($date))) . '</small>' : '';
                    return $icon . $text;
                } elseif ($validated === 'refuse') {
                    return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#dc3545" viewBox="0 0 16 16">
                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                            </svg>';
                } elseif ($validated === 'envoie_a_IA') {
                    return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#fd7e14" viewBox="0 0 16 16">
                                <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/>
                                <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 
                                2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 
                                3.065 0 3.592l.319.094a.873.873 0 0 1 .52 
                                1.255l-.16.292c-.892 1.64.901 3.434 2.541 
                                2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 
                                1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 
                                1.255-.52l.292.16c1.64.893 3.434-.902 
                                2.54-2.541l-.159-.292a.873.873 0 0 1 
                                .52-1.255l.319-.094c1.79-.527 1.79-3.065 
                                0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 
                                0 0 1-1.255-.52l-.094-.319z"/>
                            </svg>';
                } elseif ($validated === 'traite') {
                    return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#20c997" viewBox="0 0 16 16">
                                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM6.97 10.97l5.364-5.364a.75.75 0 0 0-1.06-1.06L6.5 9.293 
                                4.757 7.55a.75.75 0 1 0-1.06 1.06l2.773 
                                2.773a.75.75 0 0 0 1.06 0z"/>
                            </svg>';
                } elseif ($validated === 'a_traiter') {
                    return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#0d6efd" viewBox="0 0 16 16">
                                <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/>
                                <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291a1.873 1.873 0 0 0-1.116-2.693l-.318-.094c-.835-.246-.835-1.428 0-1.674l.319-.094a1.873 1.873 0 0 0 1.115-2.692l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.291.159a1.873 1.873 0 0 0 2.693-1.115l.094-.319z"/>
                            </svg>';
                } elseif ($validated === 'nouveau_document') {
                    return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#0d6efd" viewBox="0 0 16 16">
                                <path d="M4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V5.5L9.5 0H4z"/>
                                <path d="M9.5 0v4a1 1 0 0 0 1 1h4"/>
                                <path d="M8 7.5a.5.5 0 0 1 .5.5v2h2a.5.5 0 0 1 0 1h-2v2a.5.5 0 0 1-1 0v-2h-2a.5.5 0 0 1 0-1h2v-2a.5.5 0 0 1 .5-.5z"/>
                            </svg>';
                } else {
                    return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#ffc107" viewBox="0 0 16 16">
                            <path d="M8 3a5 5 0 1 1-4.546 2.916.5.5 0 1 0-.908-.418A6 6 0 1 0 8 2v1z"/>
                            <path d="M7.5 7V4h1v4H5.5v-1h2z"/>
                            </svg>';
                }
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