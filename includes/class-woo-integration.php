<?php
// includes/class-woo-integration.php
defined('ABSPATH') || exit;

class Visa_Woo_Integration {

    public function __construct() {
        // 1) Précharge le panier “Visa” depuis ta page paiement-visa
        add_action('template_redirect',         [$this, 'handle_redirect_to_checkout']);

        // 2) Gère le toggle add_insurance en GET
        add_action('template_redirect',         [$this, 'handle_insurance_toggle'], 5);

        // 3) Injecte le bouton Ajouter/Retirer l’assurance
        //    juste avant le tableau de commande
        add_action('woocommerce_review_order_before_payment',
                   [$this, 'output_insurance_button'], 10);

        // 4) Attache request_id et type (visa|insurance) 
        //    à chaque ligne de commande
        add_action('woocommerce_checkout_create_order_line_item',
                   [$this, 'attach_request_meta'], 20, 4);

        // 5) Après paiement, redirige vers la suite du formulaire
        add_action('woocommerce_thankyou',       [$this, 'maybe_redirect_after_payment']);
    }

    /**
     * 1) Quand on arrive sur la page “paiement-visa” avec request_id,
     *    on vide le panier et on n’y met que le produit “Demande de Visa”,
     *    puis on stocke request_id en session.
     */
    public function handle_redirect_to_checkout() {
        if ( ! is_page('paiement-visa') || ! isset($_GET['request_id']) ) {
            return;
        }
    
        $req_id      = intval( $_GET['request_id'] );
        $visa_amount = (float) get_option('visa_payment_amount', 100);
    
        // Récupère le post pour connaître le groupe
        $post = get_post($req_id);
        if ( ! $post ) return;
    
        $group_id = get_post_meta($post->ID, 'visa_group', true);
    
        // Liste des request_id à ajouter
        $request_ids = [];
    
        if ( $group_id ) {
            // Tous les posts dans ce groupe ET avec 'mandate_accepted' = 'yes'
            $group_posts = get_posts([
                'post_type'   => 'visa_request',
                'numberposts' => -1,
                'meta_query'  => [
                    [
                        'key'   => 'visa_group',
                        'value' => $group_id,
                    ],
                    [
                        'key'   => 'mandate_accepted',
                        'value' => 'yes',
                    ]
                ],
                'post_status' => 'any'
            ]);
        
            foreach ($group_posts as $gp) {
                // Vérifie si ce visa est déjà payé (commande terminée)
                $already_paid = false;
                $orders = wc_get_orders([
                    'limit'      => -1,
                    'status'     => 'completed',
                    'meta_key'   => 'request_id',
                    'meta_value' => $gp->ID,
                ]);
                if ( ! empty($orders) ) {
                    $already_paid = true;
                }
        
                if ( ! $already_paid ) {
                    $request_ids[] = $gp->ID;
                }
            }
        } else {
            // Request seul, vérifier aussi le mandat
            $mandate = get_post_meta($req_id, 'mandate_accepted', true);
            if ($mandate === 'yes') {
                $request_ids[] = $req_id;
            }
        }
    
        if ( empty($request_ids) ) {
            wc_add_notice('Toutes les demandes de ce groupe ont déjà été payées.', 'notice');
            wp_safe_redirect(home_url());
            exit;
        }
    
        // Vider le panier et session assurance
        WC()->cart->empty_cart();
        WC()->session->__unset('add_insurance');
    
        // Ajouter chaque request_id comme produit virtuel
        $visa_pid = $this->get_or_create_virtual_product($visa_amount, 'Demande de Visa');
    
        foreach ($request_ids as $rid) {
            WC()->cart->add_to_cart($visa_pid, 1, 0, [], [
                'request_id' => $rid,
                'type'       => 'visa',
            ]);
        }
    
        // Stocker le dernier request_id dans la session pour référence assurance
        WC()->session->set('visa_request_id', $request_ids[0]);
    
        wp_redirect( wc_get_checkout_url() );
        exit;
    }


