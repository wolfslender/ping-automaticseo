<?php
/**
 * Plugin Name: Auto Ping Publisher SEO
 * Description: Realiza automáticamente un ping a los servicios de actualización cada vez que publicas o actualizas un post.
 * Version: 1.0.0
 * Author: Alexis Olivero
 * Author URI: https://oliverodev.com
 */

// Evitar acceso directo al archivo
if ( !defined( 'ABSPATH' ) ) {
    // Este chequeo asegura que el archivo no sea accedido directamente por razones de seguridad.
    exit;
}

// Hook para ejecutar el ping al publicar o actualizar un post
add_action( 'publish_post', 'auto_ping_published_post' );

function auto_ping_published_post( $post_ID ) {
    
    // Obtener la lista de servicios de ping configurados en WordPress
    $ping_services = get_option( 'ping_sites' );

    // Convertir la lista en un array
    $services = preg_split( "/(\r\n|\r|\n)/", $ping_services );

    // URL de tu sitio
    $blog_url = get_option( 'Agrega tu Sitio AQUI' );

    // URL del post publicado
    $post_url = get_permalink( $post_ID );

    foreach ( $services as $service ) {
        if ( filter_var( $service, FILTER_VALIDATE_URL ) ) {
            wp_remote_post( $service, array(
                'body' => array(
                    'blog_name' => get_option( 'blogname' ),
                    'blog_url'  => $blog_url,
                    'post_url'  => $post_url
                )
            ));
        }
    }

    return $post_ID;
}

// Activar el plugin y establecer servicios de ping predeterminados
register_activation_hook( __FILE__, 'auto_ping_set_default_services' );
function auto_ping_set_default_services() {
    $default_services = "http://rpc.pingomatic.com/\nhttp://rpc.twingly.com/\nhttp://blogsearch.google.com/ping/RPC2";
    if ( !get_option( 'ping_sites' ) ) {
        update_option( 'ping_sites', $default_services );
    }
}

// Añadir página de configuración del plugin
add_action( 'admin_menu', 'auto_ping_add_settings_page' );
function auto_ping_add_settings_page() {
    add_options_page( 
        'Configuración de Auto Ping', 
        'Auto Ping', 
        'manage_options', 
        'auto-ping-settings', 
        'auto_ping_settings_page' 
    );
}

function auto_ping_settings_page() {
    if ( isset( $_POST['auto_ping_services'] ) && current_user_can( 'manage_options' ) ) {
        // Sanitizar y validar los servicios de ping
        $services = sanitize_textarea_field( $_POST['auto_ping_services'] );
        $services_array = preg_split( "/(\r\n|\r|\n)/", $services );
        $valid_services = array_filter( $services_array, function( $service ) {
            return filter_var( $service, FILTER_VALIDATE_URL );
        });
        
        // Guardar solo servicios válidos
        update_option( 'ping_sites', implode( "\n", $valid_services ) );

        echo '<div class="updated"><p>Servicios de ping actualizados.</p></div>';
    }

    $ping_services = get_option( 'ping_sites', '' );
    ?>
    <div class="wrap">
        <h1>Configuración de Auto Ping</h1>
        <form method="post" action="">
            <label for="auto_ping_services">Servicios de Ping:</label><br>
            <textarea id="auto_ping_services" name="auto_ping_services" rows="10" cols="50"><?php echo esc_textarea( $ping_services ); ?></textarea><br>
            <p>Añade los servicios de ping que quieras usar, uno por línea.</p>
            <input type="submit" class="button button-primary" value="Guardar Configuración">
        </form>
    </div>
    <?php
}
