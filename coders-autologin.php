<?php

defined('ABSPATH') or die;
/* * *****************************************************************************
 * Plugin Name: Coders Auto-Login
 * Description: Login with a single url link
 * Version: 0.0.1
 * Author: Coder01
 * Author URI: 
 * License: GPLv2 or later
 * Text Domain: coders_autologin
 * Domain Path: lang
 * Class: CodersLogin
 * 
 * @author Coder01 <coder01@mnkcoder.com>
 * **************************************************************************** */

final class CodersLogin {

    const ENDPOINT = 'coders-autologin';

    /**
     *
     * @var \CodersLogin
     */
    private static $_instance = null;

    /**
     * 
     */
    protected function __construct() {
        
    }
    
    public static final function createToken( $userEmail ){
        return md5($userEmail);
    }
    /**
     * @param WP_User $user
     * @return Boolean
     */
    private final function sendMail(WP_User $user ){
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8'
        );
        
        $link = $this->createUrl($user->user_email);
        
        $style = array(
            'display: block;',
            'padding: 4px 8px;',
            'margin: 20px auto;',
            'background-color: blue',
            'color: white',
            'font-weight: bold',
            'text-transform: uppercase',
            'border-radius: 4px',
            'border: none'
        );
        
        $message = array(
            'Este es el acceso a tu blog',
            sprintf('<a style="%s" href="%s" target="_blank">Acceder a tu cuenta aqui</a>',
                    implode(', ', $style),
                    $link)
        );
        
        $send = wp_mail( $user->user_email,
                __('Acceso directo WEB','coders_autologin'),
                '<p>'. implode('</p><p>', $message) . '</p>',
                $headers );
        
        return $send;
    }
    
    /**
     * 
     * @global wpdb $wpdb
     * @return wpdb
     */
    private final function db(){
        global $wpdb;
        return $wpdb;
    }
    /**
     * 
     * test: amF1bWUubGxvcGlzQHByb3Rvbm1haWwuY29t
     * 
     * @global wpdb $wpdb
     * @param type $input
     */
    private final function queryUserByToken( $input ){
        
        $token = base64_decode($input);
        
        global $wpdb;
        $query = sprintf("SELECT `ID` FROM `%susers` WHERE `user_email`='%s'",
                $wpdb->prefix,
                $token );
                
        $output = $this->db()->get_row($query,ARRAY_A);
        
        if( !is_null($output)){
            return array_key_exists('ID', $output) ? intval(  $output['ID'] ) : 0;
        }
        return 0;
    }
    /**
     * @param String $token
     * @return String|URL
     */
    public static final function createUrl( $token ){
        return sprintf('%s/%s/login-%s',
                get_site_url(),
                self::ENDPOINT,
                base64_encode( $token ) );
    }


    
    private final function getUserMeta(){
        
    }
    /**
     * @param String $token
     * @return boolean
     */
    public final function login( $token ){
        if( !is_user_logged_in()){
            $user_id = $this->queryUserByToken($token);
            if( $user_id > 0 ){
                    $user = get_userdata($user_id);
                    if( FALSE !== $user ){
                        wp_set_current_user($user_id, $user->user_login);
                        wp_set_auth_cookie($user_id);
                        do_action('wp_login', $user->user_login);
                        return TRUE;
                    }
            }
        }
        
        return FALSE;
    }

    /**
     * 
     */
    public static final function init() {

        if (self::$_instance !== null) {
            return;
        }
        self::$_instance = new CodersLogin();
       
        add_action('init', function() {
            $endpoint = CodersLogin::ENDPOINT;
            global $wp, $wp_rewrite;
            add_rewrite_endpoint($endpoint, EP_ROOT);
            $wp->add_query_var($endpoint);
            $wp_rewrite->add_rule("^/$endpoint/?$", 'index.php?' . $endpoint . '=$matches[1]', 'top');
            $wp_rewrite->flush_rules();
        }, 10);
        /* SETUP RESPONSE */
        add_action('template_redirect', function() {
            $query = get_query_var(CodersLogin::ENDPOINT);
            if (strlen($query)) {
                global $wp_query;
                $wp_query->set('is_404', FALSE);
                $action = explode('-', $query);
                switch ($action[0]) {
                    case 'login':
                        if(count($action)){
                            if( CodersLogin::instance()->login($action[1]) ){
                                wp_redirect(get_site_url());
                            }
                        }
                        break;
                    case 'test':
                        printf('<a href="%s">Login</a>',
                            CodersLogin::createUrl('jaume.llopis@protonmail.com'));
                        break;
                    default:
                        break;
                }
                exit;
            }
        }, 10);
    }
    /**
     * @return CodersLogin
     */
    public static final function instance() {
        return self::$_instance;
    }
}

/**
 * Inicializar aplicación
 */
CodersLogin::init();