    /**
     * 2) À chaque rechargement du checkout avec add_insurance en GET,
     *    on met à jour la session + le panier (ajout/retrait).
     */
    public function handle_insurance_toggle() {
        if ( ! is_checkout() || ! isset($_GET['add_insurance']) ) {
            return;
        }

        $add = (int) $_GET['add_insurance'];
        WC()->session->set('add_insurance', $add);

        // Retire toute ancienne ligne assurance
        foreach ( WC()->cart->get_cart() as $key => $ci ) {
            if ( ! empty($ci['type']) && $ci['type'] === 'insurance' ) {
                WC()->cart->remove_cart_item($key);
            }
        }

        // Ajoute l’assurance si demandé
        if ( $add ) {
            $req_id = WC()->session->get('visa_request_id');
            $ins_amt = (float) get_option('visa_insurance_price', 20);
            $ins_pid = $this->get_or_create_virtual_product($ins_amt, 'Assurance Visa');
            WC()->cart->add_to_cart($ins_pid, 1, 0, [], [
                'request_id' => $req_id,
                'type'       => 'insurance',
            ]);
        }

        // Redirection propre sans keep le param
        wp_safe_redirect( wc_get_checkout_url() );
        exit;
    }

    /**
     * 3) Affiche le bouton Ajouter/Retirer l’assurance
     *    juste avant le tableau de commande (order review).
     */
    public function output_insurance_button() {
        if ( ! is_checkout() || is_wc_endpoint_url('order-received') ) {
            return;
        }

        $insured = (bool) WC()->session->get('add_insurance');
        $price   = (float) get_option('visa_insurance_price', 20);
        $label   = $insured
                 ? 'Retirer l’assurance'
                 : "Ajouter l’assurance (+{$price} DZD)";
        $toggle  = $insured ? 0 : 1;
        $url     = esc_url( add_query_arg('add_insurance', $toggle, wc_get_checkout_url()) );

        // echo '<div class="woocommerce-assurance" style="margin:1.5em 0;text-align: center;">'
        //     . "<a href=\"{$url}\" class=\"button\">{$label}</a>"
        //     . '</div>';
    }

    /**
     * 4) Attache request_id et type à chaque ligne de commande
     */
    public function attach_request_meta($item, $cart_item_key, $values, $order) {
        if (isset($values['request_id'])) {
            $item->add_meta_data('request_id', $values['request_id'], true);
        }
        if (isset($values['type'])) {
            $item->add_meta_data('type', $values['type'], true);
        }
        return $item;
    }

    // /**
    //  * 5) Après paiement, redirige vers la suite du formulaire
    //  */
    // public function maybe_redirect_after_payment($order_id) {
    //     if ( ! $order_id ) {
    //         return;
    //     }

    //     $order = wc_get_order($order_id);
    //     foreach ($order->get_items() as $item) {
    //         $req_id = $item->get_meta('request_id');
    //         if ($req_id) {
    //             $url = home_url("/type-visa/?request_id={$req_id}");
    //             add_action('wp_footer', function() use ($url) {
    //                 echo '<div class="woocommerce-message"'
    //                    . ' style="margin:1em 0;">'
    //                    . 'Paiement confirmé – redirection…</div>'
    //                    . '<script>setTimeout(()=>window.location.href="'
    //                    . esc_url($url) . '",3000)</script>';
    //             });
    //             break;
    //         }
    //     }
    // }

    /**
     * Crée ou met à jour un produit virtuel (visa ou assurance)
     */
    private function get_or_create_virtual_product($amount, $label = 'Visa') {
        $slug       = 'visa_prod_' . sanitize_title($label);
        $option_key = "{$slug}_id";

        $existing = get_option($option_key);
        if ($existing && get_post_status($existing) === 'publish') {
            $prod = wc_get_product($existing);
            if ($prod) {
                // Force toujours les propriétés, même si le produit existe
                $prod->set_regular_price($amount);
                $prod->set_price($amount);
                $prod->set_virtual(true);
                $prod->set_downloadable(false);
                $prod->set_catalog_visibility('hidden');
                $prod->save();
                return $prod->get_id();
            }
        }

        // Créer un nouveau produit
        $prod = new WC_Product_Simple();
        $prod->set_name($label);
        $prod->set_regular_price($amount);
        $prod->set_price($amount);
        $prod->set_catalog_visibility('hidden');
        $prod->set_status('publish');
        $prod->set_virtual(true);
        $prod->set_downloadable(false);
        $new_id = $prod->save();
        update_option($option_key, $new_id);

        return $new_id;
    }
}
