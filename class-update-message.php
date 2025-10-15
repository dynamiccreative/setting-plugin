<?php
/**
 * Classe pour gérer les messages de mise à jour du plugin
 * 
 * @since 1.0.0
 * @author Dynamic Creative
 */
class DC_Update_Message {
    
    /**
     * URL de base pour les fichiers de mise à jour
     */
    private $base_url = 'https://raw.githubusercontent.com/dynamiccreative/setting-plugin/refs/heads/main/';
    
    /**
     * Configuration du plugin
     */
    private $config;
    
    /**
     * Durée du cache en secondes
     */
    private $cache_duration = HOUR_IN_SECONDS;
    
    /**
     * Constructeur
     * 
     * @param array $config Configuration du plugin
     */
    public function __construct( $config = [] ) {
        $this->config = $config;
        //$this->clear_cache();
        $this->init();
    }
    
    /**
     * Initialisation des hooks
     */
    private function init() {
        $plugin_slug = $this->config['slug'];
        
        // Hook pour afficher le message de mise à jour
        add_action( 'in_plugin_update_message-' . $plugin_slug, [$this, 'display_update_message'], 20, 2 );
    }
    
    /**
     * Affiche le message de mise à jour
     * 
     * @param array $plugin_data Données du plugin
     * @param object $r Réponse de l'API de mise à jour
     */
    public function display_update_message( $plugin_data, $r ) {
    	$update_version = $r->new_version;
    	if ($update_version) $message_content = $this->get_message_content($update_version);
        
        if ( $message_content ) {
            echo '<hr class="e-major-update-warning__separator" /><div class="e-major-update-warning" style="display:block;">'. $message_content .'</div>';
        }
    }
    
    /**
     * Récupère le contenu du message depuis le fichier distant
     * 
     * @return string|false Contenu du message ou false
     */
    private function get_message_content($update_version = null) {
        $file_url = $this->base_url . 'update-'.$this->config['repo'].'.json';
        $cache_key = 'dc_st_update_message_' . md5( $file_url . $this->config['version'] );

        // Si pas de version de mise à jour fournie, utiliser la version actuelle
        if ( ! $update_version ) {
            $update_version = $this->config['version'];
        }
        
        // Vérifier le cache
        $cached_data = get_transient( $cache_key );
        if ( $cached_data !== false ) {
            return $this->find_appropriate_message( $cached_data, $update_version );
        }
        
        // Récupérer le contenu distant
        $response = wp_remote_get( $file_url, [
            'timeout' => 10,
            //'user-agent' => 'DC-Support-Technique/' . ST_VERSION,
            'headers' => [
                'Cache-Control' => 'no-cache'
            ]
        ]);
        
        // Vérifier la réponse
        if ( is_wp_error( $response ) ) {
            $this->log_error( 'Erreur requête: ' . $response->get_error_message() );
            return false;
        }
        
        if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return false;
        }
        
        $content = wp_remote_retrieve_body( $response );
        
        if ( empty( trim( $content ) ) ) {
            return false;
        }
        
        // Parser le JSON
        $json_data = json_decode( $content, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            if ( WP_DEBUG_LOG ) {
                error_log( 'Erreur JSON - ' . json_last_error_msg() );
            }
            return false;
        }
        
        // Valider la structure du JSON
        if ( ! $this->validate_json_structure( $json_data ) ) {
            if ( WP_DEBUG_LOG ) {
                error_log( 'Structure JSON invalide' );
            }
            return false;
        }
        
        // Mettre en cache les données JSON
        set_transient( $cache_key, $json_data, $this->cache_duration );
        
        // Retourner le message approprié
        return $this->find_appropriate_message( $json_data, $update_version );
    }

    /**
     * Trouve le message approprié selon les versions
     */
    private function find_appropriate_message( $json_data, $update_version ) {
        if ( ! isset( $json_data['messages'] ) || ! is_array( $json_data['messages'] ) ) {
            return false;
        }

        $current_version = $this->config['version'];
        
        foreach ( $json_data['messages'] as $message_data ) {
            // Vérifier que les champs requis existent
            if ( ! isset( $message_data['update_version'] ) || 
                 ! isset( $message_data['min_version'] ) || 
                 ! isset( $message_data['message'] ) ) {
                continue;
            }
            
            $msg_update_version = $message_data['update_version'];
            $msg_min_version = $message_data['min_version'];
            $message = $message_data['message'];
            
            // Vérifier si c'est le bon message pour cette version de mise à jour
            if ( version_compare( $update_version, $msg_update_version, '=' ) ) {
                // Vérifier si la version actuelle est >= version minimale requise
                if ( version_compare( $current_version, $msg_min_version, '<=' ) ) {
                    return $this->sanitize_content( $message );
                }
            }
        }
        
        return false;
    }

    /**
     * Valide la structure du JSON
     */
    private function validate_json_structure( $data ) {
        if ( ! is_array( $data ) ) {
            return false;
        }
        
        if ( ! isset( $data['messages'] ) || ! is_array( $data['messages'] ) ) {
            return false;
        }
        
        // Vérifier qu'au moins un message existe
        if ( empty( $data['messages'] ) ) {
            return false;
        }
        
        // Vérifier la structure d'au moins le premier message
        $first_message = $data['messages'][0];
        $required_fields = ['update_version', 'min_version', 'message'];
        
        foreach ( $required_fields as $field ) {
            if ( ! isset( $first_message[$field] ) ) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Nettoie et valide le contenu
     * 
     * @param string $content Contenu brut
     * @return string|false Contenu nettoyé
     */
    private function sanitize_content( $content ) {
        $content = trim( $content );
        
        if ( empty( $content ) ) {
            return false;
        }
        
        // Limiter la taille
        if ( strlen( $content ) > 1500 ) {
            $content = substr( $content, 0, 1500 ) . '...';
        }
        
        // Nettoyer le contenu
        //$content = sanitize_textarea_field( $content );
        
        // Remplacer les sauts de ligne par des espaces pour éviter les problèmes de formatage
        //$content = str_replace( ["\r\n", "\n", "\r"], ' ', $content );
        
        return $content;
    }
        
    /**
     * Log des erreurs
     * 
     * @param string $message Message d'erreur
     */
    private function log_error( $message ) {
        if ( WP_DEBUG_LOG ) {
            error_log( 'Update Message: ' . $message );
        }
    }
    
    /**
     * Efface le cache (utile pour les tests)
     */
    public function clear_cache() {
        $file_url = $this->base_url . 'update-'.$this->config['repo'].'.json';
        $cache_key = 'dc_st_update_message_' . md5( $file_url . $this->config['version'] );
        delete_transient( $cache_key );
    }
    
    /**
     * Configure l'URL de base pour les fichiers
     * 
     * @param string $url URL de base
     */
    public function set_base_url( $url ) {
        $this->base_url = trailingslashit( $url );
    }
    
    /**
     * Configure la durée du cache
     * 
     * @param int $duration Durée en secondes
     */
	public function set_cache_duration( $duration ) {
        $this->cache_duration = (int) $duration;
    }

    /**
     * Méthode de debug pour voir les données JSON
     */
    public function get_debug_data( $update_version = null ) {
        $repo_name = $this->config['repo'];
        $file_url = $this->base_url . 'update-' . $repo_name . '.json';
        
        if ( ! $update_version ) {
            $update_version = $this->config['version'];
        }
        
        return [
            'file_url' => $file_url,
            'current_version' => $this->config['version'],
            'update_version' => $update_version,
            'config' => $this->config
        ];
    }
}
