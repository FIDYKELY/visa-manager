<?php
// includes/class-visa-admin.php

defined('ABSPATH') || exit;

use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use setasign\Fpdi\Fpdi;
use Dompdf\Dompdf;
use Dompdf\Options;

require_once plugin_dir_path(__FILE__) . '../libs/fpdf/fpdf.php';
require_once plugin_dir_path(__FILE__) . 'class-visa-list-table.php';


class Visa_Admin {

    public function __construct() {
        add_action('admin_menu',                 [$this, 'register_admin_menu']);
		add_action( 'post_edit_form_tag', 		 [ $this, 'add_form_enctype' ] );
        add_action('admin_init',                 [$this, 'register_settings']);
        add_action('add_meta_boxes',             [$this, 'add_visa_details_metabox']);
        add_action('save_post_visa_request',     [$this, 'save_visa_fields'], 10, 1 );
		add_action('add_meta_boxes', 			 [$this, 'customize_submit_box'], 1);
		add_action('admin_head', [$this, 'inject_admin_styles']);
		
		add_action( 'init', [$this, 'disable_editor_for_visa_request'], 100 );
		
		add_action('add_meta_boxes',             [$this, 'add_visa_email_metabox']);
		add_action('admin_post_send_visa_email', [$this, 'handle_visa_email_send']);
        add_action('admin_notices',              [$this, 'show_visa_email_notice']);
        
        add_action('admin_post_download_all_docs', [$this, 'handle_download_all_docs']);
        
        add_action('add_meta_boxes',         [$this, 'add_documents_metabox']);
        add_action( 'save_post_visa_request',     [ $this, 'save_documents_meta' ],    10, 2 );
        add_action('admin_enqueue_scripts',      [ $this, 'enqueue_documents_assets']);

		// add_action('add_meta_boxes',         [$this, 'add_risque_metabox']);
        //add_action( 'save_post_visa_request',     [ $this, 'save_documents_risque_meta' ],    10, 2 );
        //add_action('admin_enqueue_scripts',      [ $this, 'enqueue_documents_risque_assets']);
        
        add_action('add_meta_boxes',         [$this, 'add_expertise_metabox']);
        add_action('admin_post_download_visa_expertise', [$this, 'handle_download_visa_expertise']);
        
        add_action('add_meta_boxes',         [$this, 'add_passeport_metabox']);
        add_action('add_meta_boxes',         [$this, 'add_group_metabox']);
        
        add_action('add_meta_boxes',         [$this, 'add_gmail_metabox']);

        add_action('add_meta_boxes',         [$this, 'add_cerfa_metabox']);
		add_action('admin_post_download_cerfa_cs', [$this, 'handle_download_cerfa_cs']);
		add_action('admin_post_download_cerfa_ls', [$this, 'handle_download_cerfa_ls']);
		
		// Tranfèrer de document refusé vers validé
		add_action('wp_ajax_visa_transfer_refused_doc', [$this, 'visa_transfer_refused_doc_handler']);

		// Metabox pour afficher les documents refusés
		add_action( 'add_meta_boxes', [$this, 'visa_metabox_documents_refuses'] );
		
		// Ajouter la colonne fichier_url si elle n'existe pas encore
		add_action( 'admin_init', [$this, 'visa_maybe_add_fichier_url_column'] );

		add_action('wp_ajax_send_all_meta_to_n8n', function() {
            $post_id = absint($_POST['post_id'] ?? 0);
            if (!$post_id || !get_post($post_id)) {
                wp_send_json_error('Post ID invalide');
            }
        
            // Récupère tous les meta
            $all_meta = get_post_meta($post_id);
        
            // Optionnel : nettoyer pour enlever les meta système (_edit_last, etc.)
            $clean_meta = [];
            foreach ($all_meta as $key => $value) {
                // get_post_meta retourne un tableau, on prend la première valeur
                $clean_meta[$key] = is_array($value) ? $value[0] : $value;
            }
        
            // ===== GESTION DES DEMANDES GROUPÉES =====
            // Vérifier si la demande fait partie d'un groupe
            $visa_group = get_post_meta($post_id, 'visa_group', true);
            if ($visa_group) {
                // Récupérer le visa_group_id ou utiliser visa_group comme identifiant
                $group_id = get_post_meta($post_id, 'visa_group_id', true);
                // Si pas de visa_group_id, utiliser visa_group comme fallback
                if (!$group_id) {
                    $group_id = $visa_group;
                }
                
                // Récupérer tous les posts du même groupe (excluant le post courant)
                $args = array(
                    'post_type' => 'visa_request',
                    'post_status' => 'any',
                    'meta_key' => 'visa_group',
                    'meta_value' => $group_id,
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'post__not_in' => array($post_id) // exclure le post courant
                );
                $group_posts = get_posts($args);
                
                if (!empty($group_posts)) {
                    $group_members = array();
                    foreach ($group_posts as $pid) {
                        $member = array(
                            'post_id' => $pid,
                            'full_name' => get_post_meta($pid, 'visa_full_name', true),
                            'prenom' => get_post_meta($pid, 'visa_prenom', true),
                            'info_objet_base' => get_post_meta($pid, 'visa_info_objet_base', true),
                            'visa_type' => get_post_meta($pid, 'visa_type', true),
                            // Ajouter d'autres champs si nécessaire
                        );
                        $group_members[] = $member;
                    }
                    $clean_meta['group_members'] = $group_members;
                    $clean_meta['is_grouped'] = true;
                } else {
                    $clean_meta['group_members'] = array();
                    $clean_meta['is_grouped'] = true;
                }
            } else {
                $clean_meta['is_grouped'] = false;
            }
        
            // Envoi vers n8n (exemple avec wp_remote_post)
            $n8n_url = 'https://n8n.joel-stephanas.com/webhook/2ca0b855-ab74-47f1-823a-8dfef6026924';
            $response = wp_remote_post($n8n_url, [
                'body'    => wp_json_encode([
                    'post_id' => $post_id,
                    'meta'    => $clean_meta
                ]),
                'headers' => ['Content-Type' => 'application/json'],
            ]);
						// Réinitialisation du flag après envoi
						update_post_meta($post_id, 'visa_has_new_docs', false);
            wp_send_json_success($response);
        });
    }

    function disable_editor_for_visa_request() {
        remove_post_type_support( 'visa_request', 'editor' );
    }

	public function add_form_enctype() {
		echo ' enctype="multipart/form-data"';
	}

