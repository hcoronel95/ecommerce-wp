<?php
// Plantilla del resumen de Mi cuenta (reemplaza a myaccount/dashboard.php de WooCommerce)
if (!defined('ABSPATH')) {
    exit;
}
$usuario = wp_get_current_user();
$nombre = $usuario->first_name ? $usuario->first_name : $usuario->display_name;
$pedidos = wc_get_orders(array('customer_id' => get_current_user_id(), 'limit' => -1, 'return' => 'ids', 'status' => mercadito_numero_estados()));
$en_curso = wc_get_orders(array('customer_id' => get_current_user_id(), 'limit' => -1, 'return' => 'ids', 'status' => array('wc-pending', 'wc-on-hold', 'wc-processing')));
$ultimo = $pedidos ? wc_get_order($pedidos[0]) : null;
$tienda = wc_get_page_permalink('shop');
$accesos = array(
    array('bolsa', 'Mis pedidos', mercadito_plural(count($pedidos), 'pedido realizado', 'pedidos realizados'), wc_get_account_endpoint_url('orders')),
    array('ubicacion', 'Mi dirección', 'Dónde recibes tus compras', wc_get_account_endpoint_url('edit-address')),
    array('persona', 'Mis datos', 'Nombre, correo y contraseña', wc_get_account_endpoint_url('edit-account')),
    array('tienda', 'Seguir comprando', 'Mira todos los productos', $tienda),
);
?>
<div class="mc-resumen">
    <div class="mc-hero">
        <div>
            <span class="mc-kicker"><?php echo esc_html(wp_date('l, j \\d\\e F')); ?></span>
            <h2>Hola, <?php echo esc_html($nombre); ?></h2>
            <p><?php echo esc_html(mercadito_opcion('bienvenida')); ?></p>
            <?php if ($en_curso) : ?>
                <span class="mc-hero-chip"><?php echo esc_html(mercadito_plural(count($en_curso), 'pedido en curso', 'pedidos en curso')); ?></span>
            <?php endif; ?>
        </div>
        <a class="mc-btn mc-btn-claro" href="<?php echo esc_url($tienda); ?>"><?php echo mercadito_icono('tienda'); ?>Ir a la tienda</a>
    </div>

    <?php if (mercadito_es_encargado()) : ?>
        <div class="mc-encargado">
            <span class="mc-icono mc-icono-naranja"><?php echo mercadito_icono('tienda'); ?></span>
            <div class="mc-encargado-txt">
                <strong>Administras esta tienda</strong>
                <span><?php echo esc_html(mercadito_plural(mercadito_contar_pedidos('on-hold'), 'pago por confirmar', 'pagos por confirmar') . ' · ' . mercadito_plural(mercadito_contar_pedidos('processing'), 'pedido por entregar', 'pedidos por entregar')); ?></span>
            </div>
            <a class="mc-btn mc-btn-verde" href="<?php echo esc_url(wc_get_account_endpoint_url('mi-tienda')); ?>">Abrir Mi tienda<?php echo mercadito_icono('flecha'); ?></a>
        </div>
    <?php endif; ?>

    <div class="mc-accesos">
        <?php foreach ($accesos as $acceso) : ?>
            <a class="mc-acceso" href="<?php echo esc_url($acceso[3]); ?>">
                <span class="mc-icono"><?php echo mercadito_icono($acceso[0]); ?></span>
                <strong><?php echo esc_html($acceso[1]); ?></strong>
                <small><?php echo esc_html($acceso[2]); ?></small>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="mc-bloque">
        <div class="mc-bloque-cab">
            <h3>Tu último pedido</h3>
            <?php if ($ultimo) : ?>
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>">Ver todos</a>
            <?php endif; ?>
        </div>
        <?php if ($ultimo) :
            $miniaturas = array();
            foreach ($ultimo->get_items() as $item) {
                $producto = is_callable(array($item, 'get_product')) ? $item->get_product() : null;
                if ($producto && $producto->get_image_id()) {
                    $miniaturas[] = wp_get_attachment_image_url($producto->get_image_id(), 'thumbnail');
                }
            }
            ?>
            <div class="mc-pedido">
                <?php if ($miniaturas) : ?>
                    <div class="mc-miniaturas">
                        <?php foreach (array_slice($miniaturas, 0, 3) as $src) : ?>
                            <img src="<?php echo esc_url($src); ?>" alt="">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="mc-pedido-datos">
                    <strong>Pedido N.º <?php echo esc_html($ultimo->get_order_number()); ?></strong>
                    <small><?php echo esc_html(wc_format_datetime($ultimo->get_date_created())); ?> · <?php echo esc_html(mercadito_plural($ultimo->get_item_count(), 'producto', 'productos')); ?> · <?php echo wp_kses_post($ultimo->get_formatted_order_total()); ?></small>
                </div>
                <span class="mc-estado <?php echo esc_attr($ultimo->get_status()); ?>"><?php echo esc_html(wc_get_order_status_name($ultimo->get_status())); ?></span>
                <a class="mc-btn mc-btn-verde" href="<?php echo esc_url($ultimo->get_view_order_url()); ?>">Ver pedido</a>
            </div>
        <?php else : ?>
            <div class="mc-vacio">
                <span class="mc-icono"><?php echo mercadito_icono('bolsa'); ?></span>
                <strong>Todavía no tienes pedidos</strong>
                <small>Cuando compres algo, aquí verás cómo va tu pedido.</small>
                <a class="mc-btn mc-btn-verde" href="<?php echo esc_url($tienda); ?>">Ir a la tienda</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
do_action('woocommerce_account_dashboard');