	public function inject_admin_styles() {
		?>
		<style>
		/* cible l’élément via son slug plutôt que l’icône */
		#toplevel_page_visa_manager .wp-menu-name {
			font-weight: bold !important;
			color: #e91e63 !important;
		}
		.woocommerce-menu {
		    font-weight: bold !important;
		    color: #e91e63 !important;
		}
		#visa_request_details div[style*='border:1px solid #eee'] {
			border: 2px solid #007bff !important;
			background-color: #f8f9fa !important;
			padding: 20px !important;
			margin: 15px 0 !important;
			border-radius: 8px !important;
			box-shadow: 0 2px 8px rgba(0, 123, 255, 0.15) !important;
			position: relative;
		}

		#visa_request_details div[style*='border:1px solid #eee'] strong {
			color: #007bff !important;
			font-size: 18px !important;
			font-weight: 600 !important;
			display: block !important;
			margin-bottom: 15px !important;
			padding-bottom: 10px !important;
			border-bottom: 2px solid #007bff !important;
		}

		#visa_request_details div[style*='border:1px solid #eee'] label {
			display: block !important;
			margin-bottom: 8px !important;
			font-weight: 500 !important;
			color: #333 !important;
			font-size: 14px !important;
		}

		#visa_request_details div[style*='border:1px solid #eee'] input[type='text'],
		#visa_request_details div[style*='border:1px solid #eee'] input[type='email'],
		#visa_request_details div[style*='border:1px solid #eee'] select {
			width: 100% !important;
			padding: 8px 12px !important;
			border: 1px solid #ddd !important;
			border-radius: 4px !important;
			font-size: 14px !important;
			margin-bottom: 8px !important;
			box-sizing: border-box !important;
		}

		#visa_request_details div[style*='border:1px solid #eee'] input[type='text']:focus,
		#visa_request_details div[style*='border:1px solid #eee'] input[type='email']:focus,
		#visa_request_details div[style*='border:1px solid #eee'] select:focus {
			outline: none !important;
			border-color: #007bff !important;
			box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1) !important;
		}

		/* Responsive */
		@media (max-width: 782px) {
			#visa_request_details div[style*='border:1px solid #eee'] input[style*='width:49%'] {
				width: 100% !important;
				display: block !important;
				margin-right: 0 !important;
				margin-bottom: 8px !important;
			}
		}
		</style>
		<script>
		    document.addEventListener("DOMContentLoaded", function() {
    		    document.querySelectorAll('.wp-menu-name').forEach(el => {
                  if (el.textContent.trim() === 'WooCommerce') {
                    el.classList.add('woocommerce-menu');
                  }
                });
		    });
		</script>
		<?php
	}

    public function register_admin_menu() {
        add_menu_page(
            'Demandes de visa',
            'Visa Manager',
            'manage_visa_manager',
            'visa_manager',
            [$this, 'render_visa_list'],
            'dashicons-id-alt',
            25
        );

        add_submenu_page(
            'visa_manager',
            'Paramètres Visa',
            'Paramètres',
            'manage_visa_manager',
            'visa_manager_settings',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Paramètres Visa</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('visa_settings_group');
                do_settings_sections('visa_manager_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting('visa_settings_group', 'visa_daily_limit');
        register_setting('visa_settings_group', 'visa_shortstay_max_days');
        register_setting('visa_settings_group', 'visa_payment_amount');

        add_settings_section(
            'visa_settings_section',
            'Configuration Générale',
            null,
            'visa_manager_settings'
        );

        add_settings_field(
            'visa_daily_limit',
            'Limite de demandes par jour',
            [$this, 'render_input_field'],
            'visa_manager_settings',
            'visa_settings_section',
            ['name' => 'visa_daily_limit']
        );
        add_settings_field(
            'visa_shortstay_max_days',
            'Durée max pour court séjour (jours)',
            [$this, 'render_input_field'],
            'visa_manager_settings',
            'visa_settings_section',
            ['name' => 'visa_shortstay_max_days']
        );
        add_settings_field(
            'visa_payment_amount',
            'Montant du paiement (DZD)',
            [$this, 'render_input_field'],
            'visa_manager_settings',
            'visa_settings_section',
            ['name' => 'visa_payment_amount']
        );
		add_settings_field(
            'visa_insurance_price',
            'Prix de l’assurance (DZD)',
            [$this, 'render_input_field'],
            'visa_manager_settings',
            'visa_settings_section',
            [ 'name' => 'visa_insurance_price' ]
        );
    }

    public function render_input_field($args) {
        $value = esc_attr(get_option($args['name'], ''));
        echo "<input type='number' name='{$args['name']}' value='{$value}' />";
    }
	 
    protected function get_profession_map() {
        return [
            '65001' => 'Agriculteur',
            '65002' => 'Architecte',
            '65003' => 'Artisan',
            '65004' => 'Artiste',
            '65005' => 'Autre',
            '65006' => 'Autre technicien',
            '66001' => 'Banquier',
            '67001' => 'Cadre d\'entreprise',
            '67002' => 'Chauffeur, routier',
            '67003' => 'Chef d\'entreprise',
            '67004' => 'Chercheur, scientifique',
            '67005' => 'Chimiste',
            '67006' => 'Chômeur',
            '67007' => 'Clergé, religieux',
            '67008' => 'Commerçant',
            '68001' => 'Diplomate',
            '69001' => 'Electronicien',
            '69005' => 'Elève, Etudiant, stagiaire',
            '69002' => 'Employé',
            '69003' => 'Employé prive au service de diplomate',
            '69004' => 'Enseignant',
            '70001' => 'Fonctionnaire',
            '72001' => 'Homme politique',
            '73001' => 'Informaticien',
            '74001' => 'Journaliste',
            '77001' => 'Magistrat',
            '77002' => 'Marin',
            '77003' => 'Mode, cosmétique',
            '79001' => 'Ouvrier',
            '80001' => 'Personnel de service, administratif ou technique (postes dipl./cons.)',
            '80002' => 'Policier, militaire',
            '80003' => 'Profession juridique',
            '80004' => 'Profession libérale',
            '80005' => 'Profession médicale et paramédicale',
            '82001' => 'Retraite',
            '83001' => 'Sans profession',
            '83002' => 'Sportif',
        ];
    }
	
	/**
     * La liste de demande (page)
     */

    public function render_visa_list() {
		echo '<div class="wrap"><h1>Demandes de visa</h1>';

		$table = new Visa_List_Table();
		$table->prepare_items();

		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="' . esc_attr($_REQUEST['page']) . '">';
		echo '<label>Du : <input type="date" name="from_date" value="'. esc_attr( $_GET['from_date'] ?? '' ) .'"></label> ';
		echo '<label>Au : <input type="date" name="to_date"   value="'. esc_attr( $_GET['to_date']   ?? '' ) .'"></label> ';
		echo '<input type="submit" class="button" value="Filtrer"> ';
		
		$download_args = array_merge(
			$_GET,
			['action' => 'download_all_docs']
		);
		$download_url = esc_url( add_query_arg( $download_args, admin_url('admin-post.php') ) );
		echo '<a href="' . $download_url . '" class="button">Télécharger les documents</a> ';

		$table->search_box('Rechercher', 'visa_search');
		$table->display();
		echo '</form>';

		echo '</div>';
	}
	
    public function handle_download_all_docs() {
		// 1) Permissions
		if ( ! current_user_can('manage_options') ) {
			wp_die('Accès non autorisé.');
		}

		// 2) Récupérer les mêmes filtres que pour la liste
		$from    = sanitize_text_field( $_GET['from_date'] ?? '' );
		$to      = sanitize_text_field( $_GET['to_date']   ?? '' );
		$search  = sanitize_text_field( $_GET['s'] ?? '' );
		$orderby = sanitize_key( $_GET['orderby'] ?? 'date' );
		$order   = sanitize_key( $_GET['order']   ?? 'DESC' );

		// 3) Construire la requête sans pagination
		$args = [
			'post_type'      => 'visa_request',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => $orderby,
			'order'          => $order,
		];

		// Ajouter la recherche personnalisée sur email, nom et numéro
		if ( ! empty( $search ) ) {
			// Recherche par numéro (ID post)
			if ( is_numeric( $search ) ) {
				$args['post__in'] = [ absint( $search ) ];
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

		// meta_query « du/au » sur visa_arrival_date
		$meta_query = [];
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
		if ( ! empty( $meta_query ) ) {
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

		// 4) Exécuter WP_Query
		$q = new WP_Query( $args );
		if ( empty( $q->posts ) ) {
			wp_redirect( wp_get_referer() ?: admin_url() );
			exit;
		}

		// 5) Collecter tous les chemins physique des fichiers joints
		$files = [];
		foreach ( $q->posts as $post ) {
			$docs = get_post_meta( $post->ID, 'visa_documents', true );
			if ( is_array( $docs ) ) {
				foreach ( $docs as $url ) {
					$path = ABSPATH . ltrim( wp_parse_url( $url, PHP_URL_PATH ), '/' );
					if ( file_exists( $path ) ) {
						$files[ $path ] = basename( $path );
					}
				}
			}
		}

		if ( empty( $files ) ) {
			wp_redirect( wp_get_referer() ?: admin_url() );
			exit;
		}

		// 5) Création de l’archive ZIP
		$zip     = new ZipArchive();
		$tmpFile = wp_tempnam( 'visa_docs_' . time() . '.zip' );

		if ( $zip->open( $tmpFile, ZipArchive::OVERWRITE ) !== true ) {
			wp_die( 'Impossible de créer l’archive ZIP.' );
		}

		// 6) Boucle d’ajout en dossiers par post ID
		foreach ( $q->posts as $post ) {
			$docs = get_post_meta( $post->ID, 'visa_documents', true );
			if ( is_array( $docs ) ) {
				foreach ( $docs as $url ) {
					$fullPath = ABSPATH . ltrim( wp_parse_url( $url, PHP_URL_PATH ), '/' );
					if ( file_exists( $fullPath ) ) {
						// Chemin interne au ZIP : postID/nomDuFichier.ext
						$insidePath = $post->ID . '/' . basename( $fullPath );
						$zip->addFile( $fullPath, $insidePath );
					}
				}
			}
		}

		$zip->close();

		// 7) Envoi du ZIP au navigateur
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="visa_documents.zip"' );
		header( 'Content-Length: ' . filesize( $tmpFile ) );
		readfile( $tmpFile );
		unlink( $tmpFile );
		exit;
	}
	
	/**
	 * Affiche la metabox ajouter_metabox_documents_refuses
	 */
	public function visa_metabox_documents_refuses(){
		add_meta_box(
			'visa_metabox_documents_refuses',
			'ÂÂÂÂÂÂDocuments refusés',
			[$this, 'visa_render_metabox_documents_refuses'],
			'visa_request',
			'side',
			'high'
		);
	}

    public function add_visa_details_metabox() {
        add_meta_box(
            'visa_request_details',
            'Détails de la demande',
            [$this, 'render_visa_details_box'],
            'visa_request',
            'normal',
            'default'
        );
    }
	/**
	 * Affiche la metabox “Sauvegarder” et le formulaire
	 */
	
	public function customize_submit_box() {
		// on supprime celle par défaut
		remove_meta_box('submitdiv', 'visa_request', 'side');

		// on ajoute la nôtre, avec un nonce
		add_meta_box(
			'visa_submit_metabox',
			'Sauvegarder la demande',
			[$this, 'render_submit_metabox'],
			'visa_request',
			'side',
			'high'
		);
	}
	
	public function render_submit_metabox( $post ) {
        wp_nonce_field( 'save_visa_submit', 'visa_submit_nonce' );
    
        $validated = get_post_meta( $post->ID, 'visa_validated', true );
        
        if ( empty( $validated ) ) {
            $validated = 'en_attente';
        }
    
        echo '<p><strong>Statut de validation :</strong></p>';
        echo '<p>
            <label>
                <input type="radio" name="visa_validated" value="en_attente" ' . checked( $validated, 'en_attente', false ) . '> En attente
            </label><br>
            <label>
                <input type="radio" name="visa_validated" value="a_traiter" ' . checked( $validated, 'a_traiter', false ) . '> À traiter
            </label><br>
            <label>
                <input type="radio" name="visa_validated" value="valide" ' . checked( $validated, 'valide', false ) . '> Validé
            </label><br>
            <label>
                <input type="radio" name="visa_validated" value="nouveau_document" ' . checked( $validated, 'nouveau_document', false ) . '> Nouveau document
            </label>
        </p>';
    
        echo '<p>Cliquez sur “Enregistrer” pour sauvegarder :</p>';
        echo '<p>
            <button type="button" class="button button-secondary" id="refaire-analyse">Refaire l\'analyse</button>
            <button type="submit" class="button button-primary">Enregistrer</button>
        </p>';
    
        // Conteneur pour la barre de progression
        echo '<div id="progress-container" style="width:100%; background:#eee; border-radius:4px; height:20px; display:none; margin-top:10px;">
                <div id="progress-bar" style="width:0%; height:100%; background:#0073aa; border-radius:4px;"></div>
              </div>';
    
        ?>
        <script>
        jQuery(document).ready(function($){
            $('#refaire-analyse').on('click', function(e){
                e.preventDefault();
    
                var post_id = <?php echo $post->ID; ?>;
    
                // Affiche la barre de progression
                $('#progress-container').show();
                $('#progress-bar').css('width', '0%');
    
                // Démarre l'AJAX
                $.post(ajaxurl, {
                    action: 'send_all_meta_to_n8n',
                    post_id: post_id
                }, function(response){
                    console.log(response);
                    // Simule le remplissage progressif sur 20 secondes
                    var duration = 60000; // 20 secondes
                    var interval = 100;   // intervalle en ms
                    var steps = duration / interval;
                    var width = 0;
                    var increment = 100 / steps;
    
                    var progress = setInterval(function(){
                        width += increment;
                        if(width >= 100) {
                            width = 100;
                            clearInterval(progress);
                            // Recharge la page quand terminé
                            location.reload();
                        }
                        $('#progress-bar').css('width', width + '%');
                    }, interval);
                });
            });
        });
        </script>
        <?php
    }

	
	public function save_visa_fields( $post_id ) {
		//error_log( "save_visa_fields déclenché pour post #{$post_id}" );
		// ou pour bloquer l’écran :
		//wp_die("Hook called: " . current_filter());

		// 1. Vérification du nonce “Enregistrer”
		if (
			! isset( $_POST['visa_submit_nonce'] ) ||
			! wp_verify_nonce( $_POST['visa_submit_nonce'], 'save_visa_submit' )
		) {
			return;
		}

		// 2. Autosave / révision / permission
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
		if ( ! current_user_can('edit_post', $post_id) ) return;

		// 3. Privilege de l’utilisateur
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Validation
		if ( isset( $_POST['visa_validated'] ) ) {
			$status = sanitize_text_field( $_POST['visa_validated'] );
			update_post_meta( $post_id, 'visa_validated', $status );

			if ( $status === '1' ) {
				// Sauvegarde la date/heure de validation
				update_post_meta( $post_id, 'visa_validated_date', current_time('mysql') );
			} else {
				// Si on remet en non validé, on supprime la date (ou on la garde si tu veux l’historique)
				delete_post_meta( $post_id, 'visa_validated_date' );
			}
		}

		// 4. Liste des clés à sauvegarder
		$keys = [
			'level1_email',
			'level1_password',
			'visa_info_objet_base',
			'visa_info_objet_niveau1',
			'visa_type',
			'visa_depot',
			'visa_wilaya',
			'visa_ville',
			'visa_full_name',
			'visa_nom_famille',
			'visa_prenom',
			'visa_birth_date',
			'visa_lieu_naiss',
			'visa_pays_naiss',
			'visa_nationalite',
			'visa_nationalite_diff',
			'visa_sexe',
			'visa_etat_civil',
			'visa_etat_civil_autre',
			'visa_num_national_identite',
			'visa_doc_voyage',
			'visa_doc_voyage_autre',
			'visa_num_document',
			'visa_date_delivrance',
			'visa_date_expiration',
			'visa_delivre_par',
			'visa_adresse',
			'visa_mail',//l
			'visa_phone',
			'visa_num_resident',
			'visa_autre_date_delivrance',//l
			'visa_autre_date_expiration',//l
			'visa_profession',
			'visa_secteur_activite',
			'visa_nom_employeur',
			'visa_cp_employeur',
			'visa_ville_employeur',
			'visa_pays_employeur',
			'visa_num_employeur',
			'visa_mail_employeur',
			'visa_adresse_employeur',
			'visa_situation_professionnelle',
			'visa_employeur',
			'visa_objet',
			'visa_objet_autre',
			'visa_info_employeur',//l
			'visa_adresse_sejour',//l
			'visa_duree',//l
			'visa_moyens_existence',
			'visa_bourse',
			'visa_bourse_detail',
			'visa_prise_en_charge',
			'visa_info_prise_en_charge',
			'visa_famille_resident',
			'visa_info_famille_resident',
			'visa_duree_anterieure',
			'visa_info_duree_anterieure',
			'visa_adresse_duree_anterieure',
			'visa_arrival_date',
			'visa_departure_date',
			'visa_reason',
			'visa_stay_duration',
			'visa_info_objet_visa',

			// Court Séjour
			'visa_sexe_autre',//
			'visa_autres_nationalites',//
			'visa_nom_famille_UE',//
			'visa_prenom_famille',//
			'visa_birth_famille',//
			'visa_nationalite_famille',//
			'visa_num_nationalite_famille',//
			'visa_lien_parente',//
			'visa_lien_parente_autre',//
			'visa_resident',//
			'visa_valid_resident',//
			'visa_info_objet',//
			'visa_etat_membre',//
			'visa_etat_membre_1er_annee',//
			'visa_nbr_entre',//
			'visa_empreinte',//
			'visa_empreinte_date',//
			'visa_num_visa',//
			'visa_autorisation_delivre_par',//
			'visa_autorisation_validite',//
			'visa_autorisation_delivre_au',//
			'visa_hotel',//
			// Détails hébergement / accueil / hôtel / contact / entreprise
			'visa_nom_accueil',
			'visa_prenom_accueil',
			'visa_adresse_accueil',
			'visa_cp_accueil',
			'visa_ville_accueil',
			'visa_pays_accueil',
			'visa_num_accueil',
			'visa_mail_accueil',
			'visa_nom_hotel',
			'visa_adresse_hotel',
			'visa_cp_hotel',
			'visa_ville_hotel',
			'visa_pays_hotel',
			'visa_num_hotel',
			'visa_mail_hotel',
			'visa_nom_contact',
			'visa_prenom_contact',
			'visa_adresse_contact',
			'visa_cp_contact',
			'visa_ville_contact',
			'visa_pays_contact',
			'visa_num_contact',
			'visa_mail_contact',
			'visa_nom_entreprise',
			'visa_adresse_entreprise',
			'visa_cp_entreprise',
			'visa_ville_entreprise',
			'visa_pays_entreprise',
			'visa_num_entreprise',
			'visa_mail_entreprise',
			'visa_adresse_inviteur',//
			'visa_hote',//
			'visa_personne_de_contact',//
			'visa_financement',//
			'visa_demandeur_financement_moyen',//
			'visa_demandeur_financement_moyen_autre',//
			'visa_financement_garant',//
			'visa_garant_autre_detail',//
			'visa_garant_financement_moyen',//
			'visa_garant_financement_moyen_autre',//
			'visa_remplisseur',//
			'visa_adresse_remplisseur',//
			'visa_num_remplisseur',//
			'visa_phone_hote',
			'visa_phone_adresse_inviteur',

			// Long séjour

		];

		$saved_keys = [];
		$skipped_keys = [];
		foreach ( $keys as $key ) {
					if ( ! isset( $_POST[ $key ] ) ) {
						// champ absent => optionnel, on note pour audit mais on ne supprime rien (comportement non-destructif)
						$skipped_keys[] = $key;
						continue;
					}
        
          // 1) Gestion des tableaux
          if ( is_array( $_POST[ $key ] ) ) {
            // sanitize chaque élément et stocke en JSON ou en array
            $clean = array_map( 'sanitize_text_field', $_POST[ $key ] );
            // soit tu stockes l’array directement
            $value = $clean;
            // soit tu stockes la JSON pour garder un unique meta_entry
            // $value = wp_json_encode( $clean );
          }
          // 2) Sinon, ton process existant
          elseif ( in_array( $key, [
						'level1_email',
						'visa_mail',
						'visa_mail_accueil',
						'visa_mail_hotel',
						'visa_mail_contact',
						'visa_mail_entreprise',
					], true ) ) {
            $value = sanitize_email( $_POST[ $key ] );
          }
          elseif ( in_array( $key, [
              'visa_arrival_date', 'visa_departure_date', 'visa_birth_date',
              'visa_date_delivrance','visa_date_expiration',
              'visa_autre_date_delivrance','visa_autre_date_expiration',
              'visa_empreinte_date'
            ], true )
          ) {
            $value = sanitize_text_field( $_POST[ $key ] );
          }
          elseif ( $key === 'visa_stay_duration' ) {
            $value = absint( $_POST[ $key ] );
          }
          else {
            $value = sanitize_text_field( $_POST[ $key ] );
          }
        
          // si on sauvegarde depuis l'admin et que l'admin a sélectionné le libellé,
          // convertit le libellé en code pour rester cohérent avec le front-end
          if ( $key === 'visa_profession' ) {
              $map = $this->get_profession_map();
              if ( in_array( $value, $map, true ) ) {
                  $value = array_search( $value, $map, true );
              }
          }

					update_post_meta( $post_id, $key, $value );
					$saved_keys[] = $key;
        }
		// 4) Mise à jour des membres de la famille (tableau)
		if ( isset( $_POST['lien_parent'] ) && is_array( $_POST['lien_parent'] ) ) {
			$members = [];
			$count   = count( $_POST['lien_parent'] );
			for ( $i = 0; $i < $count; $i++ ) {
				$lien  = sanitize_text_field( $_POST['lien_parent'][ $i ] ?? '' );
				$nom   = sanitize_text_field( $_POST['nom_prenom'][ $i ] ?? '' );
				$date  = sanitize_text_field( $_POST['date_naissance'][ $i ] ?? '' );
				$nat   = sanitize_text_field( $_POST['nationalite_famille'][ $i ] ?? '' );
				if ( $lien || $nom || $date || $nat ) {
					$members[] = [
						'lien'        => $lien,
						'nom'         => $nom,
						'naissance'   => $date,
						'nationalite' => $nat,
					];
				}
			}

			if ( ! empty( $members ) ) {
				update_post_meta( $post_id, 'visa_membres_famille', wp_json_encode( $members ) );
			} else {
				delete_post_meta( $post_id, 'visa_membres_famille' );
			}
		} else {
			delete_post_meta( $post_id, 'visa_membres_famille' );
		}
		// 5. Calcul et sauvegarde de la date limite (5 jours ouvrés ≈ 7 jours calendaires)
		if ( !get_post_meta($post_id, 'visa_deadline_date', true) ) {
				$deadline = date('Y-m-d', strtotime('+7 days'));
				update_post_meta($post_id, 'visa_deadline_date', $deadline);
		}

		// Debug audit: log les clés sauvegardées et ignorées (masque le mot de passe)
		$log_post = $_POST;
		if ( isset( $log_post['level1_password'] ) ) {
			$log_post['level1_password'] = '***';
		}
		error_log( sprintf(
			"visa_save_audit post=%d saved=%s skipped=%s post_payload=%s",
			$post_id,
			json_encode( $saved_keys ),
			json_encode( $skipped_keys ),
			json_encode( $log_post )
		) );
	}
	
	//historique des documents refusés
	public function visa_render_metabox_documents_refuses($post) {
        global $wpdb;
    
        $table_name = $wpdb->prefix . 'visa_documents_refuses';
        $documents = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, nom_document AS nom, fichier_url AS url, date_refus AS date, motif_refus AS motif
                 FROM $table_name 
                 WHERE post_id = %d 
                 ORDER BY date_refus DESC",
                $post->ID
            ),
            ARRAY_A
        );
    
        echo '<div style="padding: 10px;">';
    
        if (empty($documents)) {
            echo '<p style="color: #999; margin: 0; font-style: italic;">Aucun document refusé</p>';
        } else {
            echo '<ul style="list-style: none; padding: 0; margin: 0;">';
    
            foreach ($documents as $doc) {
                $id    = intval($doc['id']);
                $nom   = esc_html($doc['nom']);
                $url   = !empty($doc['url']) ? esc_url($doc['url']) : '';
                $date_refus = esc_html($doc['date']);
                $motif = !empty($doc['motif']) ? esc_html($doc['motif']) : '';
    
                echo '<li style="padding: 8px 0; border-bottom: 1px solid #eee; margin-bottom: 5px;">';
    
                // Nom du document (lien si URL disponible)
                if ($url) {
                    echo '<strong><a href="' . $url . '" target="_blank" style="color:#0073aa; text-decoration:none;">' . $nom . '</a></strong><br>';
                } else {
                    echo '<strong>' . $nom . '</strong><br>';
                }
    
                // Motif de refus
                if ($motif) {
                    echo '<small style="color: #dc3232;">Motif : ' . $motif . '</small><br>';
                }
    
                // Date de refus
                echo '<small style="color: #999;">Refusé le : ' . $date_refus . '</small><br>';
    
                // Bouton de transfert
                echo '<button class="button button-primary visa-transfer-doc" data-docid="' . $id . '" style="margin-top:5px;">Valider</button>';
    
                echo '</li>';
            }
    
            echo '</ul>';
        }
    
        echo '</div>';
    
        // Script JS pour gérer le clic
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($){
            $('.visa-transfer-doc').on('click', function(e){
                e.preventDefault();
                var docId = $(this).data('docid');
                var button = $(this);
        
                button.prop('disabled', true).text('Transfert...');
        
                $.post(ajaxurl, {
                    action: 'visa_transfer_refused_doc',
                    doc_id: docId
                }, function(response){
                    if(response.success){
                        button.text('Transféré ✔');
                    } else {
                        var errorMsg = response.data.message ? response.data.message : 'Erreur inconnue';
                        var debugInfo = response.data.debug ? JSON.stringify(response.data.debug) : '';
                        button.prop('disabled', false).text('Erreur');
                        alert('Transfert échoué : ' + errorMsg + "\nDebug : " + debugInfo);
                    }
                }).fail(function(xhr){
                    alert('Erreur serveur : ' + xhr.status + ' ' + xhr.statusText);
                    button.prop('disabled', false).text('Réessayer');
                });
            });
        });
        </script>
        <?php
    }

	/**
	 * Ajoute la colonne fichier_url dans la table des documents refusés si elle n'existe pas
	 */
	public function visa_maybe_add_fichier_url_column() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'visa_documents_refuses';

		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
			$charset = $wpdb->get_charset_collate();
			$sql = "CREATE TABLE $table_name (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				post_id BIGINT UNSIGNED NOT NULL,
				nom_document VARCHAR(255) NOT NULL,
				fichier_url TEXT DEFAULT NULL,
				motif_refus TEXT DEFAULT NULL,
				date_refus DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY post_id (post_id)
			) $charset;";
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta($sql);
		} else {
			// Ajout colonne fichier_url si absente
			$row = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'fichier_url'");
			if (empty($row)) {
				$wpdb->query("ALTER TABLE $table_name ADD COLUMN fichier_url TEXT DEFAULT NULL AFTER nom_document");
			}
			// Ajout colonne motif_refus si absente
			$row2 = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'motif_refus'");
			if (empty($row2)) {
				$wpdb->query("ALTER TABLE $table_name ADD COLUMN motif_refus TEXT DEFAULT NULL AFTER fichier_url");
			}
		}
	}
    
    public function visa_transfer_refused_doc_handler() {
        $doc_id = isset($_POST['doc_id']) ? absint($_POST['doc_id']) : 0;
        if(!$doc_id){
            wp_send_json_error([
                'message' => 'ID invalide',
                'debug'   => $_POST
            ]);
        }
    
        $result = $this->transfer_refused_document_to_receive($doc_id);
    
        if(is_wp_error($result)){
            wp_send_json_error([
                'message' => $result->get_error_message(),
                'debug'   => $result
            ]);
        }
    
        wp_send_json_success($result);
    }
	
	public function transfer_refused_document_to_receive( $refuse_id ) {
        global $wpdb;
    
        $table_name = $wpdb->prefix . 'visa_documents_refuses';
    
        $refused_doc = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $refuse_id),
            ARRAY_A
        );
    
        if ( ! $refused_doc ) {
            return new WP_Error('not_found', 'Document refusé introuvable', ['status' => 404]);
        }
    
        $post_id     = absint($refused_doc['post_id']);
        $fichier_url = esc_url_raw($refused_doc['fichier_url']);
        $nom         = sanitize_file_name($refused_doc['nom_document']);
    
        if ( ! $post_id || ! get_post($post_id) || empty($fichier_url) ) {
            return new WP_Error('invalid_data', 'Données invalides pour transfert', [
                'post_id'     => $post_id,
                'fichier_url' => $fichier_url,
                'nom'         => $nom,
                'row'         => $refused_doc
            ]);
        }
    
        $existing = get_post_meta($post_id, 'visa_documents', true);
        $merged   = is_array($existing) ? array_merge($existing, [$fichier_url]) : [$fichier_url];
    
        update_post_meta($post_id, 'visa_documents', array_values(array_unique($merged)));
        update_post_meta($post_id, 'visa_validated', 'nouveau_document');
    
        $wpdb->delete(
            $table_name,
            [ 'id' => $refuse_id ],
            [ '%d' ]
        );
    
        return [
            'success'     => true,
            'post_id'     => $post_id,
            'document'    => $nom,
            'fichier_url' => $fichier_url,
            'deleted'     => true,
        ];
    }

	public function render_visa_details_box($post) {
		// Note: le nonce est rendu dans la metabox de soumission `render_submit_metabox`.
		// Éviter de l'ajouter deux fois pour ne pas générer d'ID dupliqué dans le DOM.

		// Récupère les valeurs existantes
		$email        = get_post_meta( $post->ID, 'level1_email', true );
		$password     = get_post_meta( $post->ID, 'level1_password', true );
		$visa_type    = get_post_meta( $post->ID, 'visa_type', true );
		$visa_depot   = get_post_meta( $post->ID, 'visa_depot', true );
		$visa_wilaya  = get_post_meta( $post->ID, 'visa_wilaya', true );
		$visa_ville   = get_post_meta( $post->ID, 'visa_ville', true );
		$info_objet_niveau1 = get_post_meta( $post->ID, 'visa_info_objet_niveau1', true );
		$info_objet_base = get_post_meta( $post->ID, 'visa_info_objet_base', true );
		$full_name    = get_post_meta( $post->ID, 'visa_full_name', true );
		$visa_nom_famille    = get_post_meta( $post->ID, 'visa_nom_famille', true );
		$visa_prenom  = get_post_meta( $post->ID, 'visa_prenom', true );
		$birth_date   = get_post_meta( $post->ID, 'visa_birth_date', true );

		$visa_lieu_naiss   = get_post_meta( $post->ID, 'visa_lieu_naiss', true );
		$visa_pays_naiss   = get_post_meta( $post->ID, 'visa_pays_naiss', true );
		$visa_nationalite  = get_post_meta( $post->ID, 'visa_nationalite', true );
		$visa_nationalite_diff   = get_post_meta( $post->ID, 'visa_nationalite_diff', true );
		$visa_autres_nationalites   = get_post_meta( $post->ID, 'visa_autres_nationalites', true );
		$visa_sexe    = get_post_meta( $post->ID, 'visa_sexe', true );
		$visa_sexe_autre   = get_post_meta( $post->ID, 'visa_sexe_autre', true );
		$visa_etat_civil   = get_post_meta( $post->ID, 'visa_etat_civil', true );
		$visa_etat_civil_autre   = get_post_meta( $post->ID, 'visa_etat_civil_autre', true );
		$visa_num_national_identite   = get_post_meta( $post->ID, 'visa_num_national_identite', true );
		$visa_doc_voyage   = get_post_meta( $post->ID, 'visa_doc_voyage', true );
		$visa_doc_voyage_autre   = get_post_meta( $post->ID, 'visa_doc_voyage_autre', true );
		$visa_num_document   = get_post_meta( $post->ID, 'visa_num_document', true );
		$visa_date_delivrance   = get_post_meta( $post->ID, 'visa_date_delivrance', true );
		$visa_date_expiration   = get_post_meta( $post->ID, 'visa_date_expiration', true );
		$visa_delivre_par   = get_post_meta( $post->ID, 'visa_delivre_par', true );
		$visa_nom_famille_UE   = get_post_meta( $post->ID, 'visa_nom_famille_UE', true );
		$visa_prenom_famille   = get_post_meta( $post->ID, 'visa_prenom_famille', true );
		$visa_birth_famille   = get_post_meta( $post->ID, 'visa_birth_famille', true );
		$visa_nationalite_famille   = get_post_meta( $post->ID, 'visa_nationalite_famille', true );
		$visa_num_nationalite_famille   = get_post_meta( $post->ID, 'visa_num_nationalite_famille', true );
		$visa_lien_parente   = (array) get_post_meta( $post->ID, 'visa_lien_parente', true );
		$visa_lien_parente_autre   = get_post_meta( $post->ID, 'visa_lien_parente_autre', true );
		$visa_adresse   = get_post_meta( $post->ID, 'visa_adresse', true );
		$visa_mail   = get_post_meta( $post->ID, 'visa_mail', true );
		$visa_phone   = get_post_meta( $post->ID, 'visa_phone', true );
		$visa_resident   = get_post_meta( $post->ID, 'visa_resident', true );
		$visa_num_resident   = get_post_meta( $post->ID, 'visa_num_resident', true );
		$visa_valid_resident   = get_post_meta( $post->ID, 'visa_valid_resident', true );
		$visa_autre_date_delivrance   = get_post_meta( $post->ID, 'visa_autre_date_delivrance', true );
		$visa_autre_date_expiration   = get_post_meta( $post->ID, 'visa_autre_date_expiration', true );
		$visa_profession   = get_post_meta( $post->ID, 'visa_profession', true );
		// si le meta contient un code numérique, convertit en libellé pour l'affichage
		$profession_map = $this->get_profession_map();
		if ( isset( $profession_map[ $visa_profession ] ) ) {
			$visa_profession = $profession_map[ $visa_profession ];
		}
		$secteur_activite   = get_post_meta( $post->ID, 'visa_secteur_activite', true );
		$visa_nom_employeur   = get_post_meta( $post->ID, 'visa_nom_employeur', true );
		$visa_cp_employeur   = get_post_meta( $post->ID, 'visa_cp_employeur', true );
		$visa_ville_employeur   = get_post_meta( $post->ID, 'visa_ville_employeur', true );
		$visa_pays_employeur   = get_post_meta( $post->ID, 'visa_pays_employeur', true );
		$visa_num_employeur   = get_post_meta( $post->ID, 'visa_num_employeur', true );
		$visa_mail_employeur   = get_post_meta( $post->ID, 'visa_mail_employeur', true );
		$visa_adresse_employeur   = get_post_meta( $post->ID, 'visa_adresse_employeur', true );
		$visa_situation_professionnelle   = get_post_meta( $post->ID, 'visa_situation_professionnelle', true );
		$visa_employeur   = get_post_meta( $post->ID, 'visa_employeur', true );
		$visa_objet   = get_post_meta( $post->ID, 'visa_objet', true );
		$visa_objet_autre   = get_post_meta( $post->ID, 'visa_objet_autre', true );
		$visa_info_objet   = get_post_meta( $post->ID, 'visa_info_objet', true );
		$visa_info_objet_visa   = get_post_meta( $post->ID, 'visa_info_objet_visa', true );
		$visa_etat_membre   = get_post_meta( $post->ID, 'visa_etat_membre', true );
		$visa_etat_membre_1er_annee   = get_post_meta( $post->ID, 'visa_etat_membre_1er_annee', true );
		$visa_nbr_entre   = get_post_meta( $post->ID, 'visa_nbr_entre', true );
		$visa_empreinte   = get_post_meta( $post->ID, 'visa_empreinte', true );
		$visa_empreinte_date   = get_post_meta( $post->ID, 'visa_empreinte_date', true );
		$visa_num_visa   = get_post_meta( $post->ID, 'visa_num_visa', true );
		$visa_autorisation_delivre_par   = get_post_meta( $post->ID, 'visa_autorisation_delivre_par', true );
		$visa_autorisation_validite   = get_post_meta( $post->ID, 'visa_autorisation_validite', true );
		$visa_autorisation_delivre_au   = get_post_meta( $post->ID, 'visa_autorisation_delivre_au', true );
		$visa_hotel   = get_post_meta( $post->ID, 'visa_hotel', true );
		
		$visa_nom_accueil   = get_post_meta( $post->ID, 'visa_nom_accueil', true );
		$visa_prenom_accueil = get_post_meta( $post->ID, 'visa_prenom_accueil', true );
		$visa_adresse_accueil = get_post_meta( $post->ID, 'visa_adresse_accueil', true );
		$visa_cp_accueil = get_post_meta( $post->ID, 'visa_cp_accueil', true );
		$visa_ville_accueil = get_post_meta( $post->ID, 'visa_ville_accueil', true );
		$visa_pays_accueil = get_post_meta( $post->ID, 'visa_pays_accueil', true );
		$visa_num_accueil = get_post_meta( $post->ID, 'visa_num_accueil', true );
		$visa_mail_accueil = get_post_meta( $post->ID, 'visa_mail_accueil', true );
		
		$visa_nom_hotel   = get_post_meta( $post->ID, 'visa_nom_hotel', true );
		$visa_adresse_hotel = get_post_meta( $post->ID, 'visa_adresse_hotel', true );
		$visa_cp_hotel = get_post_meta( $post->ID, 'visa_cp_hotel', true );
		$visa_ville_hotel = get_post_meta( $post->ID, 'visa_ville_hotel', true );
		$visa_pays_hotel = get_post_meta( $post->ID, 'visa_pays_hotel', true );
		$visa_num_hotel = get_post_meta( $post->ID, 'visa_num_hotel', true );
		$visa_mail_hotel = get_post_meta( $post->ID, 'visa_mail_hotel', true );
		
		$visa_nom_contact   = get_post_meta( $post->ID, 'visa_nom_contact', true );
		$visa_prenom_contact   = get_post_meta( $post->ID, 'visa_prenom_contact', true );
		$visa_adresse_contact = get_post_meta( $post->ID, 'visa_adresse_contact', true );
		$visa_cp_contact = get_post_meta( $post->ID, 'visa_cp_contact', true );
		$visa_ville_contact = get_post_meta( $post->ID, 'visa_ville_contact', true );
		$visa_pays_contact = get_post_meta( $post->ID, 'visa_pays_contact', true );
		$visa_num_contact = get_post_meta( $post->ID, 'visa_num_contact', true );
		$visa_mail_contact = get_post_meta( $post->ID, 'visa_mail_contact', true );
		
		$visa_nom_entreprise   = get_post_meta( $post->ID, 'visa_nom_entreprise', true );
		$visa_adresse_entreprise = get_post_meta( $post->ID, 'visa_adresse_entreprise', true );
		$visa_cp_entreprise = get_post_meta( $post->ID, 'visa_cp_entreprise', true );
		$visa_ville_entreprise = get_post_meta( $post->ID, 'visa_ville_entreprise', true );
		$visa_pays_entreprise = get_post_meta( $post->ID, 'visa_pays_entreprise', true );
		$visa_num_entreprise = get_post_meta( $post->ID, 'visa_num_entreprise', true );
		$visa_mail_entreprise = get_post_meta( $post->ID, 'visa_mail_entreprise', true );
		
		$visa_adresse_inviteur   = get_post_meta( $post->ID, 'visa_adresse_inviteur', true );
		$visa_hote   = get_post_meta( $post->ID, 'visa_hote', true );
		$visa_personne_de_contact   = get_post_meta( $post->ID, 'visa_personne_de_contact', true );
		$visa_financement   = get_post_meta( $post->ID, 'visa_financement', true );
		$visa_demandeur_financement_moyen   = get_post_meta( $post->ID, 'visa_demandeur_financement_moyen', true );
		$visa_demandeur_financement_moyen_autre   = get_post_meta( $post->ID, 'visa_demandeur_financement_moyen_autre', true );
		$visa_financement_garant   = get_post_meta( $post->ID, 'visa_financement_garant', true );
		$visa_garant_autre_detail   = get_post_meta( $post->ID, 'visa_garant_autre_detail', true );
		$visa_garant_financement_moyen   = get_post_meta( $post->ID, 'visa_garant_financement_moyen', true );
		$visa_garant_financement_moyen_autre   = get_post_meta( $post->ID, 'visa_garant_financement_moyen_autre', true );
		// Tuteurs légaux (si fournis depuis le template form-level-3)
		$visa_nom_tuteur_legal_1 = get_post_meta( $post->ID, 'visa_nom_tuteur_legal_1', true );
		$visa_prenom_tuteur_legal_1 = get_post_meta( $post->ID, 'visa_prenom_tuteur_legal_1', true );
		$visa_adresse_tuteur_legal_1 = get_post_meta( $post->ID, 'visa_adresse_tuteur_legal_1', true );
		$visa_code_postal_tuteur_legal_1 = get_post_meta( $post->ID, 'visa_code_postal_tuteur_legal_1', true );
		$visa_ville_tuteur_legal_1 = get_post_meta( $post->ID, 'visa_ville_tuteur_legal_1', true );
		$visa_pays_tuteur_legal_1 = get_post_meta( $post->ID, 'visa_pays_tuteur_legal_1', true );
		$visa_telephone_tuteur_legal_1 = get_post_meta( $post->ID, 'visa_telephone_tuteur_legal_1', true );
		$visa_email_tuteur_legal_1 = get_post_meta( $post->ID, 'visa_email_tuteur_legal_1', true );
		$visa_nationalite_tuteur_legal_1 = get_post_meta( $post->ID, 'visa_nationalite_tuteur_legal_1', true );
		$visa_statut_tuteur_legal_1 = get_post_meta( $post->ID, 'visa_statut_tuteur_legal_1', true );

		$visa_nom_tuteur_legal_2 = get_post_meta( $post->ID, 'visa_nom_tuteur_legal_2', true );
		$visa_prenom_tuteur_legal_2 = get_post_meta( $post->ID, 'visa_prenom_tuteur_legal_2', true );
		$visa_adresse_tuteur_legal_2 = get_post_meta( $post->ID, 'visa_adresse_tuteur_legal_2', true );
		$visa_code_postal_tuteur_legal_2 = get_post_meta( $post->ID, 'visa_code_postal_tuteur_legal_2', true );
		$visa_ville_tuteur_legal_2 = get_post_meta( $post->ID, 'visa_ville_tuteur_legal_2', true );
		$visa_pays_tuteur_legal_2 = get_post_meta( $post->ID, 'visa_pays_tuteur_legal_2', true );
		$visa_telephone_tuteur_legal_2 = get_post_meta( $post->ID, 'visa_telephone_tuteur_legal_2', true );
		$visa_email_tuteur_legal_2 = get_post_meta( $post->ID, 'visa_email_tuteur_legal_2', true );
		$visa_nationalite_tuteur_legal_2 = get_post_meta( $post->ID, 'visa_nationalite_tuteur_legal_2', true );
		$visa_statut_tuteur_legal_2 = get_post_meta( $post->ID, 'visa_statut_tuteur_legal_2', true );

		$visa_remplisseur   = get_post_meta( $post->ID, 'visa_remplisseur', true );
		$visa_adresse_remplisseur   = get_post_meta( $post->ID, 'visa_adresse_remplisseur', true );
		$visa_num_remplisseur   = get_post_meta( $post->ID, 'visa_num_remplisseur', true );
		$visa_phone_hote   = get_post_meta( $post->ID, 'visa_phone_hote', true );
		$visa_phone_adresse_inviteur   = get_post_meta( $post->ID, 'visa_phone_adresse_inviteur', true );
		$visa_info_employeur   = get_post_meta( $post->ID, 'visa_info_employeur', true );
		$visa_adresse_sejour   = get_post_meta( $post->ID, 'visa_adresse_sejour', true );
		$visa_duree   = get_post_meta( $post->ID, 'visa_duree', true );
		$visa_moyens_existence   = get_post_meta( $post->ID, 'visa_moyens_existence', true );
		$visa_bourse   = get_post_meta( $post->ID, 'visa_bourse', true );
		$visa_bourse_detail   = get_post_meta( $post->ID, 'visa_bourse_detail', true );
		$visa_prise_en_charge   = get_post_meta( $post->ID, 'visa_prise_en_charge', true );
		$visa_info_prise_en_charge   = get_post_meta( $post->ID, 'visa_info_prise_en_charge', true );
		$visa_famille_resident   = get_post_meta( $post->ID, 'visa_famille_resident', true );
		$visa_info_famille_resident   = get_post_meta( $post->ID, 'visa_info_famille_resident', true );
		$visa_duree_anterieure   = get_post_meta( $post->ID, 'visa_duree_anterieure', true );
		$visa_info_duree_anterieure   = get_post_meta( $post->ID, 'visa_info_duree_anterieure', true );
		$visa_adresse_duree_anterieure   = get_post_meta( $post->ID, 'visa_adresse_duree_anterieure', true );

		$visa_arrival_date      = get_post_meta( $post->ID, 'visa_arrival_date', true );
		$visa_departure_date    = get_post_meta( $post->ID, 'visa_departure_date', true );
		$visa_reason       = get_post_meta( $post->ID, 'visa_reason', true );
		$visa_stay_duration     = get_post_meta( $post->ID, 'visa_stay_duration', true );

		$members_raw = get_post_meta( $post->ID, 'visa_membres_famille', true );
		$members = json_decode( $members_raw, true ) ?: [];

		$countries = [
			'Afghanistan','Afrique du Sud','Albanie','Algérie','Allemagne','Andorre','Angola',
			'Antigua-et-Barbuda','Arabie Saoudite','Argentine','Arménie','Australie','Autriche',
			'Azerbaïdjan','Bahamas','Bahreïn','Bangladesh','Barbade','Belgique','Belize','Bénin',
			'Bhoutan','Biélorussie','Birmanie','Bolivie','Bosnie-Herzégovine','Botswana','Brésil',
			'Brunei','Bulgarie','Burkina Faso','Burundi','Cabo Verde','Cambodge','Cameroun','Canada',
			'République centrafricaine','Tchad','Chili','Chine','Chypre','Colombie','Comores',
			'Congo (Brazzaville)','Congo (Kinshasa)','Corée du Nord','Corée du Sud','Costa Rica',
			'Côte d’Ivoire','Croatie','Cuba','Danemark','Djibouti','Dominique','République dominicaine',
			'Egypte','Emirats arabes unis','Equateur','Erythrée','Espagne','Estonie','Eswatini',
			'Etats-Unis','Ethiopie','Fidji','Finlande','France','Gabon','Gambie','Géorgie','Ghana',
			'Grèce','Grenade','Guatemala','Guinée','Guinée-Bissau','Guinée équatoriale','Guyana',
			'Haïti','Honduras','Hongrie','Inde','Indonésie','Irak','Iran','Irlande','Islande','Israël',
			'Italie','Jamaïque','Japon','Jordanie','Kazakhstan','Kenya','Kirghizistan','Kiribati',
			'Kosovo','Koweït','Laos','Lettonie','Liban','Libéria','Libye','Liechtenstein','Lituanie',
			'Luxembourg','Macédoine du Nord','Madagascar','Malaisie','Malawi','Maldives','Mali','Malte',
			'Maroc','Îles Marshall','Maurice','Mauritanie','Mexique','Micronésie','Moldavie','Monaco',
			'Mongolie','Monténégro','Mozambique','Namibie','Nauru','Népal','Nicaragua','Niger','Nigéria',
			'Norvège','Nouvelle-Zélande','Oman','Ouganda','Ouzbékistan','Pakistan','Palaos','Palestine',
			'Panama','Papouasie-Nouvelle-Guinée','Paraguay','Pays-Bas','Pérou','Philippines','Pologne',
			'Portugal','Roumanie','Royaume-Uni','Russie','Rwanda','Saint-Kitts-et-Nevis','Saint-Marin',
			'Saint-Vincent-et-les-Grenadines','Sainte-Lucie','Salvador','Samoa','Sao Tomé-et-Principe',
			'Sénégal','Serbie','Seychelles','Sierra Leone','Singapour','Slovaquie','Slovénie','Somalie',
			'Soudan','Soudan du Sud','Sri Lanka','Suède','Suisse','Suriname','Syrie','Tadjikistan',
			'Tanzanie','Tchéquie','Thaïlande','Timor-Leste','Togo','Tonga','Trinité-et-Tobago','Tunisie',
			'Turkménistan','Turquie','Tuvalu','Ukraine','Uruguay','Vanuatu','Vatican','Venezuela','Vietnam',
			'Yémen','Zambie','Zimbabwe'
		];

		$nationalities = [
			'AFG' => 'Afghane (Afghanistan)',
			'ALB' => 'Albanaise (Albanie)',
			'DZA' => 'Algérienne (Algérie)',
			'DEU' => 'Allemande (Allemagne)',
			'USA' => 'Américaine (États-Unis)',
			'AND' => 'Andorrane (Andorre)',
			'AGO' => 'Angolaise (Angola)',
			'ATG' => 'Antiguaise-et-Barbudienne (Antigua-et-Barbuda)',
			'ARG' => 'Argentine (Argentine)',
			'ARM' => 'Arménienne (Arménie)',
			'AUS' => 'Australienne (Australie)',
			'AUT' => 'Autrichienne (Autriche)',
			'AZE' => 'Azerbaïdjanaise (Azerbaïdjan)',
			'BHS' => 'Bahamienne (Bahamas)',
			'BHR' => 'Bahreïnienne (Bahreïn)',
			'BGD' => 'Bangladaise (Bangladesh)',
			'BRB' => 'Barbadienne (Barbade)',
			'BEL' => 'Belge (Belgique)',
			'BLZ' => 'Belizienne (Belize)',
			'BEN' => 'Béninoise (Bénin)',
			'BTN' => 'Bhoutanaise (Bhoutan)',
			'BLR' => 'Biélorusse (Biélorussie)',
			'MMR' => 'Birmane (Birmanie)',
			'GNB' => 'Bissau-Guinéenne (Guinée-Bissau)',
			'BOL' => 'Bolivienne (Bolivie)',
			'BIH' => 'Bosnienne (Bosnie-Herzégovine)',
			'BWA' => 'Botswanaise (Botswana)',
			'BRA' => 'Brésilienne (Brésil)',
			'GBR' => 'Britannique (Royaume-Uni)',
			'BRN' => 'Brunéienne (Brunéi)',
			'BGR' => 'Bulgare (Bulgarie)',
			'BFA' => 'Burkinabée (Burkina)',
			'BDI' => 'Burundaise (Burundi)',
			'KHM' => 'Cambodgienne (Cambodge)',
			'CMR' => 'Camerounaise (Cameroun)',
			'CAN' => 'Canadienne (Canada)',
			'CPV' => 'Cap-verdienne (Cap-Vert)',
			'CAF' => 'Centrafricaine (Centrafrique)',
			'CHL' => 'Chilienne (Chili)',
			'CHN' => 'Chinoise (Chine)',
			'CYP' => 'Chypriote (Chypre)',
			'COL' => 'Colombienne (Colombie)',
			'COM' => 'Comorienne (Comores)',
			'COG' => 'Congolaise (Congo-Brazzaville)',
			'COD' => 'Congolaise (Congo-Kinshasa)',
			'COK' => 'Cookienne (Îles Cook)',
			'CRI' => 'Costaricaine (Costa Rica)',
			'HRV' => 'Croate (Croatie)',
			'CUB' => 'Cubaine (Cuba)',
			'DNK' => 'Danoise (Danemark)',
			'DJI' => 'Djiboutienne (Djibouti)',
			'DOM' => 'Dominicaine (République dominicaine)',
			'DMA' => 'Dominiquaise (Dominique)',
			'EGY' => 'Égyptienne (Égypte)',
			'ARE' => 'Émirienne (Émirats arabes unis)',
			'GNQ' => 'Équato-guineenne (Guinée équatoriale)',
			'ECU' => 'Équatorienne (Équateur)',
			'ERI' => 'Érythréenne (Érythrée)',
			'ESP' => 'Espagnole (Espagne)',
			'TLS' => 'Est-timoraise (Timor-Leste)',
			'EST' => 'Estonienne (Estonie)',
			'ETH' => 'Éthiopienne (Éthiopie)',
			'FJI' => 'Fidjienne (Fidji)',
			'FIN' => 'Finlandaise (Finlande)',
			'FRA' => 'Française (France)',
			'GAB' => 'Gabonaise (Gabon)',
			'GMB' => 'Gambienne (Gambie)',
			'GEO' => 'Géorgienne (Géorgie)',
			'GHA' => 'Ghanéenne (Ghana)',
			'GRD' => 'Grenadienne (Grenade)',
			'GTM' => 'Guatémaltèque (Guatemala)',
			'GIN' => 'Guinéenne (Guinée)',
			'GUY' => 'Guyanienne (Guyana)',
			'HTI' => 'Haïtienne (Haïti)',
			'GRC' => 'Hellénique (Grèce)',
			'HND' => 'Hondurienne (Honduras)',
			'HUN' => 'Hongroise (Hongrie)',
			'IND' => 'Indienne (Inde)',
			'IDN' => 'Indonésienne (Indonésie)',
			'IRQ' => 'Irakienne (Irak)',
			'IRN' => 'Iranienne (Iran)',
			'IRL' => 'Irlandaise (Irlande)',
			'ISL' => 'Islandaise (Islande)',
			'ISR' => 'Israélienne (Israël)',
			'ITA' => 'Italienne (Italie)',
			'CIV' => 'Ivoirienne (Côte d’Ivoire)',
			'JAM' => 'Jamaïcaine (Jamaïque)',
			'JPN' => 'Japonaise (Japon)',
			'JOR' => 'Jordanienne (Jordanie)',
			'KAZ' => 'Kazakhstanaise (Kazakhstan)',
			'KEN' => 'Kenyane (Kenya)',
			'KGZ' => 'Kirghize (Kirghizistan)',
			'KIR' => 'Kiribatienne (Kiribati)',
			'KNA' => 'Kittitienne et Névicienne (Saint-Christophe-et-Niévès)',
			'KWT' => 'Koweïtienne (Koweït)',
			'LAO' => 'Laotienne (Laos)',
			'LSO' => 'Lesothane (Lesotho)',
			'LVA' => 'Lettone (Lettonie)',
			'LBN' => 'Libanaise (Liban)',
			'LBR' => 'Libérienne (Libéria)',
			'LBY' => 'Libyenne (Libye)',
			'LIE' => 'Liechtensteinoise (Liechtenstein)',
			'LTU' => 'Lituanienne (Lituanie)',
			'LUX' => 'Luxembourgeoise (Luxembourg)',
			'MKD' => 'Macédonienne (Macédoine du Nord)',
			'MYS' => 'Malaisienne (Malaisie)',
			'MWI' => 'Malawienne (Malawi)',
			'MDV' => 'Maldivienne (Maldives)',
			'MDG' => 'Malgache (Madagascar)',
			'MLI' => 'Malienne (Mali)',
			'MLT' => 'Maltaise (Malte)',
			'MAR' => 'Marocaine (Maroc)',
			'MHL' => 'Marshallaise (Îles Marshall)',
			'MUS' => 'Mauricienne (Maurice)',
			'MRT' => 'Mauritanienne (Mauritanie)',
			'MEX' => 'Mexicaine (Mexique)',
			'FSM' => 'Micronésienne (Micronésie)',
			'MDA' => 'Moldave (Moldavie)',
			'MCO' => 'Monégasque (Monaco)',
			'MNG' => 'Mongole (Mongolie)',
			'MNE' => 'Monténégrine (Monténégro)',
			'MOZ' => 'Mozambicaine (Mozambique)',
			'NAM' => 'Namibienne (Namibie)',
			'NRU' => 'Nauruane (Nauru)',
			'NLD' => 'Néerlandaise (Pays-Bas)',
			'NZL' => 'Néo-Zélandaise (Nouvelle-Zélande)',
			'NPL' => 'Népalaise (Népal)',
			'NIC' => 'Nicaraguayenne (Nicaragua)',
			'NGA' => 'Nigériane (Nigéria)',
			'NER' => 'Nigérienne (Niger)',
			'NIU' => 'Niuéenne (Niue)',
			'PRK' => 'Nord-coréenne (Corée du Nord)',
			'NOR' => 'Norvégienne (Norvège)',
			'OMN' => 'Omanaise (Oman)',
			'UGA' => 'Ougandaise (Ouganda)',
			'UZB' => 'Ouzbéke (Ouzbékistan)',
			'PAK' => 'Pakistanaise (Pakistan)',
			'PLW' => 'Palaosienne (Palaos)',
			'PSE' => 'Palestinienne (Palestine)',
			'PAN' => 'Panaméenne (Panama)',
			'PNG' => 'Papouane-Néo-Guinéenne (Papouasie-Nouvelle-Guinée)',
			'PRY' => 'Paraguayenne (Paraguay)',
			'PER' => 'Péruvienne (Pérou)',
			'PHL' => 'Philippine (Philippines)',
			'POL' => 'Polonaise (Pologne)',
			'PRT' => 'Portugaise (Portugal)',
			'QAT' => 'Qatarienne (Qatar)',
			'ROU' => 'Roumaine (Roumanie)',
			'RUS' => 'Russe (Russie)',
			'RWA' => 'Rwandaise (Rwanda)',
			'LCA' => 'Saint-Lucienne (Sainte-Lucie)',
			'SMR' => 'Saint-Marinaise (Saint-Marin)',
			'VCT' => 'Saint-Vincentaise et Grenadine (Saint-Vincent-et-les-Grenadines)',
			'SLB' => 'Salomonaise (Îles Salomon)',
			'SLV' => 'Salvadorienne (Salvador)',
			'WSM' => 'Samoane (Samoa)',
			'STP' => 'Santoméenne (Sao Tomé-et-Principe)',
			'SAU' => 'Saoudienne (Arabie saoudite)',
			'SEN' => 'Sénégalaise (Sénégal)',
			'SRB' => 'Serbe (Serbie)',
			'SYC' => 'Seychelloise (Seychelles)',
			'SLE' => 'Sierra-Léonaise (Sierra Leone)',
			'SGP' => 'Singapourienne (Singapour)',
			'SVK' => 'Slovaque (Slovaquie)',
			'SVN' => 'Slovène (Slovénie)',
			'SOM' => 'Somalienne (Somalie)',
			'SDN' => 'Soudanaise (Soudan)',
			'LKA' => 'Sri-Lankaise (Sri Lanka)',
			'ZAF' => 'Sud-Africaine (Afrique du Sud)',
			'KOR' => 'Sud-Coréenne (Corée du Sud)',
			'SSD' => 'Sud-Soudanaise (Soudan du Sud)',
		];

		// Niveau 1 : email
		echo '<div style="display:flex;width:100%;justify-content: space-between;"><div style="width:49%;"><p><label><strong>Adresse email</strong><br>
			  <input type="email" name="level1_email" value="' . esc_attr( $email ) . '" style="width:100%;" required>
			  </label></p></div>';
		echo '<div style="width:49%;"><p><label><strong>Mot de passe</strong><br>
			  <input type="text" name="level1_password" value="' . esc_attr( $password ) . '" style="width:100%;" required>
			  </label></p></div></div>';

		// Niveau 2 : type de visa
		echo '<p><label><strong>Type de visa</strong><br>
			  <select name="visa_type" style="width:100%;" required>
				  <option value="">Sélectionnez…</option>
				  <option value="court_sejour"'   . selected( $visa_type, 'court_sejour', false ) . '>Court séjour</option>
				  <option value="long_sejour"'    . selected( $visa_type, 'long_sejour', false )  . '>Long séjour</option>
			  </select>
			  </label></p>';
		echo '<p><label><strong>Ville de dépot</strong><br>
			  <select name="visa_depot" style="width:100%;" required>
				  <option value="">Sélectionnez…</option>
				  <option value="Alger"'   . selected( $visa_depot, 'Alger', false ) . '>Alger</option>
				  <option value="Annaba"'    . selected( $visa_depot, 'Annaba', false )  . '>Annaba</option>
				  <option value="Constantine"'    . selected( $visa_depot, 'Constantine', false )  . '>Constantine</option>
				  <option value="Oran"'    . selected( $visa_depot, 'Oran', false )  . '>Oran</option>
			  </select>
			  </label></p>';
		echo '<div style="display:flex;width:100%;justify-content: space-between;"><div style="width:49%;"><p><label><strong>Wilaya</strong><br>
			  <input type="text" name="visa_wilaya" value="' . esc_attr( $visa_wilaya ) . '" style="width:100%;" required>
			  </label></p></div>';
		echo '<div style="width:49%;"><p><label><strong>Ville</strong><br>
			  <input type="text" name="visa_ville" value="' . esc_attr( $visa_ville ) . '" style="width:100%;" required>
			  </label></p></div></div>';
		

		// Niveau 3 : infos personnelles & dates
		if ( $visa_type === 'court_sejour' ) {
			
			// 1. Nom [nom de famille]
			echo '<p><label><strong>1. Nom [nom de famille]</strong><br>
				<input type="text" name="visa_full_name" value="' . esc_attr( $full_name ) . '" style="width:100%;" required>
			</label></p>';

			// 2. Nom à la naissance [nom(s) de famille antérieur(s)]
			echo '<p><label><strong>2. Nom à la naissance [nom(s) de famille antérieur(s)]</strong><br>
				<input type="text" name="visa_nom_famille" value="' . esc_attr( $visa_nom_famille ) . '" style="width:100%;" required>
			</label></p>';

			// 3. Prénom(s) [nom(s) usuel(s)]
			echo '<p><label><strong>3. Prénom(s) [nom(s) usuel(s)]</strong><br>
				<input type="text" name="visa_prenom" value="' . esc_attr( $visa_prenom ) . '" style="width:100%;" required>
			</label></p>';

			// 4. Date de naissance (jour-mois-année)
			echo '<p><label><strong>4. Date de naissance (jour-mois-année)</strong><br>
				<input type="date" name="visa_birth_date" value="' . esc_attr( $birth_date ) . '" style="width:100%;" required>
			</label></p>';

			// 5. Lieu de naissance
			echo '<p><label><strong>5. Lieu de naissance</strong><br>
				<input type="text" name="visa_lieu_naiss" value="' . esc_attr( $visa_lieu_naiss ) . '" style="width:100%;" required>
			</label></p>';

			// 6. Pays de naissance
			echo '<p><label><strong>6. Pays de naissance </strong><span class="required">*</span><br>';
			echo '<select id="visa_pays_naiss" name="visa_pays_naiss" style="width:100%;" required>';
			echo '<option value="">' . esc_html__( '-- Sélectionnez un pays --', 'text-domain' ) . '</option>';

			foreach ( $countries as $country ) {
				echo '<option value="' . esc_attr( $country ) . '"' 
					. selected( $visa_pays_naiss, $country, false ) 
					. '>' . esc_html( $country ) . '</option>';
			}

			echo '</select></label></p>';

			// 7. Nationalité actuelle
			echo '<p><label><strong>7. Nationalité actuelle</strong><span class="required">*</span><br>';
			echo '<select name="visa_nationalite" style="width:100%;" required>';
			echo '<option value="">' . esc_html__( '-- Sélectionnez une nationalité --', 'text-domain' ) . '</option>';

			foreach ( $nationalities as $code => $label ) {
				echo '<option value="' . esc_attr( $code ) . '"' 
					. selected( $visa_nationalite, $code, false ) 
					. '>' . esc_html( $label ) . '</option>';
			}
			echo '</select></label></p>';

			// Nationalité à la naissance, si différente
			echo '<p><label><strong>Nationalité à la naissance, si différente</strong><br>';
			echo '<select name="visa_nationalite_diff" style="width:100%;">';
			echo '<option value="">' . esc_html__( '-- Sélectionnez une nationalité --', 'text-domain' ) . '</option>';

			foreach ( $nationalities as $code => $label ) {
				echo '<option value="' . esc_attr( $code ) . '"' 
					. selected( $visa_nationalite_diff, $code, false ) 
					. '>' . esc_html( $label ) . '</option>';
			}

			echo '</select></label></p>';

			// Autre(s) nationalité(s)
			echo '<p><label><strong>Autre(s) nationalité(s)</strong><br>
				<input type="text" name="visa_autres_nationalites" value="' . esc_attr( $visa_autres_nationalites ) . '" style="width:100%;">
			</label></p>';

			// 8. Sexe
			echo '<p><label><strong>8. Sexe</strong><br>';
			echo '<input type="radio" name="visa_sexe" value="homme" ' . checked( $visa_sexe, 'homme', false ) . '> Homme ';
			echo '<input type="radio" name="visa_sexe" value="femme" ' . checked( $visa_sexe, 'femme', false ) . '> Femme ';
			echo '<input type="radio" name="visa_sexe" value="autre" ' . checked( $visa_sexe, 'autre', false ) . '> Autre<br>';
			echo '<input type="text" name="visa_sexe_autre" value="' . esc_attr( $visa_sexe_autre ) . '" style="width:100%;">';
			echo '</label></p>';

			// 9. État Civil
			echo '<p><label><strong>9. État Civil</strong><br>';
			echo '<input type="radio" name="visa_etat_civil" value="celibataire" ' . checked( $visa_etat_civil, 'celibataire', false ) . '> Célibataire ';
			echo '<input type="radio" name="visa_etat_civil" value="marie" ' . checked( $visa_etat_civil, 'marie', false ) . '> Marié(e) ';
			echo '<input type="radio" name="visa_etat_civil" value="partenariat" ' . checked( $visa_etat_civil, 'partenariat', false ) . '> Partenariat enregistré ';
			echo '<input type="radio" name="visa_etat_civil" value="separe" ' . checked( $visa_etat_civil, 'separe', false ) . '> Séparé(e) ';
			echo '<input type="radio" name="visa_etat_civil" value="divorce" ' . checked( $visa_etat_civil, 'divorce', false ) . '> Divorcé(e) ';
			echo '<input type="radio" name="visa_etat_civil" value="veuf" ' . checked( $visa_etat_civil, 'veuf', false ) . '> Veuf(Veuve) ';
			echo '<input type="radio" name="visa_etat_civil" value="autre" ' . checked( $visa_etat_civil, 'autre', false ) . '> Autre<br>';
			echo '<input type="text" name="visa_etat_civil_autre" value="' . esc_attr( $visa_etat_civil_autre ) . '" style="width:100%;">';
			echo '</label></p>';

			

			// Afficher les tuteurs légaux structurés (si présents)
			if ( $visa_nom_tuteur_legal_1 || $visa_prenom_tuteur_legal_1 || $visa_adresse_tuteur_legal_1 ) {
				echo '<div style="border:1px solid #eee;padding:10px;margin:8px 0;background:#fafafa;">';
				echo '<strong>Tuteur légal n°1</strong><br/>';
				echo '<label>Nom: <input type="text" name="visa_nom_tuteur_legal_1" value="' . esc_attr( $visa_nom_tuteur_legal_1 ) . '" style="width:100%;"></label><br/>';
				echo '<label>Prénom: <input type="text" name="visa_prenom_tuteur_legal_1" value="' . esc_attr( $visa_prenom_tuteur_legal_1 ) . '" style="width:100%;"></label><br/>';
				echo '<label>Adresse: <input type="text" name="visa_adresse_tuteur_legal_1" value="' . esc_attr( $visa_adresse_tuteur_legal_1 ) . '" style="width:100%;"></label><br/>';
				echo '<label>Code postal: <input type="text" name="visa_code_postal_tuteur_legal_1" value="' . esc_attr( $visa_code_postal_tuteur_legal_1 ) . '" style="width:49%; margin-right:2%;"></label><br/>';
				echo '<label>Ville: <input type="text" name="visa_ville_tuteur_legal_1" value="' . esc_attr( $visa_ville_tuteur_legal_1 ) . '" style="width:49%;"></label><br/>';
				echo '<label>Pays: <input type="text" name="visa_pays_tuteur_legal_1" value="' . esc_attr( $visa_pays_tuteur_legal_1 ) . '" style="width:49%; margin-right:2%;"></label><br/>';
				echo '<label>Téléphone: <input type="text" name="visa_telephone_tuteur_legal_1" value="' . esc_attr( $visa_telephone_tuteur_legal_1 ) . '" style="width:49%;"></label><br/>';
				echo '<label>Email: <input type="email" name="visa_email_tuteur_legal_1" value="' . esc_attr( $visa_email_tuteur_legal_1 ) . '" style="width:100%;"></label><br/>';
				echo '<label>Nationalité: <input type="text" name="visa_nationalite_tuteur_legal_1" value="' . esc_attr( $visa_nationalite_tuteur_legal_1 ) . '" style="width:100%;"></label><br/>';
				echo '<label>Statut: <select name="visa_statut_tuteur_legal_1" style="width:100%;"><option value="">-- Sélectionnez un statut --</option><option value="Apatride" ' . selected( $visa_statut_tuteur_legal_1, 'Apatride', false ) . '>Apatride</option><option value="Réfugié 1946/51" ' . selected( $visa_statut_tuteur_legal_1, 'Réfugié 1946/51', false ) . '>Réfugié 1946/51</option><option value="Réfugié hs conv" ' . selected( $visa_statut_tuteur_legal_1, 'Réfugié hs conv', false ) . '>Réfugié hs conv</option></select></label>';
				echo '</div>';
			}

			if ( $visa_nom_tuteur_legal_2 || $visa_prenom_tuteur_legal_2 || $visa_adresse_tuteur_legal_2 ) {
				echo '<div style="border:1px solid #eee;padding:10px;margin:8px 0;background:#fafafa;">';
				echo '<strong>Tuteur légal n°2</strong><br/>';
				echo '<label>Nom: <input type="text" name="visa_nom_tuteur_legal_2" value="' . esc_attr( $visa_nom_tuteur_legal_2 ) . '" style="width:100%;"></label><br/>';
				echo '<label>Prénom: <input type="text" name="visa_prenom_tuteur_legal_2" value="' . esc_attr( $visa_prenom_tuteur_legal_2 ) . '" style="width:100%;"></label><br/>';
				echo '<label>Adresse: <input type="text" name="visa_adresse_tuteur_legal_2" value="' . esc_attr( $visa_adresse_tuteur_legal_2 ) . '" style="width:100%;"></label><br/>';
				echo '<label>Code postal: <input type="text" name="visa_code_postal_tuteur_legal_2" value="' . esc_attr( $visa_code_postal_tuteur_legal_2 ) . '" style="width:49%; margin-right:2%;"></label><br/>';
				echo '<label>Ville: <input type="text" name="visa_ville_tuteur_legal_2" value="' . esc_attr( $visa_ville_tuteur_legal_2 ) . '" style="width:49%;"></label><br/>';
				echo '<label>Pays: <input type="text" name="visa_pays_tuteur_legal_2" value="' . esc_attr( $visa_pays_tuteur_legal_2 ) . '" style="width:49%; margin-right:2%;"></label><br/>';
				echo '<label>Téléphone: <input type="text" name="visa_telephone_tuteur_legal_2" value="' . esc_attr( $visa_telephone_tuteur_legal_2 ) . '" style="width:49%;"></label><br/>';
				echo '<label>Email: <input type="email" name="visa_email_tuteur_legal_2" value="' . esc_attr( $visa_email_tuteur_legal_2 ) . '" style="width:100%;"></label><br/>';
				echo '<label>Nationalité: <input type="text" name="visa_nationalite_tuteur_legal_2" value="' . esc_attr( $visa_nationalite_tuteur_legal_2 ) . '" style="width:100%;"></label>';
				echo '<label>Statut: <select name="visa_statut_tuteur_legal_2" style="width:100%;"><option value="">-- Sélectionnez un statut --</option><option value="Apatride" ' . selected( $visa_statut_tuteur_legal_2, 'Apatride', false ) . '>Apatride</option><option value="Réfugié 1946/51" ' . selected( $visa_statut_tuteur_legal_2, 'Réfugié 1946/51', false ) . '>Réfugié 1946/51</option><option value="Réfugié hs conv" ' . selected( $visa_statut_tuteur_legal_2, 'Réfugié hs conv', false ) . '>Réfugié hs conv</option></select></label>';
				echo '</div>';
			}

			// 11. Numéro national d’identité
			echo '<p><label><strong>10. Numéro national d’identité, le cas échéant</strong><br>
				<input type="text" name="visa_num_national_identite" value="' . esc_attr( $visa_num_national_identite ) . '" style="width:100%;">
			</label></p>';

			// 11. Type de document de voyage
			echo '<p><label><strong>11. Type de document de voyage</strong><br>';
			echo '<input type="radio" name="visa_doc_voyage" value="passeport_ordinaire" ' . checked( $visa_doc_voyage, 'passeport_ordinaire', false ) . '> Passeport ordinaire ';
			echo '<input type="radio" name="visa_doc_voyage" value="passeport_diplomatique" ' . checked( $visa_doc_voyage, 'passeport_diplomatique', false ) . '> Passeport diplomatique ';
			echo '<input type="radio" name="visa_doc_voyage" value="passeport_service" ' . checked( $visa_doc_voyage, 'passeport_service', false ) . '> Passeport de service ';
			echo '<input type="radio" name="visa_doc_voyage" value="passeport_officiel" ' . checked( $visa_doc_voyage, 'passeport_officiel', false ) . '> Passeport officiel ';
			echo '<input type="radio" name="visa_doc_voyage" value="passeport_special" ' . checked( $visa_doc_voyage, 'passeport_special', false ) . '> Passeport spécial ';
			echo '<input type="radio" name="visa_doc_voyage" value="autre" ' . checked( $visa_doc_voyage, 'autre', false ) . '> Autre document (à préciser)<br>';
			echo '<input type="text" name="visa_doc_voyage_autre" value="' . esc_attr( $visa_doc_voyage_autre ) . '" style="width:100%;">';
			echo '</label></p>';

			// 13. Numéro du document de voyage
			echo '<p><label><strong>12. Numéro du document de voyage</strong><br>
				<input type="text" name="visa_num_document" value="' . esc_attr( $visa_num_document ) . '" style="width:100%;" required>
			</label></p>';

			// 14. Date de délivrance
			echo '<p><label><strong>13. Date de délivrance</strong><br>
				<input type="date" name="visa_date_delivrance" value="' . esc_attr( $visa_date_delivrance ) . '" style="width:100%;" required>
			</label></p>';

			// 15. Date d’expiration
			echo '<p><label><strong>14. Date d’expiration</strong><br>
				<input type="date" name="visa_date_expiration" value="' . esc_attr( $visa_date_expiration ) . '" style="width:100%;" required>
			</label></p>';

			// 16. Délivré par (pays)
			echo '<p><label><strong>15. Délivré par (pays)</strong><span class="required">*</span><br>';
			echo '<select id="visa_delivre_par" name="visa_delivre_par" style="width:100%;" required>';
			echo '<option value="">' . esc_html__( '-- Sélectionnez un pays --', 'text-domain' ) . '</option>';

			foreach ( $countries as $country ) {
				echo '<option value="' . esc_attr( $country ) . '"' 
					. selected( $visa_delivre_par, $country, false ) 
					. '>' . esc_html( $country ) . '</option>';
			}

			echo '</select></label></p>';

			// 17. Données du membre de la famille UE/EEE/Suisse/UK
			echo '<p><label><strong>16. Nom (membre UE/EEE/Suisse/UK)</strong><br>
				<input type="text" name="visa_nom_famille_UE" value="' . esc_attr( $visa_nom_famille_UE ) . '" style="width:100%;">
			</label></p>';
			echo '<p><label><strong>Prénom(s)</strong><br>
				<input type="text" name="visa_prenom_famille" value="' . esc_attr( $visa_prenom_famille ) . '" style="width:100%;">
			</label></p>';
			echo '<p><label><strong>Date de naissance</strong><br>
				<input type="date" name="visa_birth_famille" value="' . esc_attr( $visa_birth_famille ) . '" style="width:100%;">
			</label></p>';
			echo '<p><label><strong>Nationalité</strong><br>';
			echo '<select name="visa_nationalite_famille" style="width:100%;">';
			echo '<option value="">' . esc_html__( '-- Sélectionnez une nationalité --', 'text-domain' ) . '</option>';

			foreach ( $nationalities as $code => $label ) {
				echo '<option value="' . esc_attr( $code ) . '"' 
					. selected( $visa_nationalite_famille, $code, false ) 
					. '>' . esc_html( $label ) . '</option>';
			}

			echo '</select></label></p>';
			echo '<p><label><strong>Numéro document</strong><br>
				<input type="text" name="visa_num_nationalite_famille" value="' . esc_attr( $visa_num_nationalite_famille ) . '" style="width:100%;">
			</label></p>';

			// 18. Lien de parenté
			echo '<p><label><strong>17. Lien de parenté </strong><br>';
			echo '<input type="checkbox" name="visa_lien_parente[]" value="conjoint" '
               . ( in_array( 'conjoint', $visa_lien_parente, true ) ? 'checked' : '' )
               . '> Conjoint ';
			echo '<input type="checkbox" name="visa_lien_parente[]" value="enfant" ' 
				. ( in_array( 'enfant', (array) $visa_lien_parente ) ? 'checked' : '' ) 
				. '> Enfant ';
			echo '<input type="checkbox" name="visa_lien_parente[]" value="petit_fils" ' 
				. ( in_array( 'petit_fils', (array) $visa_lien_parente ) ? 'checked' : '' ) 
				. '> Petit-fils/fille ';
			echo '<input type="checkbox" name="visa_lien_parente[]" value="ascendant" ' 
				. ( in_array( 'ascendant', (array) $visa_lien_parente ) ? 'checked' : '' ) 
				. '> Ascendant dépendant ';
			echo '<input type="checkbox" name="visa_lien_parente[]" value="partenariat" ' 
				. ( in_array( 'partenariat', (array) $visa_lien_parente ) ? 'checked' : '' ) 
				. '> Partenariat enregistré ';
			echo '<input type="checkbox" name="visa_lien_parente[]" value="autre" ' 
				. ( in_array( 'autre', (array) $visa_lien_parente ) ? 'checked' : '' ) 
				. '> Autre<br>';
			echo '<input type="text" name="visa_lien_parente_autre" value="' 
				. esc_attr( $visa_lien_parente_autre ) 
				. '" style="width:100%;">';
			echo '</label></p>';

			// 19. Adresse & e-mail du demandeur
			echo '<p><label><strong>18. Adresse & e-mail du demandeur</strong><br>';
			echo '<input type="text" name="visa_adresse" value="' 
				. esc_attr( $visa_adresse ) 
				. '" style="width:100%;" required><br>';
			echo '<p><label><strong>Numéro de téléphone</strong><br>';
			echo '<input type="text" name="visa_phone" value="' 
				. esc_attr( $visa_phone ) 
				. '" style="width:100%;">';
			echo '</label></p>';

			// 20. Résidence hors nationalité
			echo '<p><label><strong>19. Résidence hors nationalité</strong><br>';
			echo '<input type="radio" name="visa_resident" value="non" ' 
				. checked( $visa_resident, 'non', false ) 
				. '> Non ';
			echo '<input type="radio" name="visa_resident" value="oui" ' 
				. checked( $visa_resident, 'oui', false ) 
				. '> Oui : Titre de séjour ou équivalent<br>';
			echo '<input type="text" name="visa_num_resident" value="' 
				. esc_attr( $visa_num_resident ) 
				. '" style="width:49%; margin-right:2%;">';
			echo '<input type="date" name="visa_valid_resident" value="' 
				. esc_attr( $visa_valid_resident ) 
				. '" style="width:49%;">';
			echo '</label></p>';


			// 21. Profession actuelle
			echo '
            <p><label><strong>20.Situation professionnelle</strong></label><br>
            <select name="visa_situation_professionnelle" style="width:100%;" required>
              <option value="">-- Sélectionnez une situation professionnelle --</option>
              <option value="En activité" ' . selected( $visa_situation_professionnelle, 'En activité', false ) . '>En activité</option>
              <option value="Sans profession" ' . selected( $visa_situation_professionnelle, 'Sans profession', false ) . '>Sans profession</option>
              <option value="Chômeur" ' . selected( $visa_situation_professionnelle, 'Chômeur', false ) . '>Chômeur</option>
              <option value="Retraité" ' . selected( $visa_situation_professionnelle, 'Retraité', false ) . '>Retraité</option>
              <option value="Etudiant" ' . selected( $visa_situation_professionnelle, 'Etudiant', false ) . '>Étudiant</option>
            </select>
            </p>';
            
			
			echo '<p><label><strong>21. Activité professionnelle actuelle</strong><span class="required">*</span><br>';
			echo '<select name="visa_profession" required>';
						echo '<option value="">&nbsp;</option>';
						echo '<option value="Agriculteur"' . selected($visa_profession, 'Agriculteur') . '>Agriculteur</option>';
						echo '<option value="Architecte"' . selected($visa_profession, 'Architecte') . '>Architecte</option>';
						echo '<option value="Artisan"' . selected($visa_profession, 'Artisan') . '>Artisan</option>';
						echo '<option value="Artiste"' . selected($visa_profession, 'Artiste') . '>Artiste</option>';
						echo '<option value="Autre"' . selected($visa_profession, 'Autre') . '>Autre</option>';
						echo '<option value="Autre technicien"' . selected($visa_profession, 'Autre technicien') . '>Autre technicien</option>';
						echo '<option value="Banquier"' . selected($visa_profession, 'Banquier') . '>Banquier</option>';
						echo '<option value="Cadre d\'entreprise"' . selected($visa_profession, 'Cadre d\'entreprise') . '>Cadre d\'entreprise</option>';
						echo '<option value="Chauffeur, routier"' . selected($visa_profession, 'Chauffeur, routier') . '>Chauffeur, routier</option>';
						echo '<option value="Chef d\'entreprise"' . selected($visa_profession, 'Chef d\'entreprise') . '>Chef d\'entreprise</option>';
						echo '<option value="Chercheur, scientifique"' . selected($visa_profession, 'Chercheur, scientifique') . '>Chercheur, scientifique</option>';
						echo '<option value="Chimiste"' . selected($visa_profession, 'Chimiste') . '>Chimiste</option>';
						echo '<option value="Chômeur"' . selected($visa_profession, 'Chômeur') . '>Chômeur</option>';
						echo '<option value="Clergé, religieux"' . selected($visa_profession, 'Clergé, religieux') . '>Clergé, religieux</option>';
						echo '<option value="Commerçant"' . selected($visa_profession, 'Commerçant') . '>Commerçant</option>';
						echo '<option value="Diplomate"' . selected($visa_profession, 'Diplomate') . '>Diplomate</option>';
						echo '<option value="Electronicien"' . selected($visa_profession, 'Electronicien') . '>Electronicien</option>';
						echo '<option value="Elève, Etudiant, stagiaire"' . selected($visa_profession, 'Elève, Etudiant, stagiaire') . '>Elève, Etudiant, stagiaire</option>';
						echo '<option value="Employé"' . selected($visa_profession, 'Employé') . '>Employé</option>';
						echo '<option value="Employé prive au service de diplomate"' . selected($visa_profession, 'Employé prive au service de diplomate') . '>Employé prive au service de diplomate</option>';
						echo '<option value="Enseignant"' . selected($visa_profession, 'Enseignant') . '>Enseignant</option>';
						echo '<option value="Fonctionnaire"' . selected($visa_profession, 'Fonctionnaire') . '>Fonctionnaire</option>';
						echo '<option value="Homme politique"' . selected($visa_profession, 'Homme politique') . '>Homme politique</option>';
						echo '<option value="Informaticien"' . selected($visa_profession, 'Informaticien') . '>Informaticien</option>';
						echo '<option value="Journaliste"' . selected($visa_profession, 'Journaliste') . '>Journaliste</option>';
						echo '<option value="Magistrat"' . selected($visa_profession, 'Magistrat') . '>Magistrat</option>';
						echo '<option value="Marin"' . selected($visa_profession, 'Marin') . '>Marin</option>';
						echo '<option value="Mode, cosmétique"' . selected($visa_profession, 'Mode, cosmétique') . '>Mode, cosmétique</option>';
						echo '<option value="Ouvrier"' . selected($visa_profession, 'Ouvrier') . '>Ouvrier</option>';
						echo '<option value="Personnel de service, administratif ou technique (postes dipl./cons.)"' . selected($visa_profession, 'Personnel de service, administratif ou technique (postes dipl./cons.)') . '>Personnel de service, administratif ou technique (postes dipl./cons.)</option>';
						echo '<option value="Policier, militaire"' . selected($visa_profession, 'Policier, militaire') . '>Policier, militaire</option>';
						echo '<option value="Profession juridique"' . selected($visa_profession, 'Profession juridique') . '>Profession juridique</option>';
						echo '<option value="Profession libérale"' . selected($visa_profession, 'Profession libérale') . '>Profession libérale</option>';
						echo '<option value="Profession médicale et paramédicale"' . selected($visa_profession, 'Profession médicale et paramédicale') . '>Profession médicale et paramédicale</option>';
						echo '<option value="Retraite"' . selected($visa_profession, 'Retraite') . '>Retraite</option>';
						echo '<option value="Sans profession"' . selected($visa_profession, 'Sans profession') . '>Sans profession</option>';
						echo '<option value="Sportif"' . selected($visa_profession, 'Sportif') . '>Sportif</option>';
				echo '</select>';
			echo '</label></p>';
			echo '</p>';
			
			echo '<p><label><strong>Secteur d\'activité</strong><br>';
            echo '<select name="visa_secteur_activite" style="width:100%;">';
            echo '<option value="">-- Sélectionnez un secteur d\'activité --</option>';
            echo '<option value="Activités de services administratifs et de soutien"' . selected( $secteur_activite, 'Activités de services administratifs et de soutien', false ) . '>Activités de services administratifs et de soutien</option>';
            echo '<option value="Activités des ménages en tant qu\'employeurs; activités indifférenciées des ménages en tant que producteurs de biens et services pour usage propre"' . selected( $secteur_activite, 'Activités des ménages en tant qu\'employeurs; activités indifférenciées des ménages en tant que producteurs de biens et services pour usage propre', false ) . '>Activités des ménages en tant qu\'employeurs; activités indifférenciées des ménages en tant que producteurs de biens et services pour usage propre</option>';
            echo '<option value="Activités extra-territoriales"' . selected( $secteur_activite, 'Activités extra-territoriales', false ) . '>Activités extra-territoriales</option>';
            echo '<option value="Activités financières et d\'assurance"' . selected( $secteur_activite, 'Activités financières et d\'assurance', false ) . '>Activités financières et d\'assurance</option>';
            echo '<option value="Activités immobilières"' . selected( $secteur_activite, 'Activités immobilières', false ) . '>Activités immobilières</option>';
            echo '<option value="Activités spécialisées, scientifiques et techniques"' . selected( $secteur_activite, 'Activités spécialisées, scientifiques et techniques', false ) . '>Activités spécialisées, scientifiques et techniques</option>';
            echo '<option value="Administration publique"' . selected( $secteur_activite, 'Administration publique', false ) . '>Administration publique</option>';
            echo '<option value="Agriculture, sylviculture et pêche"' . selected( $secteur_activite, 'Agriculture, sylviculture et pêche', false ) . '>Agriculture, sylviculture et pêche</option>';
            echo '<option value="Arts, spectacles et activités récréatives"' . selected( $secteur_activite, 'Arts, spectacles et activités récréatives', false ) . '>Arts, spectacles et activités récréatives</option>';
            echo '<option value="Autres activités"' . selected( $secteur_activite, 'Autres activités', false ) . '>Autres activités</option>';
            echo '<option value="Autres activités de services"' . selected( $secteur_activite, 'Autres activités de services', false ) . '>Autres activités de services</option>';
            echo '<option value="Commerce; réparation d\'automobiles et de motocycles"' . selected( $secteur_activite, 'Commerce; réparation d\'automobiles et de motocycles', false ) . '>Commerce; réparation d\'automobiles et de motocycles</option>';
            echo '<option value="Construction"' . selected( $secteur_activite, 'Construction', false ) . '>Construction</option>';
            echo '<option value="Enseignement"' . selected( $secteur_activite, 'Enseignement', false ) . '>Enseignement</option>';
            echo '<option value="Hébergement et restauration"' . selected( $secteur_activite, 'Hébergement et restauration', false ) . '>Hébergement et restauration</option>';
            echo '<option value="Industrie manufacturière"' . selected( $secteur_activite, 'Industrie manufacturière', false ) . '>Industrie manufacturière</option>';
            echo '<option value="Industries extractives"' . selected( $secteur_activite, 'Industries extractives', false ) . '>Industries extractives</option>';
            echo '<option value="Information et communication"' . selected( $secteur_activite, 'Information et communication', false ) . '>Information et communication</option>';
            echo '<option value="Production et distribution d\'eau; assainissement, gestion des déchets et dépollution"' . selected( $secteur_activite, 'Production et distribution d\'eau; assainissement, gestion des déchets et dépollution', false ) . '>Production et distribution d\'eau; assainissement, gestion des déchets et dépollution</option>';
            echo '<option value="Production et distribution d\'électricité, de gaz, de vapeur et d\'air conditionné"' . selected( $secteur_activite, 'Production et distribution d\'électricité, de gaz, de vapeur et d\'air conditionné', false ) . '>Production et distribution d\'électricité, de gaz, de vapeur et d\'air conditionné</option>';
            echo '<option value="Santé humaine et action sociale"' . selected( $secteur_activite, 'Santé humaine et action sociale', false ) . '>Santé humaine et action sociale</option>';
            echo '<option value="Transports et entreposage"' . selected( $secteur_activite, 'Transports et entreposage', false ) . '>Transports et entreposage</option>';
            
            echo '</select></label></p>';

			// 22. Employeur / établissement
			echo '<p><label><strong>22. Nom, adresse et téléphone employeur</strong><br>
				<textarea name="visa_employeur" style="width:100%;" required>' . esc_textarea( $visa_employeur ) . '</textarea>
			</label></p>';
			echo '
                <p><label>Nom de l’employeur ou établissement<br>
                <input type="text" name="visa_nom_employeur" value="' . esc_attr( $visa_nom_employeur ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Adresse<br>
                <input type="text" name="visa_adresse_employeur" value="' . esc_attr( $visa_adresse_employeur ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Code postal<br>
                <input type="text" name="visa_cp_employeur" value="' . esc_attr( $visa_cp_employeur ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Ville<br>
                <input type="text" name="visa_ville_employeur" value="' . esc_attr( $visa_ville_employeur ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Pays<br>
                <input type="text" name="visa_pays_employeur" value="' . esc_attr( $visa_pays_employeur ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Téléphone<br>
                <input type="text" name="visa_num_employeur" value="' . esc_attr( $visa_num_employeur ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Email<br>
                <input type="email" name="visa_mail_employeur" value="' . esc_attr( $visa_mail_employeur ) . '" style="width:100%;">
                </label></p>
                ';

			// 23. Objet(s) du voyage
			echo '<p><label><strong>23. Objet(s) du voyage</strong><br>';

            echo '<input type="radio" name="visa_objet" value="activite_professionnelle" ' . checked( $visa_objet, 'etablissement_familial_prive', false ) . '> Établissement familial ou privé '; 
            echo '<input type="radio" name="visa_objet" value="etudes" ' . checked( $visa_objet, 'etudes', false ) . '> Études '; 
            echo '<input type="radio" name="visa_objet" value="medical" ' . checked( $visa_objet, 'medical', false ) . '> Raisons médicales '; 
            echo '<input type="radio" name="visa_objet" value="tourisme" ' . checked( $visa_objet, 'tourisme', false ) . '> Tourisme '; 
            echo '<input type="radio" name="visa_objet" value="travailler" ' . checked( $visa_objet, 'travailler', false ) . '> Travailler '; 
            echo '<input type="radio" name="visa_objet" value="accord_retrait" ' . checked( $visa_objet, 'accord_retrait', false ) . '> Visa d\'entrée (accord de retrait) '; 
            echo '<input type="radio" name="visa_objet" value="visite_familiale_privee" ' . checked( $visa_objet, 'visite_familiale_privee', false ) . '> Visite familiale ou privée '; 
            echo '<input type="radio" name="visa_objet" value="visite_officielle" ' . checked( $visa_objet, 'visite_officielle', false ) . '> Visite officielle '; 
            echo '<input type="radio" name="visa_objet" value="autre" ' . checked( $visa_objet, 'autre', false ) . '> Autre<br>';

                echo '<input type="text" name="visa_objet_autre" value="' 
                    . esc_attr( $visa_objet_autre ) . '" style="width:100%;">';
            echo '</label></p>';
			// 24. Informations complémentaires
			echo '<p><label><strong>24. Informations complémentaires sur l\'objet du voyage</strong><br>
				<textarea name="visa_info_objet_base" style="width:100%;">' . esc_textarea( $info_objet_base ) . '</textarea>
			</label></p>';

			// 25. État membre de destination principale
			echo '<p><label><strong>25. État membre de destination principale</strong><br>
				<textarea name="visa_etat_membre" style="width:100%;" required>' . esc_textarea( $visa_etat_membre ) . '</textarea>
			</label></p>';

			// 26. État membre de première entrée
			echo '<p><label><strong>26. État membre de première entrée</strong><br>
				<input type="text" name="visa_etat_membre_1er_annee" value="' . esc_attr( $visa_etat_membre_1er_annee ) . '" style="width:100%;" required>
			</label></p>';

			// 27. Nombre d’entrées demandées
			echo '<p><label><strong>27. Nombre d’entrées demandées</strong><br>';
			echo '<input type="radio" name="visa_nbr_entre" value="une_entree" ' 
				. checked( $visa_nbr_entre, 'une_entree', false ) 
				. '> Une entrée ';
			echo '<input type="radio" name="visa_nbr_entre" value="deux_entrees" ' 
				. checked( $visa_nbr_entre, 'deux_entrees', false ) 
				. '> Deux entrées ';
			echo '<input type="radio" name="visa_nbr_entre" value="entrees_multiples" ' 
				. checked( $visa_nbr_entre, 'entrees_multiples', false ) 
				. '> Entrées multiples';
			echo '</label></p>';

			// Dates arrivée & départ
			echo '<p><label><strong>Date d’arrivée prévue (1er séjour)</strong><br>
				<input type="date" name="visa_arrival_date" value="' . esc_attr( $visa_arrival_date ) . '" style="width:100%;" required>
			</label></p>';
			echo '<p><label><strong>Date de départ prévue (1er séjour)</strong><br>
				<input type="date" name="visa_departure_date" value="' . esc_attr( $visa_departure_date ) . '" style="width:100%;" required>
			</label></p>';

			// 28. Empreintes digitales
			echo '<p><label><strong>28. Empreintes digitales relevées précédemment</strong><br>
				<input type="radio" name="visa_empreinte" value="non" '.checked( $visa_empreinte, 'non', false ) .'> Non
				<input type="radio" name="visa_empreinte" value="oui"'.checked( $visa_empreinte, 'oui', false ) .'> Oui<br>
				<input type="date" name="visa_empreinte_date" value="' . esc_attr( $visa_empreinte_date ) . '" style="width:100%;">
			</label></p>';

			// Numéro du visa connu
			echo '<p><label><strong>Numéro du visa, si connu</strong><br>
				<input type="text" name="visa_num_visa" value="' . esc_attr( $visa_num_visa ) . '" style="width:100%;">
			</label></p>';

			// 29. Autorisation d’entrée finale
			echo '<p><label><strong>29. Autorisation d’entrée dans le pays de destination finale</strong><br>
				<label>Délivrée par</label><br>
				<input type="text" name="visa_autorisation_delivre_par" value="' . esc_attr( $visa_autorisation_delivre_par ) . '" style="width:100%;"><br>
				<label>valable du</label><br>
				<input type="date" name="visa_autorisation_validite" value="' . esc_attr( $visa_autorisation_validite ) . '" style="width:100%;"><br>
				<label>au</label><br>
				<input type="date" name="visa_autorisation_delivre_au" value="' . esc_attr( $visa_autorisation_delivre_au ) . '" style="width:100%;">
			</label></p>';

			// 30. Invités / hébergement
			echo '<p><label><strong>30. Nom et prénom des invités / hôtels</strong><br>
				<textarea name="visa_hotel" style="width:100%;">' . esc_textarea( $visa_hotel ) . '</textarea>
			</label></p>';
			echo '<p><label><strong>Adresse et e-mail des invités / hôtels</strong><br>
				<textarea name="visa_adresse_inviteur" style="width:100%;">' . esc_textarea( $visa_adresse_inviteur ) . '</textarea>
			</label></p>';
			echo '<p><label><strong>Numéro de téléphone :</strong><br>
				<input type="text" name="visa_phone_adresse_inviteur" value="' . esc_attr( $visa_phone_adresse_inviteur ) . '" style="width:100%;">
			</label></p>';
			
			echo '
                <p><label>Nom de l’hebergeur<br>
                <input type="text" name="visa_nom_accueil" value="' . esc_attr( $visa_nom_accueil ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Prenom de l’hebergeur<br>
                <input type="text" name="visa_prenom_accueil" value="' . esc_attr( $visa_prenom_accueil ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Adresse<br>
                <input type="text" name="visa_adresse_accueil" value="' . esc_attr( $visa_adresse_accueil ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Code postal<br>
                <input type="text" name="visa_cp_accueil" value="' . esc_attr( $visa_cp_accueil ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Ville<br>
                <input type="text" name="visa_ville_accueil" value="' . esc_attr( $visa_ville_accueil ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Pays<br>
                <input type="text" name="visa_pays_accueil" value="' . esc_attr( $visa_pays_accueil ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Téléphone<br>
                <input type="text" name="visa_num_accueil" value="' . esc_attr( $visa_num_accueil ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Email<br>
                <input type="email" name="visa_mail_accueil" value="' . esc_attr( $visa_mail_accueil ) . '" style="width:100%;">
                </label></p>
                ';
            
            echo '
                <p><label>Nom de l’hotel ou le lieu d\'hebergement<br>
                <input type="text" name="visa_nom_hotel" value="' . esc_attr( $visa_nom_hotel ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Adresse<br>
                <input type="text" name="visa_adresse_hotel" value="' . esc_attr( $visa_adresse_hotel ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Code postal<br>
                <input type="text" name="visa_cp_hotel" value="' . esc_attr( $visa_cp_hotel ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Ville<br>
                <input type="text" name="visa_ville_hotel" value="' . esc_attr( $visa_ville_hotel ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Pays<br>
                <input type="text" name="visa_pays_hotel" value="' . esc_attr( $visa_pays_hotel ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Téléphone<br>
                <input type="text" name="visa_num_hotel" value="' . esc_attr( $visa_num_hotel ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Email<br>
                <input type="email" name="visa_mail_hotel" value="' . esc_attr( $visa_mail_hotel ) . '" style="width:100%;">
                </label></p>
                ';

			// 31. Hôte entreprise/organisation
			echo '<p><label><strong>31. Nom et adresse de l’entreprise/organisation hôte</strong><br>
				<textarea name="visa_hote" style="width:100%;">' . esc_textarea( $visa_hote ) . '</textarea>
			</label></p>';
			echo '<p><label><strong>Contact dans l’entreprise/organisation</strong><br>
				<textarea name="visa_personne_de_contact" style="width:100%;">' . esc_textarea( $visa_personne_de_contact ) . '</textarea>
			</label></p>';
			echo '<p><label><strong>Numéro de téléphone :</strong><br>
				<input type="text" name="visa_phone_hote" value="' . esc_attr( $visa_phone_hote ) . '" style="width:100%;">
			</label></p>';
			
			echo '
                <p><label>Nom de l’entreprise<br>
                <input type="text" name="visa_nom_entreprise" value="' . esc_attr( $visa_nom_entreprise ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Adresse<br>
                <input type="text" name="visa_adresse_entreprise" value="' . esc_attr( $visa_adresse_entreprise ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Code postal<br>
                <input type="text" name="visa_cp_entreprise" value="' . esc_attr( $visa_cp_entreprise ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Ville<br>
                <input type="text" name="visa_ville_entreprise" value="' . esc_attr( $visa_ville_entreprise ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Pays<br>
                <input type="text" name="visa_pays_entreprise" value="' . esc_attr( $visa_pays_entreprise ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Téléphone<br>
                <input type="text" name="visa_num_entreprise" value="' . esc_attr( $visa_num_entreprise ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Email<br>
                <input type="email" name="visa_mail_entreprise" value="' . esc_attr( $visa_mail_entreprise ) . '" style="width:100%;">
                </label></p>
                ';
            
            echo '
                <p><label>Nom de la personne de contact<br>
                <input type="text" name="visa_nom_contact" value="' . esc_attr( $visa_nom_contact ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Prenom de la personne de contact<br>
                <input type="text" name="visa_prenom_contact" value="' . esc_attr( $visa_prenom_contact ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Adresse<br>
                <input type="text" name="visa_adresse_contact" value="' . esc_attr( $visa_adresse_contact ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Code postal<br>
                <input type="text" name="visa_cp_contact" value="' . esc_attr( $visa_cp_contact ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Ville<br>
                <input type="text" name="visa_ville_contact" value="' . esc_attr( $visa_ville_contact ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Pays<br>
                <input type="text" name="visa_pays_contact" value="' . esc_attr( $visa_pays_contact ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Téléphone<br>
                <input type="text" name="visa_num_contact" value="' . esc_attr( $visa_num_contact ) . '" style="width:100%;">
                </label></p>
                
                <p><label>Email<br>
                <input type="email" name="visa_mail_contact" value="' . esc_attr( $visa_mail_contact ) . '" style="width:100%;">
                </label></p>
                ';

			// 32. Les frais de voyage et de subsistance durant le séjour du demandeur sont financés
			echo '<fieldset class="financing">';
			echo '<legend><strong>32. Les frais de voyage et de subsistance durant le séjour du demandeur sont financés</strong><span class="required">*</span></legend>';

			// Choix du financeur
			echo '<div class="financing-options">';
			echo '<label><input type="radio" name="visa_financement" value="demandeur" ' . checked( $visa_financement, 'demandeur', false ) . '> Par le demandeur</label>';
			echo '<label><input type="radio" name="visa_financement" value="garant" ' . checked( $visa_financement, 'garant', false ) . '> Par un garant</label>';
			echo '</div>';

			// Section Demandeur
			echo '<div class="subsection" data-for="demandeur">';
			echo '<p><strong>Moyens de subsistance :</strong><span class="required">*</span></p>';
			echo '<div class="checkbox-grid">';
			foreach ( [ 'liquide' => 'Argent liquide', 'cheque' => 'Chèques de voyage', 'credit' => 'Carte de crédit', 'hebergement' => 'Hébergement prépayé', 'transport' => 'Transport prépayé' ] as $val => $label ) {
				echo '<label><input type="checkbox" name="visa_demandeur_financement_moyen[]" value="' . $val . '" ' . ( in_array( $val, (array) $visa_demandeur_financement_moyen ) ? 'checked' : '' ) . '> ' . $label . '</label>';
			}
			echo '<label><input type="checkbox" name="visa_demandeur_financement_moyen[]" value="autre" ' . ( in_array( 'autre', (array) $visa_demandeur_financement_moyen ) ? 'checked' : '' ) . '> Autre
				<input type="text" name="visa_demandeur_financement_moyen_autre" value="' . esc_attr( $visa_demandeur_financement_moyen_autre ) . '" placeholder="Précisez" style="width:100%; margin-top:4px;">
			</label>';
			echo '</div>'; // .checkbox-grid
			echo '</div>'; // .subsection demandeur

			// Section Garant
			echo '<div class="subsection" data-for="garant">';
			echo '<p><strong>Précisions sur le garant :</strong></p>';
			echo '<label><input type="radio" name="visa_financement_garant" value="garant_vise" ' . checked( $visa_financement_garant, 'garant_vise', false ) . '> Visé dans la case 30 ou 31</label>';
			echo '<label><input type="radio" name="visa_financement_garant" value="garant_autre" ' . checked( $visa_financement_garant, 'garant_autre', false ) . '> Autre
				<input type="text" name="visa_garant_autre_detail" value="' . esc_attr( $visa_garant_autre_detail ) . '" placeholder="Détails" style="width:100%; margin-top:4px;">
			</label>';

			echo '<p><strong>Moyens de subsistance :</strong></p>';
			echo '<div class="checkbox-grid">';
			foreach ( [ 'liquide' => 'Argent liquide', 'finance' => 'Tous frais financés', 'hebergement' => 'Hébergement fourni', 'transport' => 'Transport prépayé' ] as $val => $label ) {
				echo '<label><input type="checkbox" name="visa_garant_financement_moyen[]" value="' . $val . '" ' . ( in_array( $val, (array) $visa_garant_financement_moyen ) ? 'checked' : '' ) . '> ' . $label . '</label>';
			}
			echo '<label><input type="checkbox" name="visa_garant_financement_moyen[]" value="autre" ' . ( in_array( 'autre', (array) $visa_garant_financement_moyen ) ? 'checked' : '' ) . '> Autre
				<input type="text" name="visa_garant_financement_moyen_autre" value="' . esc_attr( $visa_garant_financement_moyen_autre ) . '" placeholder="Précisez" style="width:100%; margin-top:4px;">
			</label>';
			echo '</div>'; // .checkbox-grid
			echo '</div>'; // .subsection garant

			echo '</fieldset>';

			// 33. Nom et prénom de la personne qui remplit le formulaire
			echo '<p><label><strong>33. Nom et prénom de la personne qui remplit le formulaire, si elle n’est pas le demandeur :</strong><br>
				<input type="text" name="visa_remplisseur" value="' . esc_attr( $visa_remplisseur ) . '" style="width:100%;">
			</label></p>';

			// Adresse et e-mail du remplisseur
			echo '<p><label><strong>Adresse et adresse électronique de la personne qui remplit le formulaire :</strong><br>
				<textarea name="visa_adresse_remplisseur" style="width:100%;">' . esc_textarea( $visa_adresse_remplisseur ) . '</textarea>
			</label></p>';

			// Numéro de téléphone du remplisseur
			echo '<p><label><strong>Numéro de téléphone :</strong><br>
				<input type="text" name="visa_num_remplisseur" value="' . esc_attr( $visa_num_remplisseur ) . '" style="width:100%;">
			</label></p>';
		} else {
			// 1. Nom(s)
			echo '<p><label><strong>1. Nom(s)</strong><span class="required">*</span><br>
				<input type="text" name="visa_full_name" value="' . esc_attr( $full_name ) . '" style="width:100%;" required>
			</label></p>';

			// 2. Nom(s) de famille antérieur(s)
			echo '<p><label><strong>2. Nom(s) de famille antérieur(s)</strong><br>
				<input type="text" name="visa_nom_famille" value="' . esc_attr( $visa_nom_famille ) . '" style="width:100%;">
			</label></p>';

			// 3. Prénom(s)
			echo '<p><label><strong>3. Prénom(s)</strong><span class="required">*</span><br>
				<input type="text" name="visa_prenom" value="' . esc_attr( $visa_prenom ) . '" style="width:100%;" required>
			</label></p>';

			// 4. Date de naissance
			echo '<p><label><strong>4. Date de naissance (jj-mm-aaaa)</strong><span class="required">*</span><br>
				<input type="date" name="visa_birth_date" value="' . esc_attr( $birth_date ) . '" style="width:100%;" required>
			</label></p>';

			// 5. Lieu de naissance
			echo '<p><label><strong>5. Lieu de naissance</strong><span class="required">*</span><br>
				<input type="text" name="visa_lieu_naiss" value="' . esc_attr( $visa_lieu_naiss ) . '" style="width:100%;" required>
			</label></p>';

			// 6. Pays de naissance
			echo '<p><label><strong>6. Pays de naissance</strong><span class="required">*</span><br>';
			echo '<select id="visa_pays_naiss" name="visa_pays_naiss" style="width:100%;" required>';
			echo '<option value="">' . esc_html__( '-- Sélectionnez un pays --', 'text-domain' ) . '</option>';

			foreach ( $countries as $country ) {
				echo '<option value="' . esc_attr( $country ) . '"' 
					. selected( $visa_pays_naiss, $country, false ) 
					. '>' . esc_html( $country ) . '</option>';
			}

			echo '</select></label></p>';

			// 7. Nationalité actuelle
			echo '<p><label><strong>7. Nationalité actuelle</strong><span class="required">*</span><br>';
			echo '<select name="visa_nationalite" style="width:100%;" required>';
			echo '<option value="">' . esc_html__( '-- Sélectionnez une nationalité --', 'text-domain' ) . '</option>';

			foreach ( $nationalities as $code => $label ) {
				echo '<option value="' . esc_attr( $code ) . '"' 
					. selected( $visa_nationalite, $code, false ) 
					. '>' . esc_html( $label ) . '</option>';
			}
			echo '</select></label></p>';

			// 8. Nationalité à la naissance, si différente
			echo '<p><label><strong>Nationalité à la naissance, si différente</strong><br>';
			echo '<select name="visa_nationalite_diff" style="width:100%;">';
			echo '<option value="">' . esc_html__( '-- Sélectionnez une nationalité --', 'text-domain' ) . '</option>';

			foreach ( $nationalities as $code => $label ) {
				echo '<option value="' . esc_attr( $code ) . '"' 
					. selected( $visa_nationalite_diff, $code, false ) 
					. '>' . esc_html( $label ) . '</option>';
			}

			echo '</select></label></p>';

			// 9. Sexe
			echo '<p><label><strong>8. Sexe</strong><span class="required">*</span><br>
				<input type="radio" name="visa_sexe" value="masculin"' . checked( $visa_sexe, 'masculin', false ) . '> Masculin
				<input type="radio" name="visa_sexe" value="feminin"' . checked( $visa_sexe, 'feminin', false ) . '> Féminin
			</label></p>';

			// 10. État civil
			echo '<p><label><strong>9. État Civil</strong><span class="required">*</span><br>
				<input type="radio" name="visa_etat_civil" value="celibataire"' . checked( $visa_etat_civil, 'celibataire', false ) . '> Célibataire
				<input type="radio" name="visa_etat_civil" value="marie"' . checked( $visa_etat_civil, 'marie', false ) . '> Marié(e)
				<input type="radio" name="visa_etat_civil" value="separe"' . checked( $visa_etat_civil, 'separe', false ) . '> Séparé(e)
				<input type="radio" name="visa_etat_civil" value="divorce"' . checked( $visa_etat_civil, 'divorce', false ) . '> Divorcé(e)
				<input type="radio" name="visa_etat_civil" value="veuf"' . checked( $visa_etat_civil, 'veuf', false ) . '> Veuf(Veuve)
				<input type="radio" name="visa_etat_civil" value="autre"' . checked( $visa_etat_civil, 'autre', false ) . '> Autre
				<input type="text" name="visa_etat_civil_autre" value="' . esc_attr( $visa_etat_civil_autre ) . '" style="width:100%; margin-top:4px;">
			</label></p>';

			// Afficher les tuteurs légaux structurés (si présents)
			if ( $visa_nom_tuteur_legal_1 || $visa_prenom_tuteur_legal_1 || $visa_adresse_tuteur_legal_1 ) {
				echo '<div style="border:1px solid #eee;padding:10px;margin:8px 0;background:#fafafa;">';
				echo '<strong>Tuteur légal n°1</strong><br/>';
				echo '<label>Nom: <input type="text" name="visa_nom_tuteur_legal_1" value="' . esc_attr( $visa_nom_tuteur_legal_1 ) . '" style="width:100%;"></label><br/>';
				echo '<label>Prénom: <input type="text" name="visa_prenom_tuteur_legal_1" value="' . esc_attr( $visa_prenom_tuteur_legal_1 ) . '" style="width:100%;"></label><br/>';
				echo '<label>Adresse: <input type="text" name="visa_adresse_tuteur_legal_1" value="' . esc_attr( $visa_adresse_tuteur_legal_1 ) . '" style="width:100%;"></label><br/>';
				echo '<label>Code postal: <input type="text" name="visa_code_postal_tuteur_legal_1" value="' . esc_attr( $visa_code_postal_tuteur_legal_1 ) . '" style="width:49%; margin-right:2%;"></label><br/>';
				echo '<label>Ville: <input type="text" name="visa_ville_tuteur_legal_1" value="' . esc_attr( $visa_ville_tuteur_legal_1 ) . '" style="width:49%;"></label><br/>';
				echo '<label>Pays: <input type="text" name="visa_pays_tuteur_legal_1" value="' . esc_attr( $visa_pays_tuteur_legal_1 ) . '" style="width:49%; margin-right:2%;"></label><br/>';
				echo '<label>Téléphone: <input type="text" name="visa_telephone_tuteur_legal_1" value="' . esc_attr( $visa_telephone_tuteur_legal_1 ) . '" style="width:49%;"></label><br/>';
				echo '<label>Email: <input type="email" name="visa_email_tuteur_legal_1" value="' . esc_attr( $visa_email_tuteur_legal_1 ) . '" style="width:100%;"></label><br/>';
				echo '<label>Nationalité: <input type="text" name="visa_nationalite_tuteur_legal_1" value="' . esc_attr( $visa_nationalite_tuteur_legal_1 ) . '" style="width:100%;"></label><br/>';
				echo '<label>Statut: <select name="visa_statut_tuteur_legal_1" style="width:100%;"><option value="">-- Sélectionnez un statut --</option><option value="Apatride" ' . selected( $visa_statut_tuteur_legal_1, 'Apatride', false ) . '>Apatride</option><option value="Réfugié 1946/51" ' . selected( $visa_statut_tuteur_legal_1, 'Réfugié 1946/51', false ) . '>Réfugié 1946/51</option><option value="Réfugié hs conv" ' . selected( $visa_statut_tuteur_legal_1, 'Réfugié hs conv', false ) . '>Réfugié hs conv</option></select></label>';
				echo '</div>';
			}

			if ( $visa_nom_tuteur_legal_2 || $visa_prenom_tuteur_legal_2 || $visa_adresse_tuteur_legal_2 ) {
				echo '<div style="border:1px solid #eee;padding:10px;margin:8px 0;background:#fafafa;">';
				echo '<strong>Tuteur légal n°2</strong><br/>';
				echo '<label>Nom: <input type="text" name="visa_nom_tuteur_legal_2" value="' . esc_attr( $visa_nom_tuteur_legal_2 ) . '" style="width:100%;"></label><br/>';
				echo '<label>Prénom: <input type="text" name="visa_prenom_tuteur_legal_2" value="' . esc_attr( $visa_prenom_tuteur_legal_2 ) . '" style="width:100%;"></label><br/>';
				echo '<label>Adresse: <input type="text" name="visa_adresse_tuteur_legal_2" value="' . esc_attr( $visa_adresse_tuteur_legal_2 ) . '" style="width:100%;"></label><br/>';
				echo '<label>Code postal: <input type="text" name="visa_code_postal_tuteur_legal_2" value="' . esc_attr( $visa_code_postal_tuteur_legal_2 ) . '" style="width:49%; margin-right:2%;"></label><br/>';
				echo '<label>Ville: <input type="text" name="visa_ville_tuteur_legal_2" value="' . esc_attr( $visa_ville_tuteur_legal_2 ) . '" style="width:49%;"></label><br/>';
				echo '<label>Pays: <input type="text" name="visa_pays_tuteur_legal_2" value="' . esc_attr( $visa_pays_tuteur_legal_2 ) . '" style="width:49%; margin-right:2%;"></label><br/>';
				echo '<label>Téléphone: <input type="text" name="visa_telephone_tuteur_legal_2" value="' . esc_attr( $visa_telephone_tuteur_legal_2 ) . '" style="width:49%;"></label><br/>';
				echo '<label>Email: <input type="email" name="visa_email_tuteur_legal_2" value="' . esc_attr( $visa_email_tuteur_legal_2 ) . '" style="width:100%;"></label><br/>';
				echo '<label>Nationalité: <input type="text" name="visa_nationalite_tuteur_legal_2" value="' . esc_attr( $visa_nationalite_tuteur_legal_2 ) . '" style="width:100%;"></label>';
				echo '<label>Statut: <select name="visa_statut_tuteur_legal_2" style="width:100%;"><option value="">-- Sélectionnez un statut --</option><option value="Apatride" ' . selected( $visa_statut_tuteur_legal_2, 'Apatride', false ) . '>Apatride</option><option value="Réfugié 1946/51" ' . selected( $visa_statut_tuteur_legal_2, 'Réfugié 1946/51', false ) . '>Réfugié 1946/51</option><option value="Réfugié hs conv" ' . selected( $visa_statut_tuteur_legal_2, 'Réfugié hs conv', false ) . '>Réfugié hs conv</option></select></label>';
				echo '</div>';
			}
			
			// 12. Numéro national d’identité
			echo '<p><label><strong>11. Numéro national d’identité, le cas échéant</strong><br>
				<input type="text" name="visa_num_national_identite" value="' . esc_attr( $visa_num_national_identite ) . '" style="width:100%;">
			</label></p>';

			// 13. Type de document de voyage
			echo '<p><label><strong>12. Type de document de voyage</strong><span class="required">*</span><br>
				<input type="radio" name="visa_doc_voyage" value="passeport_ordinaire"' . checked( $visa_doc_voyage, 'passeport_ordinaire', false ) . '> Passeport ordinaire
				<input type="radio" name="visa_doc_voyage" value="passeport_diplomatique"' . checked( $visa_doc_voyage, 'passeport_diplomatique', false ) . '> Passeport diplomatique
				<input type="radio" name="visa_doc_voyage" value="passeport_service"' . checked( $visa_doc_voyage, 'passeport_service', false ) . '> Passeport de service
				<input type="radio" name="visa_doc_voyage" value="passeport_officiel"' . checked( $visa_doc_voyage, 'passeport_officiel', false ) . '> Passeport officiel
				<input type="radio" name="visa_doc_voyage" value="passeport_special"' . checked( $visa_doc_voyage, 'passeport_special', false ) . '> Passeport spécial
				<input type="radio" name="visa_doc_voyage" value="autre"' . checked( $visa_doc_voyage, 'autre', false ) . '> Autre
				<input type="text" name="visa_doc_voyage_autre" value="' . esc_attr( $visa_doc_voyage_autre ) . '" style="width:100%; margin-top:4px;">
			</label></p>';

			// 14. Numéro du document de voyage
			echo '<p><label><strong>13. Numéro du document de voyage</strong><span class="required">*</span><br>
				<input type="text" name="visa_num_document" value="' . esc_attr( $visa_num_document ) . '" style="width:100%;" required>
			</label></p>';

			// 15. Date de délivrance
			echo '<p><label><strong>14. Date de délivrance</strong><span class="required">*</span><br>
				<input type="date" name="visa_date_delivrance" value="' . esc_attr( $visa_date_delivrance ) . '" style="width:100%;" required>
			</label></p>';

			// 16. Date d’expiration
			echo '<p><label><strong>15. Date d’expiration</strong><span class="required">*</span><br>
				<input type="date" name="visa_date_expiration" value="' . esc_attr( $visa_date_expiration ) . '" style="width:100%;" required>
			</label></p>';

			// 17. Délivré par (pays)
			echo '<p><label><strong>16. Délivré par (pays)</strong><span class="required">*</span><br>';
			echo '<select id="visa_delivre_par" name="visa_delivre_par" style="width:100%;" required>';
			echo '<option value="">' . esc_html__( '-- Sélectionnez un pays --', 'text-domain' ) . '</option>';

			foreach ( $countries as $country ) {
				echo '<option value="' . esc_attr( $country ) . '"' 
					. selected( $visa_delivre_par, $country, false ) 
					. '>' . esc_html( $country ) . '</option>';
			}

			echo '</select></label></p>';

			// 18. Adresse du domicile
			echo '<p><label><strong>17. Adresse du domicile</strong><span class="required">*</span><br>
				<input type="text" name="visa_adresse" value="' . esc_attr( $visa_adresse ) . '" style="width:100%;" required>
			</label></p>';

			// 19. Adresse électronique
			echo '<p><label><strong>18. Adresse électronique</strong><span class="required">*</span><br>
				<input type="email" name="visa_mail" value="' . esc_attr( $visa_mail ) . '" style="width:100%;" required>
			</label></p>';

			// 20. Numéro(s) de téléphone
			echo '<p><label><strong>19. Numéro(s) de téléphone</strong><span class="required">*</span><br>
				<input type="text" name="visa_phone" value="' . esc_attr( $visa_phone ) . '" style="width:100%;" required>
			</label></p>';

			// 21. Résidence dans un pays autre
			echo '<p><label><strong>20. Résidence hors nationalité</strong><br>
				<label>Numéro du titre de séjour</label><br>
				<input type="text" name="visa_num_resident" value="' . esc_attr( $visa_num_resident ) . '" style="width:100%;"><br>
				<label>Date de délivrance</label><br>
				<input type="date" name="visa_autre_date_delivrance" value="' . esc_attr( $visa_autre_date_delivrance ) . '" style="width:100%;"><br>
				<label>Date d\'expiration</label><br>
				<input type="date" name="visa_autre_date_expiration" value="' . esc_attr( $visa_autre_date_expiration ) . '" style="width:100%;">
			</label></p>';

			// 22. Activité professionnelle actuelle
			echo '<p><label><strong>21. Activité professionnelle actuelle</strong><span class="required">*</span></label><br>';
			echo '<select name="visa_profession" required>';
						echo '<option value="">&nbsp;</option>';
						echo '<option value="Agriculteur"' . selected($visa_profession, 'Agriculteur') . '>Agriculteur</option>';
						echo '<option value="Architecte"' . selected($visa_profession, 'Architecte') . '>Architecte</option>';
						echo '<option value="Artisan"' . selected($visa_profession, 'Artisan') . '>Artisan</option>';
						echo '<option value="Artiste"' . selected($visa_profession, 'Artiste') . '>Artiste</option>';
						echo '<option value="Autre"' . selected($visa_profession, 'Autre') . '>Autre</option>';
						echo '<option value="Autre technicien"' . selected($visa_profession, 'Autre technicien') . '>Autre technicien</option>';
						echo '<option value="Banquier"' . selected($visa_profession, 'Banquier') . '>Banquier</option>';
						echo '<option value="Cadre d\'entreprise"' . selected($visa_profession, 'Cadre d\'entreprise') . '>Cadre d\'entreprise</option>';
						echo '<option value="Chauffeur, routier"' . selected($visa_profession, 'Chauffeur, routier') . '>Chauffeur, routier</option>';
						echo '<option value="Chef d\'entreprise"' . selected($visa_profession, 'Chef d\'entreprise') . '>Chef d\'entreprise</option>';
						echo '<option value="Chercheur, scientifique"' . selected($visa_profession, 'Chercheur, scientifique') . '>Chercheur, scientifique</option>';
						echo '<option value="Chimiste"' . selected($visa_profession, 'Chimiste') . '>Chimiste</option>';
						echo '<option value="Chômeur"' . selected($visa_profession, 'Chômeur') . '>Chômeur</option>';
						echo '<option value="Clergé, religieux"' . selected($visa_profession, 'Clergé, religieux') . '>Clergé, religieux</option>';
						echo '<option value="Commerçant"' . selected($visa_profession, 'Commerçant') . '>Commerçant</option>';
						echo '<option value="Diplomate"' . selected($visa_profession, 'Diplomate') . '>Diplomate</option>';
						echo '<option value="Electronicien"' . selected($visa_profession, 'Electronicien') . '>Electronicien</option>';
						echo '<option value="Elève, Etudiant, stagiaire"' . selected($visa_profession, 'Elève, Etudiant, stagiaire') . '>Elève, Etudiant, stagiaire</option>';
						echo '<option value="Employé"' . selected($visa_profession, 'Employé') . '>Employé</option>';
						echo '<option value="Employé prive au service de diplomate"' . selected($visa_profession, 'Employé prive au service de diplomate') . '>Employé prive au service de diplomate</option>';
						echo '<option value="Enseignant"' . selected($visa_profession, 'Enseignant') . '>Enseignant</option>';
						echo '<option value="Fonctionnaire"' . selected($visa_profession, 'Fonctionnaire') . '>Fonctionnaire</option>';
						echo '<option value="Homme politique"' . selected($visa_profession, 'Homme politique') . '>Homme politique</option>';
						echo '<option value="Informaticien"' . selected($visa_profession, 'Informaticien') . '>Informaticien</option>';
						echo '<option value="Journaliste"' . selected($visa_profession, 'Journaliste') . '>Journaliste</option>';
						echo '<option value="Magistrat"' . selected($visa_profession, 'Magistrat') . '>Magistrat</option>';
						echo '<option value="Marin"' . selected($visa_profession, 'Marin') . '>Marin</option>';
						echo '<option value="Mode, cosmétique"' . selected($visa_profession, 'Mode, cosmétique') . '>Mode, cosmétique</option>';
						echo '<option value="Ouvrier"' . selected($visa_profession, 'Ouvrier') . '>Ouvrier</option>';
						echo '<option value="Personnel de service, administratif ou technique (postes dipl./cons.)"' . selected($visa_profession, 'Personnel de service, administratif ou technique (postes dipl./cons.)') . '>Personnel de service, administratif ou technique (postes dipl./cons.)</option>';
						echo '<option value="Policier, militaire"' . selected($visa_profession, 'Policier, militaire') . '>Policier, militaire</option>';
						echo '<option value="Profession juridique"' . selected($visa_profession, 'Profession juridique') . '>Profession juridique</option>';
						echo '<option value="Profession libérale"' . selected($visa_profession, 'Profession libérale') . '>Profession libérale</option>';
						echo '<option value="Profession médicale et paramédicale"' . selected($visa_profession, 'Profession médicale et paramédicale') . '>Profession médicale et paramédicale</option>';
						echo '<option value="Retraite"' . selected($visa_profession, 'Retraite') . '>Retraite</option>';
						echo '<option value="Sans profession"' . selected($visa_profession, 'Sans profession') . '>Sans profession</option>';
						echo '<option value="Sportif"' . selected($visa_profession, 'Sportif') . '>Sportif</option>';
				echo '</select>';
			echo '</p>';
			echo '<p><label><strong>Secteur d\'activité</strong><br>';
            echo '<select name="visa_secteur_activite" style="width:100%;" required>';
            echo '<option value="">-- Sélectionnez un secteur d\'activité --</option>';
            echo '<option value="Activités de services administratifs et de soutien"' . selected( $secteur_activite, 'Activités de services administratifs et de soutien', false ) . '>Activités de services administratifs et de soutien</option>';
            echo '<option value="Activités des ménages en tant qu\'employeurs; activités indifférenciées des ménages en tant que producteurs de biens et services pour usage propre"' . selected( $secteur_activite, 'Activités des ménages en tant qu\'employeurs; activités indifférenciées des ménages en tant que producteurs de biens et services pour usage propre', false ) . '>Activités des ménages en tant qu\'employeurs; activités indifférenciées des ménages en tant que producteurs de biens et services pour usage propre</option>';
            echo '<option value="Activités extra-territoriales"' . selected( $secteur_activite, 'Activités extra-territoriales', false ) . '>Activités extra-territoriales</option>';
            echo '<option value="Activités financières et d\'assurance"' . selected( $secteur_activite, 'Activités financières et d\'assurance', false ) . '>Activités financières et d\'assurance</option>';
            echo '<option value="Activités immobilières"' . selected( $secteur_activite, 'Activités immobilières', false ) . '>Activités immobilières</option>';
            echo '<option value="Activités spécialisées, scientifiques et techniques"' . selected( $secteur_activite, 'Activités spécialisées, scientifiques et techniques', false ) . '>Activités spécialisées, scientifiques et techniques</option>';
            echo '<option value="Administration publique"' . selected( $secteur_activite, 'Administration publique', false ) . '>Administration publique</option>';
            echo '<option value="Agriculture, sylviculture et pêche"' . selected( $secteur_activite, 'Agriculture, sylviculture et pêche', false ) . '>Agriculture, sylviculture et pêche</option>';
            echo '<option value="Arts, spectacles et activités récréatives"' . selected( $secteur_activite, 'Arts, spectacles et activités récréatives', false ) . '>Arts, spectacles et activités récréatives</option>';
            echo '<option value="Autres activités"' . selected( $secteur_activite, 'Autres activités', false ) . '>Autres activités</option>';
            echo '<option value="Autres activités de services"' . selected( $secteur_activite, 'Autres activités de services', false ) . '>Autres activités de services</option>';
            echo '<option value="Commerce; réparation d\'automobiles et de motocycles"' . selected( $secteur_activite, 'Commerce; réparation d\'automobiles et de motocycles', false ) . '>Commerce; réparation d\'automobiles et de motocycles</option>';
            echo '<option value="Construction"' . selected( $secteur_activite, 'Construction', false ) . '>Construction</option>';
            echo '<option value="Enseignement"' . selected( $secteur_activite, 'Enseignement', false ) . '>Enseignement</option>';
            echo '<option value="Hébergement et restauration"' . selected( $secteur_activite, 'Hébergement et restauration', false ) . '>Hébergement et restauration</option>';
            echo '<option value="Industrie manufacturière"' . selected( $secteur_activite, 'Industrie manufacturière', false ) . '>Industrie manufacturière</option>';
            echo '<option value="Industries extractives"' . selected( $secteur_activite, 'Industries extractives', false ) . '>Industries extractives</option>';
            echo '<option value="Information et communication"' . selected( $secteur_activite, 'Information et communication', false ) . '>Information et communication</option>';
            echo '<option value="Production et distribution d\'eau; assainissement, gestion des déchets et dépollution"' . selected( $secteur_activite, 'Production et distribution d\'eau; assainissement, gestion des déchets et dépollution', false ) . '>Production et distribution d\'eau; assainissement, gestion des déchets et dépollution</option>';
            echo '<option value="Production et distribution d\'électricité, de gaz, de vapeur et d\'air conditionné"' . selected( $secteur_activite, 'Production et distribution d\'électricité, de gaz, de vapeur et d\'air conditionné', false ) . '>Production et distribution d\'électricité, de gaz, de vapeur et d\'air conditionné</option>';
            echo '<option value="Santé humaine et action sociale"' . selected( $secteur_activite, 'Santé humaine et action sociale', false ) . '>Santé humaine et action sociale</option>';
            echo '<option value="Transports et entreposage"' . selected( $secteur_activite, 'Transports et entreposage', false ) . '>Transports et entreposage</option>';
            
            echo '</select></label></p>';

			// Situation professionnelle
			echo '
            <p><label><strong>Situation professionnelle</strong></label><br>
            <select name="visa_situation_professionnelle" style="width:100%;" required>
              <option value="">-- Sélectionnez une situation professionnelle --</option>
              <option value="En activité" ' . selected( $visa_situation_professionnelle, 'En activité', false ) . '>En activité</option>
              <option value="Sans profession" ' . selected( $visa_situation_professionnelle, 'Sans profession', false ) . '>Sans profession</option>
              <option value="Chômeur" ' . selected( $visa_situation_professionnelle, 'Chômeur', false ) . '>Chômeur</option>
              <option value="Retraité" ' . selected( $visa_situation_professionnelle, 'Retraité', false ) . '>Retraité</option>
              <option value="Etudiant" ' . selected( $visa_situation_professionnelle, 'Etudiant', false ) . '>Étudiant</option>
            </select>
            </p>';

			// 23. Employeur / établissement
			echo '<p><label><strong>22. Employeur / établissement</strong><span class="required">*</span><br>
				<textarea name="visa_employeur" style="width:100%;" required>' . esc_textarea( $visa_employeur ) . '</textarea>
			</label></p>';

			// 24. Motif de la demande
			echo '<p><label><strong>23. Motif de la demande</strong><br>
                <input type="radio" name="visa_objet" value="etudes"' . checked( $visa_objet, 'etudes', false ) . '> Études
                <input type="radio" name="visa_objet" value="installation_familiale_majeur"' . checked( $visa_objet, 'installation_familiale_majeur', false ) . '> Installation familiale ou privée (majeur)
                <input type="radio" name="visa_objet" value="installation_familiale_mineur"' . checked( $visa_objet, 'installation_familiale_mineur', false ) . '> Installation familiale ou privée (mineur)
                <input type="radio" name="visa_objet" value="fonctions_officielles"' . checked( $visa_objet, 'fonctions_officielles', false ) . '> Prise de fonctions officielles
                <input type="radio" name="visa_objet" value="stage_salarie"' . checked( $visa_objet, 'stage_salarie', false ) . '> Stage salarié
                <input type="radio" name="visa_objet" value="travailler"' . checked( $visa_objet, 'travailler', false ) . '> Travailler
                <input type="radio" name="visa_objet" value="visa_retour"' . checked( $visa_objet, 'visa_retour', false ) . '> Visa de retour
                <input type="radio" name="visa_objet" value="visiteur"' . checked( $visa_objet, 'visiteur', false ) . '> Visiteur
                <input type="radio" name="visa_objet" value="autre"' . checked( $visa_objet, 'autre', false ) . '> Autre<br>
                <input type="text" name="visa_objet_autre" value="' . esc_attr( $visa_objet_autre ) . '" style="width:100%; margin-top:4px;">
            </label></p>';

			// 25. Infos employeur invitant
			echo '<p><label><strong>24. Infos employeur/accueil/invitant</strong><span class="required">*</span><br>
				<textarea name="visa_info_employeur" style="width:100%;" required>' . esc_textarea( $visa_info_employeur ) . '</textarea>
			</label></p>';

			// 26. Adresse en France
			echo '<p><label><strong>25. Adresse en France pendant le séjour</strong><span class="required">*</span><br>
				<input type="text" name="visa_adresse_sejour" value="' . esc_attr( $visa_adresse_sejour ) . '" style="width:100%;" required>
			</label></p>';

			// 27. Date d'entrée prévue
			echo '<p><label><strong>26. Date d\'entrée prévue</strong><span class="required">*</span><br>
				<input type="date" name="visa_arrival_date" value="' . esc_attr( $visa_arrival_date ) . '" style="width:100%;" required>
			</label></p>';

			// 28. Durée prévue du séjour
			echo '<p><label><strong>27. Durée prévue du séjour</strong><span class="required">*</span><br>
				<input type="radio" name="visa_duree" value="entre_3_et_6_mois"' . checked( $visa_duree, 'entre_3_et_6_mois', false ) . '> Entre 3 et 6 mois
				<input type="radio" name="visa_duree" value="entre_6_mois_et_un_an"' . checked( $visa_duree, 'entre_6_mois_et_un_an', false ) . '> Entre 6 mois et un an
				<input type="radio" name="visa_duree" value="superieur_a_un_an"' . checked( $visa_duree, 'superieur_a_un_an', false ) . '> Supérieure à un an
			</label></p>';

			// 29. Famille en séjour (tableau avec boutons Ajouter / Supprimer)
			echo '<p><strong>Si vous séjournez en famille, indiquez :</strong><br>
			<table id="famille-table-admin" border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">
			<thead>
				<tr>
				<th>Lien de parenté</th>
				<th>Nom(s), prénom(s)</th>
				<th>Date de naissance (jj/mm/aa)</th>
				<th>Nationalité</th>
				<th>Action</th>
				</tr>
			</thead>
			<tbody>';

			foreach ( $members as $member ) {
				echo '<tr>';
				echo '<td><input type="text" name="lien_parent[]" value="' . esc_attr( $member['lien'] ) . '" style="width:100%;"></td>';
				echo '<td><input type="text" name="nom_prenom[]" value="' . esc_attr( $member['nom'] ) . '" style="width:100%;"></td>';
				echo '<td><input type="date" name="date_naissance[]" value="' . esc_attr( $member['naissance'] ) . '" style="width:100%;"></td>';
				echo '<td><input type="text" name="nationalite_famille[]" value="' . esc_attr( $member['nationalite'] ) . '" style="width:100%;"></td>';
				echo '<td><button type="button" class="remove-row button">Supprimer</button></td>';
				echo '</tr>';
			}

			echo '</tbody>
			</table><br>
			<button type="button" id="add-row-admin" class="button">Ajouter une ligne</button>
			';

			// JS inline pour gérer Ajout / Suppression
			echo '<script>
			jQuery(function($){
			var table = $("#famille-table-admin");

			// Ajouter une ligne
			$("#add-row-admin").on("click", function(){
				var $first = table.find("tbody tr:first").clone();
				$first.find("input").val("");
				table.find("tbody").append($first);
			});

			// Supprimer la ligne cliquée
			table.on("click", ".remove-row", function(){
				var $tbody = table.find("tbody");
				if ( $tbody.find("tr").length > 1 ) {
				$(this).closest("tr").remove();
				} else {
				$tbody.find("tr:first input").val("");
				}
			});
			});
			</script>';

			// 30. Moyens d'existence
			echo '<p><label><strong>29. Moyens d\'existence en France</strong><span class="required">*</span><br>
				<input type="text" name="visa_moyens_existence" value="' . esc_attr( $visa_moyens_existence ) . '" style="width:100%;" required>
			</label></p>';

			// 31. Bourse
			echo '<p><label><strong>Serez-vous titulaire d\'une bourse ?</strong><br>
				<input type="radio" name="visa_bourse" value="non"' . checked( $visa_bourse, 'non', false ) . '> Non
				<input type="radio" name="visa_bourse" value="oui"' . checked( $visa_bourse, 'oui', false ) . '> Oui<br>
				<input type="text" name="visa_bourse_detail" value="' . esc_attr( $visa_bourse_detail ) . '" placeholder="Détails" style="width:100%; margin-top:4px;">
			</label></p>';

			// 32. Prise en charge
			echo '<p><label><strong>30. Prise en charge par une ou plusieurs personnes</strong><br>
				<input type="radio" name="visa_prise_en_charge" value="non"' . checked( $visa_prise_en_charge, 'non', false ) . '> Non
				<input type="radio" name="visa_prise_en_charge" value="oui"' . checked( $visa_prise_en_charge, 'oui', false ) . '> Oui<br>
				<textarea name="visa_info_prise_en_charge" style="width:100%;" placeholder="Détails">' . esc_textarea( $visa_info_prise_en_charge ) . '</textarea>
			</label></p>';

			// 33. Famille résidant en France
			echo '<p><label><strong>31. Membres de famille en France</strong><br>
				<input type="radio" name="visa_famille_resident" value="non"' . checked( $visa_famille_resident, 'non', false ) . '> Non
				<input type="radio" name="visa_famille_resident" value="oui"' . checked( $visa_famille_resident, 'oui', false ) . '> Oui<br>
				<textarea name="visa_info_famille_resident" style="width:100%;" placeholder="Détails">' . esc_textarea( $visa_info_famille_resident ) . '</textarea>
			</label></p>';

			// 34. Résidence antérieure >3 mois
			echo '<p><label><strong>32. Avez-vous déjà résidé plus de trois mois consécutifs en France ?</strong><br>
				<input type="radio" name="visa_duree_anterieure" value="non"' . checked( $visa_duree_anterieure, 'non', false ) . '> Non
				<input type="radio" name="visa_duree_anterieure" value="oui"' . checked( $visa_duree_anterieure, 'oui', false ) . '> Oui<br>
				<textarea name="visa_info_duree_anterieure" style="width:100%;" placeholder="Dates et motifs">' . esc_textarea( $visa_info_duree_anterieure ) . '</textarea><br>
				<textarea name="visa_adresse_duree_anterieure" style="width:100%;" placeholder="Adresse(s)">' . esc_textarea( $visa_adresse_duree_anterieure ) . '</textarea>
			</label></p>';
		}
    }
    
    /**
	 * Mailing
	 */
	 
	public function add_visa_email_metabox() {
        add_meta_box(
            'visa_admin_email_box',
            'Contacter le demandeur',
            [$this, 'render_visa_email_box'],
            'visa_request',
            'side',
            'default'
        );
    }

    public function render_visa_email_box($post) {
        $email = get_post_meta($post->ID, 'level1_email', true);
				$full_name    = get_post_meta( $post->ID, 'visa_full_name', true );
				$visa_prenom  = get_post_meta( $post->ID, 'visa_prenom', true );

        if (!$email) {
            echo "<p><em>Adresse email introuvable.</em></p>";
            return;
        }

        // Pas de <form> imbriqué : on dépose uniquement les champs + le bouton
        echo '<input type="hidden" name="visa_email_action" value="send_visa_email">';
        echo '<input type="hidden" name="post_id" value="'. esc_attr($post->ID) .'">';
        echo '<input type="hidden" name="visa_email_nonce" value="'. esc_attr(wp_create_nonce('visa_email_' . $post->ID)) .'">';

        echo '<p><strong>À :</strong><br>'. esc_html($email) .'</p>';

        echo '<p><label>Sujet :<br>
                <input type="text" name="visa_email_subject" style="width:100%;" value="#'. esc_attr($post->ID) .' - '. esc_attr($full_name) .' '. esc_attr($visa_prenom) .' - ">
              </label></p>';

        echo '<p><label>Message :<br>
                <textarea name="visa_email_body" rows="5" style="width:100%;"></textarea>
              </label></p>';

        echo '<p><label>
                <input type="checkbox" name="visa_email_include_docs" value="1">
                Joindre les documents envoyés par le demandeur
              </label></p>';

        // Bouton à part, qui cible admin-post.php
        echo '<p><button type="submit"
                      class="button button-primary"
                      formmethod="post"
                      name="action"
                        value="send_visa_email"
                      formaction="'. esc_url(admin_url('admin-post.php')) .'">
                    Envoyer l’e-mail
              </button></p>';
              
        // Historique
        $history = get_post_meta($post->ID, 'visa_email_history', true);
        if (!empty($history) && is_array($history)) {
            echo '<hr><h4>Historique des emails envoyés</h4>';
            echo '<ul style="max-height:200px; overflow:auto; padding-left:20px;">';
            
            $history = array_reverse($history);
            
            foreach ($history as $entry) {
                echo '<li>';
                echo '<strong>Date :</strong> ' . esc_html($entry['date']) . '<br>';
                echo '<strong>Sujet :</strong> ' . esc_html($entry['subject']) . '<br>';
                echo '<strong>Statut :</strong> ' . esc_html($entry['status']) . '<br>';
                echo '<em>' . esc_html($entry['body_excerpt']) . '</em>';
                echo '</li><hr>';
            }
            echo '</ul>';
        } else {
            echo '<p><em>Aucun email envoyé pour ce demandeur.</em></p>';
        }
    }

    public function handle_visa_email_send() {
			$post_id = intval($_POST['post_id'] ?? 0);
			if (!$post_id || !current_user_can('edit_post', $post_id)) {
					wp_die('Accès non autorisé.');
			}
			if (!wp_verify_nonce($_POST['visa_email_nonce'] ?? '', 'visa_email_' . $post_id)) {
					wp_die('Échec de vérification de sécurité.');
			}

			$email       = get_post_meta($post_id, 'level1_email', true);
			$subject     = sanitize_text_field($_POST['visa_email_subject'] ?? '');
			$body        = sanitize_textarea_field($_POST['visa_email_body'] ?? '');
			$attachments = [];

			if (!empty($_POST['visa_email_include_docs'])) {
					if (!class_exists('ZipArchive')) {
							wp_die('Impossible de créer une archive ZIP : extension ZipArchive manquante.');
					}

					$file_entries = [];

					// a) Documents principaux
					$urls = get_post_meta($post_id, 'visa_documents', true);
					if (is_array($urls)) {
							foreach ($urls as $url) {
									$path = $this->get_attachment_path($url);
									if ($path) {
											$file_entries[$path] = 'documents_envoyes/' . basename($path);
									}
							}
					}

					// // b) Documents du risque migratoire
					// $risque_fields = [
					// 		'contrat_travail_doc',
					// 		'attestation_conge_doc',
					// 		'fiche_de_paie_doc',
					// 		'releve_bancaire_doc',
					// 		'preuve_de_propriete_doc',
					// 		'contrat_location_doc',
					// 		'attestation_scolarite_enfant_doc',
					// 		'justificatif_inscription_universitaire_doc',
					// 		'lettre_mission_professionnelle_doc',
					// 		'engagement_retour_doc',
					// 		'preuve_responsabilites_familiales_doc',
					// 		'attestation_mariage_restees_au_pays_doc',
					// 		'preuve_activite_eco_doc',
					// 		'attestation_bancaire_doc',
					// 		'engagement_honneur_de_retour_doc',
					// 		'reservation_billet_retour_doc',
					// 		'preuve_participation_doc',
					// 		'lettre_organisme_accueil_doc',
					// 		'preuve_prec_conformite_doc',
					// 		'casier_vierge_doc'
					// ];

					// foreach ($risque_fields as $field) {
					// 		$url = get_post_meta($post_id, $field, true);
					// 		if ($url) {
					// 				$path = $this->get_attachment_path($url);
					// 				if ($path) {
					// 						$file_entries[$path] = 'risque_migratoire/' . $field . '_' . basename($path);
					// 				}
					// 		}
					// }

					// Création du ZIP
					if (!empty($file_entries)) {
							$zip_file = trailingslashit(sys_get_temp_dir()) . 'visa_docs_' . $post_id . '_' . time() . '.zip';
							$zip = new ZipArchive();
							if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
									foreach ($file_entries as $abs_path => $zip_name) {
											$zip->addFile($abs_path, $zip_name);
									}
									$zip->close();
									$attachments[] = $zip_file;
							}
					}
			}

			$edit_link = admin_url('post.php?post=' . $post_id . '&action=edit');

			if ($email && $subject && $body) {
					$sent = wp_mail($email, $subject, $body, ['Content-Type: text/plain; charset=UTF-8'], $attachments);

					// Nettoyage différé
					if (!empty($attachments)) {
							add_action('shutdown', function() use ($attachments) {
									foreach ($attachments as $file) {
											if (strpos($file, '.zip') !== false && file_exists($file)) {
													unlink($file);
											}
									}
							});
					}

					$history = get_post_meta($post_id, 'visa_email_history', true);
					if (!is_array($history)) {
							$history = [];
					}

					$history[] = [
							'date' => current_time('mysql'),
							'subject' => $subject,
							'body_excerpt' => wp_trim_words($body, 20),
							'status' => $sent ? 'envoyé' : 'échec',
					];

					update_post_meta($post_id, 'visa_email_history', $history);
					wp_redirect(add_query_arg('visa_email_sent', 'success', $edit_link));
			} else {
					wp_redirect(add_query_arg('visa_email_sent', 'fail', $edit_link));
			}
			exit;
		}

		/**
		 * Convertit une URL de fichier en chemin absolu valide, si possible.
		 */
		private function get_attachment_path($url) {
				if (!$url) return false;
				$path = wp_parse_url($url, PHP_URL_PATH);
				if (!$path) return false;
				$abs = ABSPATH . ltrim($path, '/');
				$ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
				if (file_exists($abs) && in_array($ext, ['pdf','jpg','jpeg','png'], true)) {
						return $abs;
				}
				return false;
		}

    public function show_visa_email_notice() {
        if (!isset($_GET['visa_email_sent'])) {
            return;
        }
        if ($_GET['visa_email_sent'] === 'success') {
            echo '<div class="notice notice-success is-dismissible"><p>Email envoyé au demandeur.</p></div>';
        } elseif ($_GET['visa_email_sent'] === 'fail') {
            echo '<div class="notice notice-error is-dismissible"><p>Échec de l’envoi de l’email. Vérifiez les champs.</p></div>';
        }
    }
    
    /**
     * Vérification du passeport
     */
    public function add_passeport_metabox() {
        add_meta_box(
            'visa_passeport',
            'Vérification du passeport',
            [$this, 'render_passeport_metabox'],
            'visa_request',
            'side',
            'default'
        );
    }
    
    public function render_passeport_metabox($post) {
        $coherence = get_post_meta($post->ID, 'visa_passport_coherence', true);
        $coherence_detail = get_post_meta($post->ID, 'visa_passport_coherence_detail', true);
        
        if ( $coherence ) {
            // Détermine la couleur selon le résumé
            $color = stripos($coherence, 'Non cohérent') !== false ? 'red' :
                 (stripos($coherence, 'Partiellement cohérent') !== false ? '#d7d700' :
                 (stripos($coherence, 'Cohérent') !== false ? 'green' : 'gray'));
        
            echo "<p style='padding:8px; border-radius:4px; background-color:{$color}; color:#fff; font-weight:bold;'>
                    " . esc_html($coherence) . "
                  </p>";
        } 
    
        if (is_array($coherence_detail)) {
            echo "<p><strong>Détail :</strong></p><ul>";
            foreach ($coherence_detail as $key => $value) {
                echo "<li><strong>" . esc_html($key) . ":</strong> " . esc_html($value) . "</li>";
            }
            echo "</ul>";
        }
    }
    
    /**
     * Affichage Gmail
     */
    public function add_gmail_metabox() {
        add_meta_box(
            'visa_passeport',
            'Boîte de réception Gmail',
            [$this, 'visa_render_gmail_metabox'],
            'visa_request',
            'normal',
            'default'
        );
    }
    
    function visa_render_gmail_metabox($post) {

        if (!current_user_can('administrator')) {
            echo '<p>Accès refusé</p>';
            return;
        }
    
        // ðÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂ user_id lié à la demande
        $user_id = get_post_meta($post->ID, '_visa_user_id', true);
    
        if (!$user_id) {
            echo '<p>Aucun demandeur associé.</p>';
            return;
        }
    
        $gmail = Visa_Manager_Google_Int::get_user_gmail_service($user_id);
    
        if (!$gmail) {
            echo '<p><strong>Gmail non connecté</strong></p>';
            return;
        }
    
        try {
            $messages = $gmail->users_messages->listUsersMessages('me', [
                'maxResults' => 10,
                'labelIds'   => ['INBOX'],
            ]);
        } catch (Exception $e) {
            echo '<p>Erreur Gmail API</p>';
            return;
        }
    
        if (!$messages->getMessages()) {
            echo '<p>Aucun message</p>';
            return;
        }
    
        echo '<ul style="margin:0;padding-left:18px">';
    
        foreach ($messages->getMessages() as $msg) {
    
            $message = $gmail->users_messages->get('me', $msg->getId(), [
                'format' => 'metadata',
                'metadataHeaders' => ['From', 'Subject', 'Date']
            ]);
    
            $headers = [];
            foreach ($message->getPayload()->getHeaders() as $h) {
                $headers[$h->getName()] = $h->getValue();
            }
    
            echo '<li style="margin-bottom:10px">';
            echo '<strong>' . esc_html($headers['Subject'] ?? '(Sans sujet)') . '</strong><br>';
            echo '<small>' . esc_html($headers['From'] ?? '') . '</small><br>';
            echo '<small style="color:#666">' . esc_html($headers['Date'] ?? '') . '</small>';
            echo '</li>';
        }
    
        echo '</ul>';
    }

    
    /**
     * Groupe de document
     */
    public function add_group_metabox() {
        add_meta_box(
            'visa_group',
            'Groupe de document',
            [$this, 'render_group_metabox'],
            'visa_request',
            'side',
            'default'
        );
    }
    
    public function render_group_metabox($post) {
        $group = get_post_meta($post->ID, 'visa_group', true);
    
        if ($group) {
            $existing_posts = get_posts([
                'post_type'  => 'visa_request',
                'post_status'=> 'any',
                'meta_key'   => 'visa_group',
                'meta_value' => $group,
                'fields'     => 'ids',
            ]);
    
            if (!empty($existing_posts)) {
                echo '<p><strong>Demandes liées au groupe :</strong></p>';
                echo '<ul>';
                foreach ($existing_posts as $id) {
                    // Lien vers la page d’édition de chaque post
                    $edit_link = get_edit_post_link($id);
                    $title = get_the_title($id);
                    echo '<li><a href="' . esc_url($edit_link) . '">#' . esc_html($id) . ' — ' . esc_html($title) . '</a></li>';
                }
                echo '</ul>';
            } else {
                echo '<p>Aucune autre demande trouvée dans ce groupe.</p>';
            }
    
        } else {
            echo '<p>Pas de groupe rattaché.</p>';
        }
    }

    
    /**
     * Génère et renvoie le PDF “expertise” de la demande
     */
    public function add_expertise_metabox() {
        add_meta_box(
            'visa_expertise',
            'Expertise et Lettre de motivation',
            [$this, 'render_expertise_metabox'],
            'visa_request',
            'side',
            'default'
        );
    }
    
    public function render_expertise_metabox($post) {
        $file_url = get_post_meta($post->ID, 'visa_expertise_doc', true);
        $resume = get_post_meta($post->ID, 'visa_synthese_resume', true);
        $synthese_url = get_post_meta($post->ID, 'visa_synthese_doc', true);
				
        if ( $resume ) {
						// Détermine la couleur selon le résumé
            $color = stripos($resume, 'Défavorable') !== false ? 'red' : 'green';
        
            echo "<p><strong>Résumé :</strong></p>";
            echo "<p style='padding:8px; border-radius:4px; background-color:{$color}; color:#fff; font-weight:bold;'>
                    " . esc_html($resume) . "
                  </p>";
									
				// 		if ( stripos($resume, 'Défavorable') !== false ) {
				// 				echo '<div style="padding:8px 12px; background:#f8d7da; color:#721c24; border-radius:4px; font-weight:bold; display:inline-block; margin-bottom:10px;">ðÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂ´ Défavorable</div>';
				// 		} elseif ( stripos($resume, 'Favorable') !== false ) {
				// 				echo '<div style="padding:8px 12px; background:#d4edda; color:#155724; border-radius:4px; font-weight:bold; display:inline-block; margin-bottom:10px;">ðÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂ¢ Favorable</div>';
				// 		} else {
				// 				echo '<div style="padding:8px 12px; background:#fff3cd; color:#856404; border-radius:4px; font-weight:bold; display:inline-block; margin-bottom:10px;">ðÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂ¡ ' . esc_html($resume) . '</div>';
				// 		}
				} else {
						echo '<div style="padding:8px 12px; background:#e2e3e5; color:#41464b; border-radius:4px; font-weight:bold; display:inline-block; margin-bottom:10px;">Aucune analyse IA</div>';
				}
    
        if ( $file_url ) {
            echo "<p><a class='button button-secondary' href='" . esc_url($file_url) . "' target='_blank'>
                    Télécharger la lettre de motivation (PDF)
                  </a></p>";
        } else {
            echo "<p><em>Aucune lettre de motivation disponible.</em></p>";
        }
        
        if ( $synthese_url ) {
            echo "<p><a class='button button-secondary' href='" . esc_url($synthese_url) . "' target='_blank'>
                    Télécharger l’expertise (PDF)
                  </a></p>";
        } else {
            echo "<p><em>Aucun document d’expertise disponible.</em></p>";
        }
    }
    
    public function handle_download_visa_expertise() {
		$post_id = intval( $_GET['post_id'] ?? 0 );
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( 'Accès non autorisé.' );
		}

		// 1) Liste exhaustive des meta-keys à afficher
		$all_keys = [
			'level1_email'                   => 'Email de connexion',
			'visa_type'                      => 'Type de visa',
			'visa_depot'                     => 'Date de dépôt',
			'visa_wilaya'                    => 'Wilaya de dépôt',
			'visa_ville'                     => 'Ville de dépôt',
			'visa_info_objet_base'           => 'Motif détaillé',
			'visa_full_name'                 => 'Nom complet',
			'visa_nom_famille'               => 'Nom de famille',
			'visa_prenom'                    => 'Prénom',
			'visa_birth_date'                => 'Date de naissance',
			'visa_lieu_naiss'                => 'Lieu de naissance',
			'visa_pays_naiss'                => 'Pays de naissance',
			'visa_nationalite'               => 'Nationalité',
			'visa_nationalite_diff'          => 'Nationalité secondaire',
			'visa_sexe'                      => 'Sexe',
			'visa_sexe_autre'                => 'Sexe (autre)',
			'visa_etat_civil'                => 'État civil',
			'visa_etat_civil_autre'          => 'État civil (autre)',
			'visa_num_national_identite'     => 'N° carte ident. nationale',
			'visa_doc_voyage'                => 'Justificatif voyage 1',
			'visa_doc_voyage_autre'          => 'Justificatif voyage 2',
			'visa_date_delivrance'           => 'Date de délivrance',
			'visa_date_expiration'           => 'Date d’expiration',
			'visa_delivre_par'               => 'Délivré par',
			'visa_adresse'                   => 'Adresse complète',
			'visa_mail'                      => 'Email personnel',
			'visa_phone'                     => 'Téléphone',
			'visa_num_resident'              => 'N° de résident',
			'visa_autre_date_delivrance'     => 'Date délivrance (autre)',
			'visa_autre_date_expiration'     => 'Date expiration (autre)',
			'visa_profession'                => 'Profession',
			'visa_employeur'                 => 'Employeur / Société',
			'visa_info_employeur'            => 'Référent / Contact employeur',
			'visa_objet'                     => 'Motif du voyage',
			'visa_objet_autre'               => 'Motif (autre)',
			'visa_info_objet'                => 'Info motif',
			'visa_adresse_sejour'            => 'Adresse de séjour',
			'visa_duree'                     => 'Durée prévue',
			'visa_stay_duration'             => 'Durée du séjour (jours)',
			'visa_duree_anterieure'          => 'Durée antérieure',
			'visa_info_duree_anterieure'     => 'Info durée antérieure',
			'visa_adresse_duree_anterieure'  => 'Adresse séjour antérieur',
			'visa_arrival_date'              => 'Date d’arrivée',
			'visa_departure_date'            => 'Date de départ',
			'visa_reason'                    => 'Motif long séjour',
			'visa_moyens_existence'          => 'Moyens d’existence',
			'visa_bourse'                    => 'Bourse / aide',
			'visa_bourse_detail'             => 'Détail bourse',
			'visa_prise_en_charge'           => 'Prise en charge',
			'visa_info_prise_en_charge'      => 'Détail prise en charge',
			'visa_famille_resident'          => 'Famille résidente',
			'visa_info_famille_resident'     => 'Détail famille résidente',
			'visa_empreinte'                 => 'Empreinte requise',
			'visa_empreinte_date'            => 'Date empreinte',
			'visa_num_visa'                  => 'N° de visa précédent',
			'visa_autorisation_delivre_par'  => 'Autorisation délivrée par',
			'visa_autorisation_validite'     => 'Validité autorisation',
			'visa_autorisation_delivre_au'   => 'Délivrée au',
			'visa_hotel'                     => 'Hôtel invité',
			'visa_adresse_inviteur'          => 'Adresse inviteur',
			'visa_hote'                      => 'Nom de l’hôte',
			'visa_personne_de_contact'       => 'Contact sur place',
			'visa_financement'               => 'Financement',
			'visa_demandeur_financement_moyen'       => 'Moyen financement demandeur',
			'visa_demandeur_financement_moyen_autre'=> 'Moyen financement (autre)',
			'visa_financement_garant'        => 'Garantie financement',
			'visa_garant_autre_detail'        => 'Autre garantie financement',
			'visa_garant_financement_moyen'  => 'Moyen financement garant',
			'visa_garant_financement_moyen_autre'=> 'Moyen financement garant (autre)',
			'visa_remplisseur'               => 'Nom du remplisseur',
			'visa_adresse_remplisseur'       => 'Adresse du remplisseur',
			'visa_num_remplisseur'           => 'Téléphone remplisseur',
			'visa_membres_famille'           => 'Membres de la famille',
		];

		// 2) Extraction & formatage
		$data = [];
		foreach ( $all_keys as $meta_key => $label ) {
			$raw = get_post_meta( $post_id, $meta_key, true );
			if ( '' === $raw ) {
				continue;
			}

			// Fichiers attachés
			if ( in_array( $meta_key, ['visa_doc_voyage','visa_doc_voyage_autre'], true ) ) {
				if ( is_numeric( $raw ) ) {
					$url   = wp_get_attachment_url( $raw );
					$name  = basename( get_attached_file( $raw ) );
					$value = "$name ( $url )";
				} else {
					$value = esc_html( $raw );
				}
			}
			// JSON famille
			elseif ( $meta_key === 'visa_membres_famille' ) {
				$members = json_decode( $raw, true );
				$lines   = [];
				if ( is_array( $members ) ) {
					foreach ( $members as $m ) {
						$lines[] = sprintf(
							'%s / %s / %s / %s',
							$m['lien'] ?? '',
							$m['nom'] ?? '',
							$m['naissance'] ?? '',
							$m['nationalite'] ?? ''
						);
					}
				}
				$value = implode("\n", $lines);
			}
			// Durée en jours
			elseif ( $meta_key === 'visa_stay_duration' ) {
				$value = intval( $raw ) . ' jours';
			}
			// Tous les autres
			else {
				$value = sanitize_text_field( $raw );
			}

			if ( $value !== '' ) {
				$data[ $label ] = $value;
			}
		}

		// 3) Nettoyage des buffers
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		// 4) Génération du PDF (FPDF)
		$pdf = new FPDF('P','mm','A4');
		$pdf->AddPage();
		$pdf->SetFont('Arial','',11);

		// --- En-tête consulaire
		$ville = $data['Ville de dépôt'] ?? 'Ville inconnue';
		$pdf->Cell(0, 5, utf8_decode("Consulat Général de France à $ville"), 0, 1, 'L');
		$pdf->Cell(0, 5, utf8_decode("Adresse du Consulat : $ville, Algérie"), 0, 1, 'L');
		$pdf->Ln(8);

		// --- Objet
		$pdf->SetFont('Arial','B',12);
		$pdf->Cell(0,6, utf8_decode('Objet : Demande de visa'), 0,1,'L');
		$pdf->Ln(6);

		// --- Salutation
		$pdf->SetFont('Arial','',11);
		$pdf->MultiCell(0,5, utf8_decode('Monsieur le Consul Général,' ) );
		$pdf->Ln(4);

		// --- Corps : boucle sur tous les champs
		foreach ( $data as $label => $value ) {
			$pdf->SetFont('Arial','B',11);
			$pdf->Cell(60,7, utf8_decode($label.' :'), 0,0);
			$pdf->SetFont('Arial','',11);
			$pdf->MultiCell(0,7, utf8_decode($value), 0,1);
		}

		// --- Formule de politesse
		$pdf->Ln(8);
		$pdf->MultiCell(0,5, utf8_decode(
			"Je vous remercie de bien vouloir examiner ma demande et reste à votre disposition pour toute information complémentaire.\n\n" .
			"Dans l’attente de votre réponse favorable, je vous prie d’agréer, Monsieur le Consul Général, l’expression de mes salutations distinguées."
		) );

		// --- Signature
		$full_name = get_post_meta( $post_id, 'visa_full_name', true );
		if ( $full_name ) {
			$pdf->Ln(6);
			$pdf->MultiCell(0,5, utf8_decode($full_name));
		}

		// 5) Envoi du PDF
		header('Content-Type: application/pdf');
		header('Content-Disposition: inline; filename="expertise_visa_' . $post_id . '.pdf"' );
		$pdf->Output('I','expertise_visa_' . $post_id . '.pdf');
		exit;
	}
	
	/**
     * Documents CRUD
     */
    public function add_documents_metabox() {
        add_meta_box(
            'visa_documents',
            'Documents envoyés',
            [$this, 'render_documents_metabox'],
            'visa_request',
            'side',
            'default'
        );
    }

    public function render_documents_metabox( WP_Post $post ) {
        wp_nonce_field( 'save_visa_documents', 'visa_documents_nonce' );
    
        // Récupère le tableau de URLs déjà uploadés
        $docs = get_post_meta( $post->ID, 'visa_documents', true );
        if ( ! is_array( $docs ) ) {
            $docs = [];
        }
    
        echo '<div id="visa-doc-list">';
        foreach ( $docs as $i => $url ) {
            $file = esc_html( basename( wp_parse_url( $url, PHP_URL_PATH ) ) );
            echo '<p style="display:flex" data-index="'. $i .'">';
            echo    '<a href="'. esc_url( $url ) .'" target="_blank">'. $file .'</a> ';
            echo    '<button type="button" class="button-link remove-doc" style="font-size: x-large;font-weight: 900;color: #d63638;text-decoration: unset;" data-index="'. $i .'">×</button>';
            echo    '<input type="hidden" name="visa_documents[]" value="'. esc_url( $url ) .'">';
            echo '</p>';
        }
        echo '</div>';
    
        // Bouton pour ouvrir la médiathèque
        echo '<p><button type="button" class="button add-doc">+ Ajouter</button></p>';
    
        // --- AJOUT : liste des documents requis ---
        $saved_doc_requis = get_post_meta($post->ID, 'visa_doc_requis', true);
        if ( !empty($saved_doc_requis) ) {
            echo '<h4>Documents requis :</h4>';
            echo '<ul class="visa-doc-requis">';
            // Si c'est déjà du HTML, on l'affiche tel quel
            if ( is_array($saved_doc_requis) ) {
                foreach ( $saved_doc_requis as $doc ) {
                    echo '<li>- ' . wp_kses_post($doc) . '</li>';
                }
            } else {
                echo wp_kses_post($saved_doc_requis);
            }
            echo '</ul>';
        }
    }


    public function save_documents_meta( $post_id, WP_Post $post ) {
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( $post->post_type !== 'visa_request' ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;
        if ( empty( $_POST['visa_documents_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['visa_documents_nonce'], 'save_visa_documents' ) ) return;
    
        // Récupère l’array direct depuis $_POST
        $docs = isset( $_POST['visa_documents'] ) && is_array( $_POST['visa_documents'] )
              ? array_map( 'esc_url_raw', $_POST['visa_documents'] )
              : [];
    
        // Filtre les URLs valides
        $valid = array_filter( $docs, function($url){
            return filter_var( $url, FILTER_VALIDATE_URL );
        });
    
        if ( $valid ) {
						$previous = get_post_meta( $post_id, 'visa_documents', true );
						update_post_meta( $post_id, 'visa_documents', array_values( $valid ) );

						// Comparaison pour détecter un ajout ou une modification
						if ( $previous !== array_values( $valid ) ) {
								update_post_meta( $post_id, 'visa_has_new_docs', true );
						}
				} else {
						delete_post_meta( $post_id, 'visa_documents' );
						delete_post_meta( $post_id, 'visa_has_new_docs' ); 
				}
    }
    
    public function enqueue_documents_assets( $hook ) {
        global $post;
        if ( ! in_array( $hook, ['post.php','post-new.php'], true ) ) {
            return;
        }
        if ( empty($post) || $post->post_type !== 'visa_request' ) {
            return;
        }
    
        wp_enqueue_media();
        wp_enqueue_script(
            'visa-admin-docs',                                  // handle
            plugin_dir_url( __FILE__ ) . 'admin-docs.js',       // chemin vers ton JS
            [ 'jquery' ],                                       // dépendances
            '1.0',                                              // version
            true                                                // in_footer
        );
    }

	/**
     * Documents CRUD Risque migratoire
     */
    // public function add_risque_metabox() {
    //     add_meta_box(
    //         'visa_risque',
    //         'Risque migratoire',
    //         [$this, 'render_documents_metabox_risque'],
    //         'visa_request',
    //         'side',
    //         'default'
    //     );
    // }

	// public function render_documents_metabox_risque( WP_Post $post ) {
	// 	wp_nonce_field( 'save_visa_documents', 'visa_documents_nonce' );

	// 	$fields = [
	// 		'contrat_travail',
	// 		'attestation_conge',
	// 		'fiche_de_paie',
	// 		'releve_bancaire',
	// 		'preuve_de_propriété',
	// 		'contrat_location',
	// 		'attestation_scolarite_enfant',
	// 		'justificatif_inscription_universitaire',
	// 		'lettre_mission_professionnelle',
	// 		'engagement_retour',
	// 		'preuve_responsabilités_familiales',
	// 		'attestation_mariage_restees_au_pays',
	// 		'preuve_activite_eco',
	// 		'attestation_bancaire',
	// 		'engagement_honneur_de_retour',
	// 		'reservation_billet_retour',
	// 		'preuve_participation',
	// 		'lettre_organisme_accueil',
	// 		'preuve_prec_conformite',
	// 		'casier_vierge'
	// 	];

	// 	echo '<div id="visa-doc-list"><ol>';
	// 	foreach ($fields as $field) {
	// 		$file_url = get_post_meta($post->ID, $field . '_doc', true);
	// 		if ($file_url) {
	// 			$file = esc_html( basename( wp_parse_url( $file_url, PHP_URL_PATH ) ) );
	// 			echo '<li>'. esc_html($field) .'</li>';
	// 			echo '<p style="display:flex">';
	// 			echo    '<a href="'. esc_url( $file_url ) .'" target="_blank">'. $file .'</a> ';
	// 			echo '</p>';
	// 		}
	// 	}
	// 	echo '</ol></div>';
	// }
    
    /**
     * Génère le formulaire cerfa
     */
    public function add_cerfa_metabox() {
        add_meta_box(
            'visa_cerfa',
            'Génerer le formulaire cerfa',
            [$this, 'render_cerfa_metabox'],
            'visa_request',
            'side',
            'default'
        );
    }
    
    public function render_cerfa_metabox( WP_Post $post ) {
        // Génère un nonce unique
        wp_nonce_field( 'generate_visa_doc', 'visa_doc_nonce' );

        $visa_type = get_post_meta( $post->ID, 'visa_type', true );

        if ( 'court_sejour' === $visa_type ) {
            $action = 'download_cerfa_cs';
            $label  = 'Télécharger CERFA Court Séjour';
        } else {
            $action = 'download_cerfa_ls';
            $label  = 'Télécharger CERFA Long Séjour';
        }

        // URL d'appel admin-post.php avec nonce et post_id
        $url = add_query_arg(
            [
                'action'  => $action,
                'post_id' => $post->ID,
                'nonce'   => wp_create_nonce( 'generate_visa_doc' ),
            ],
            admin_url( 'admin-post.php' )
        );

        printf(
          '<button type="button" class="button button-secondary" id="export-cerfa-js" data-post-id="%1$d" data-visa-type="%2$s">%3$s</button>',
          $post->ID,
          esc_attr( $visa_type ),
          esc_html( $label )
        );

    }
    
    

	public function handle_download_cerfa_cs() {
        $this->download_cerfa_pdf( 'court_sejour' );
    }

    /**
     * 4) Handler Long Séjour
     */
    public function handle_download_cerfa_ls() {
        $this->download_cerfa_pdf( 'long_sejour' );
    }

    /**
     * 5) Méthode générique qui importe le template et injecte les meta
     */
    protected function download_cerfa_pdf(string $mode) {
        $post_id = absint($_GET['post_id'] ?? 0);
        $nonce   = sanitize_text_field($_GET['nonce'] ?? '');
        if (
            ! $post_id ||
            ! current_user_can('edit_post', $post_id) ||
            ! wp_verify_nonce($nonce, 'generate_visa_doc')
        ) {
            wp_die('Accès non autorisé.');
        }
    
        // Chargement du template HTML
        $template = plugin_dir_path(__FILE__) . '../templates/cerfa-' 
                       . ( $mode === 'court_sejour' ? 'cs' : 'ls' ) 
                       . '.html';
        if (! file_exists($template)) {
            wp_die('Template introuvable : '.$template);
        }
        $html = file_get_contents($template);
    
        // 2) Modifier le HTML via DOMDocument
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        // Pour ne pas forcer l’ajout de <html><body> par défaut :
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
    
        // 3) Boucle de mapping (ids ↔ champs WP)
        $fields = [
            '1.' => 'visa_full_name',
            '2.' => 'visa_prenom',
            '3.' => 'visa_birth_date',
            '4.' => 'visa_birth_place',
            '5.' => 'visa_nationalite',
            '6.' => 'visa_passport_number',
            // … autres champs …
        ];
    
        foreach ($fields as $id => $meta_key) {
            $value = get_post_meta($post_id, $meta_key, true) ?: 'Non renseigné';
            $value = esc_html($value);
    
            $node = $dom->getElementById($id);
            if ($node) {
                // Remplace uniquement le contenu textuel
                while ($node->firstChild) {
                    $node->removeChild($node->firstChild);
                }
                $node->appendChild($dom->createTextNode($value));
            }
        }
    
        // 4) Récupérer le HTML final
        $final_html = $dom->saveHTML();
        
        file_put_contents(
          plugin_dir_path(__FILE__) . '../cerfa-debug.html',
          $final_html
        );
        exit;

    
        // 5) Génération PDF
        require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';
    
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
    
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($final_html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
    
        $filename = "cerfa_{$mode}_{$post_id}.pdf";
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }
}