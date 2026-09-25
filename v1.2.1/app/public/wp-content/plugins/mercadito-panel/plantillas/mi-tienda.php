<?php
// Plantilla de «Mi tienda» dentro de Mi cuenta (solo para el encargado)
if (!defined('ABSPATH')) {
    exit;
}
$espera = mercadito_contar_pedidos('on-hold');
$proceso = mercadito_contar_pedidos('processing');
$total_productos = (int) wp_count_posts('product')->publish;
$pedidos = wc_get_orders(array('limit' => 10, 'orderby' => 'date', 'order' => 'DESC', 'status' => mercadito_numero_estados()));
$productos = wc_get_products(array('limit' => -1, 'status' => 'publish', 'orderby' => array('menu_order' => 'ASC', 'title' => 'ASC')));
$categorias = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
$categorias = is_wp_error($categorias) ? array() : $categorias;
$clientes = mercadito_clientes();
$qr = mercadito_qr_imagen();
$base = wc_get_account_endpoint_url('mi-tienda');
$nonce = '<input type="hidden" name="_mercadito" value="' . esc_attr(wp_create_nonce('mercadito_frontal')) . '">';
$resaltar = isset($_GET['producto']) ? absint($_GET['producto']) : 0;
$pestanas = array(
    'pedidos'   => array('Pedidos', 'bolsa', $espera + $proceso),
    'productos' => array('Mis productos', 'caja', 0),
    'nuevo'     => array('Agregar producto', 'mas', 0),
    'qr'        => array('Mi código QR', 'qr', 0),
    'clientes'  => array('Clientes', 'clientes', 0),
);
if (current_user_can('manage_woocommerce')) {
    $pestanas['personalizar'] = array('Personalizar', 'ajustes', 0);
}
$activa = isset($_GET['ver']) ? sanitize_key(wp_unslash($_GET['ver'])) : 'pedidos';
if (!isset($pestanas[$activa])) {
    $activa = 'pedidos';
}
$cifras = array(
    array('reloj', $espera, 'Pagos por confirmar', $espera > 0),
    array('camion', $proceso, 'Pedidos por entregar', $proceso > 0),
    array('caja', $total_productos, 'Productos en la tienda', false),
    array('dinero', wc_price(mercadito_ventas_mes()), 'Ventas de este mes', false),
);
?>
<div class="mt-f">
    <div class="mc-hero mt-f-hero">
        <div>
            <span class="mc-kicker"><?php echo esc_html(get_bloginfo('name')); ?></span>
            <h2><?php echo esc_html(mercadito_opcion('panel_titulo')); ?></h2>
            <p><?php echo esc_html(mercadito_opcion('panel_texto')); ?></p>
        </div>
        <div class="mt-f-hero-acciones">
            <a class="mc-btn mc-btn-claro" href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener"><?php echo mercadito_icono('externo'); ?>Ver mi tienda</a>
            <a class="mc-btn mc-btn-borde" href="<?php echo esc_url(mercadito_tienda_url()); ?>">Panel de WordPress</a>
        </div>
    </div>

    <div class="mt-f-cifras">
        <?php foreach ($cifras as $cifra) : ?>
            <div class="mt-f-cifra<?php echo $cifra[3] ? ' alerta' : ''; ?>">
                <span class="mc-icono"><?php echo mercadito_icono($cifra[0]); ?></span>
                <div><b><?php echo wp_kses_post((string) $cifra[1]); ?></b><span><?php echo esc_html($cifra[2]); ?></span></div>
            </div>
        <?php endforeach; ?>
    </div>

    <nav class="mt-f-tabs" role="tablist" aria-label="Secciones de Mi tienda">
        <?php foreach ($pestanas as $clave => $pestana) : ?>
            <a id="tab-<?php echo esc_attr($clave); ?>" class="<?php echo $clave === $activa ? 'activa' : ''; ?>" href="<?php echo esc_url(add_query_arg('ver', $clave, $base)); ?>" role="tab" aria-controls="mt-<?php echo esc_attr($clave); ?>" aria-selected="<?php echo $clave === $activa ? 'true' : 'false'; ?>" data-mt-tab="<?php echo esc_attr($clave); ?>">
                <?php echo mercadito_icono($pestana[1]); ?><span><?php echo esc_html($pestana[0]); ?></span>
                <?php if ($pestana[2] > 0) : ?><em class="mt-f-cuenta"><?php echo (int) $pestana[2]; ?></em><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <section id="mt-pedidos" class="mt-f-panel" role="tabpanel" aria-labelledby="tab-pedidos" data-mt-panel<?php echo 'pedidos' === $activa ? '' : ' hidden'; ?>>
        <div class="mt-f-cab">
            <div>
                <h3>Pedidos recientes</h3>
                <p>Confirma el pago cuando veas el dinero en tu cuenta y marca el pedido como entregado al terminar.</p>
            </div>
            <a class="mt-f-enlace" href="<?php echo esc_url(mercadito_orders_url()); ?>">Ver todos en el panel<?php echo mercadito_icono('flecha'); ?></a>
        </div>
        <?php if ($pedidos) : ?>
            <table class="mt-f-tabla">
                <thead>
                    <tr><th>Pedido</th><th>Cliente</th><th>Pago</th><th>Total</th><th>Estado</th><th><span class="screen-reader-text">Acciones</span></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido) :
                        $estado = $pedido->get_status();
                        $cliente = trim($pedido->get_billing_first_name() . ' ' . $pedido->get_billing_last_name());
                        $boton = null;
                        if ('on-hold' === $estado) {
                            $boton = array('processing', 'Confirmar pago', '');
                        } elseif ('processing' === $estado) {
                            $boton = array('completed', 'Marcar entregado', ' sec');
                        }
                        ?>
                        <tr>
                            <td data-label="Pedido"><strong class="mt-f-num">N.º <?php echo esc_html($pedido->get_order_number()); ?></strong><small><?php echo esc_html(wc_format_datetime($pedido->get_date_created(), 'd/m/Y · H:i')); ?></small></td>
                            <td data-label="Cliente"><?php echo esc_html($cliente ? $cliente : 'Sin nombre'); ?><?php if ($pedido->get_billing_phone()) : ?><small><?php echo esc_html($pedido->get_billing_phone()); ?></small><?php endif; ?></td>
                            <td data-label="Pago"><?php echo esc_html(mercadito_metodo_pago($pedido)); ?></td>
                            <td data-label="Total"><strong><?php echo wp_kses_post($pedido->get_formatted_order_total()); ?></strong></td>
                            <td data-label="Estado"><span class="mc-estado <?php echo esc_attr($estado); ?>"><?php echo esc_html(wc_get_order_status_name($estado)); ?></span></td>
                            <td class="mt-f-accion">
                                <?php if ($boton) : ?>
                                    <form method="post">
                                        <?php echo $nonce; ?>
                                        <input type="hidden" name="mercadito_accion" value="pedido_estado">
                                        <input type="hidden" name="pedido" value="<?php echo (int) $pedido->get_id(); ?>">
                                        <input type="hidden" name="estado" value="<?php echo esc_attr($boton[0]); ?>">
                                        <button type="submit" class="mt-f-btn<?php echo esc_attr($boton[2]); ?>"><?php echo esc_html($boton[1]); ?></button>
                                    </form>
                                <?php else : ?>
                                    <a class="mt-f-detalle" href="<?php echo esc_url($pedido->get_edit_order_url()); ?>">Ver detalle</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="mc-vacio">
                <span class="mc-icono"><?php echo mercadito_icono('bolsa'); ?></span>
                <strong>Todavía no hay pedidos</strong>
                <small>Cuando alguien compre, el pedido aparecerá aquí.</small>
            </div>
        <?php endif; ?>
    </section>

    <section id="mt-productos" class="mt-f-panel" role="tabpanel" aria-labelledby="tab-productos" data-mt-panel<?php echo 'productos' === $activa ? '' : ' hidden'; ?>>
        <div class="mt-f-cab">
            <div>
                <h3>Mis productos</h3>
                <p>Toca la foto para cambiarla, ajusta el nombre o el precio y pulsa «Guardar».</p>
            </div>
            <a class="mc-btn mc-btn-verde" href="<?php echo esc_url(add_query_arg('ver', 'nuevo', $base)); ?>" data-mt-ir="nuevo"><?php echo mercadito_icono('mas'); ?>Agregar producto</a>
        </div>
        <?php if ($productos) : ?>
            <div class="mt-f-productos">
                <?php foreach ($productos as $producto) :
                    $id = (int) $producto->get_id();
                    $imagen = $producto->get_image_id() ? wp_get_attachment_image_url($producto->get_image_id(), 'woocommerce_thumbnail') : '';
                    $agotado = !$producto->is_in_stock();
                    $categoria_actual = $producto->get_category_ids() ? (int) current($producto->get_category_ids()) : 0;
                    $descripcion = trim(wp_strip_all_tags($producto->get_short_description()));
                    ?>
                    <form id="p-<?php echo $id; ?>" class="mt-f-producto<?php echo $agotado ? ' agotado' : ''; ?><?php echo $resaltar === $id ? ' resaltado' : ''; ?>" method="post" enctype="multipart/form-data">
                        <?php echo $nonce; ?>
                        <input type="hidden" name="mercadito_accion" value="producto_editar">
                        <input type="hidden" name="producto" value="<?php echo $id; ?>">
                        <input class="mt-f-oculto" id="foto-<?php echo $id; ?>" type="file" name="foto_<?php echo $id; ?>" accept="image/*" data-vista="vista-<?php echo $id; ?>" aria-label="Cambiar la foto de <?php echo esc_attr($producto->get_name()); ?>">
                        <label class="mt-f-foto" for="foto-<?php echo $id; ?>">
                            <img id="vista-<?php echo $id; ?>" src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($producto->get_name()); ?>"<?php echo $imagen ? '' : ' hidden'; ?>>
                            <span class="mt-f-foto-accion"><?php echo mercadito_icono('foto'); ?>Cambiar foto</span>
                            <?php if ($agotado) : ?><em class="mt-f-sello">Agotado</em><?php endif; ?>
                        </label>
                        <div class="mt-f-cuerpo">
                            <p class="mt-f-campo">
                                <label for="nombre-<?php echo $id; ?>">Nombre</label>
                                <input id="nombre-<?php echo $id; ?>" type="text" name="nombre" value="<?php echo esc_attr($producto->get_name()); ?>" required>
                            </p>
                            <p class="mt-f-campo">
                                <label for="precio-<?php echo $id; ?>">Precio</label>
                                <span class="mt-f-precio"><span>$</span><input id="precio-<?php echo $id; ?>" type="number" name="precio" step="0.01" min="0" inputmode="decimal" value="<?php echo esc_attr($producto->get_regular_price()); ?>" required></span>
                            </p>
                            <label class="mt-f-switch"><input type="checkbox" name="disponible" value="1"<?php checked(!$agotado); ?>><span>Disponible para vender</span></label>
                            <details class="mt-f-mas">
                                <summary>Categoría y descripción</summary>
                                <p class="mt-f-campo">
                                    <label for="categoria-<?php echo $id; ?>">Categoría</label>
                                    <select id="categoria-<?php echo $id; ?>" name="categoria">
                                        <option value="0">Sin cambio</option>
                                        <?php foreach ($categorias as $categoria) : ?>
                                            <option value="<?php echo (int) $categoria->term_id; ?>"<?php selected($categoria_actual, (int) $categoria->term_id); ?>><?php echo esc_html($categoria->name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </p>
                                <p class="mt-f-campo">
                                    <label for="descripcion-<?php echo $id; ?>">Descripción corta</label>
                                    <textarea id="descripcion-<?php echo $id; ?>" name="descripcion" rows="3"><?php echo esc_textarea($descripcion); ?></textarea>
                                    <input type="hidden" name="descripcion_original" value="<?php echo esc_attr($descripcion); ?>">
                                </p>
                            </details>
                            <div class="mt-f-acciones">
                                <button type="submit" class="mt-f-btn">Guardar</button>
                                <a class="mt-f-ver" href="<?php echo esc_url($producto->get_permalink()); ?>" target="_blank" rel="noopener">Ver<?php echo mercadito_icono('externo'); ?></a>
                            </div>
                        </div>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="mc-vacio">
                <span class="mc-icono"><?php echo mercadito_icono('caja'); ?></span>
                <strong>Todavía no hay productos</strong>
                <small>Agrega el primero en la pestaña «Agregar producto».</small>
            </div>
        <?php endif; ?>
    </section>

    <section id="mt-nuevo" class="mt-f-panel" role="tabpanel" aria-labelledby="tab-nuevo" data-mt-panel<?php echo 'nuevo' === $activa ? '' : ' hidden'; ?>>
        <div class="mt-f-cab">
            <div>
                <h3>Agregar un producto</h3>
                <p>Con nombre, precio y foto ya se puede vender. Aparece en la tienda apenas lo publicas.</p>
            </div>
        </div>
        <form class="mt-f-nuevo" method="post" enctype="multipart/form-data">
            <?php echo $nonce; ?>
            <input type="hidden" name="mercadito_accion" value="producto_nuevo">
            <div class="mt-f-campos">
                <p class="mt-f-campo ancho">
                    <label for="mt-nombre">Nombre del producto</label>
                    <input id="mt-nombre" type="text" name="nombre" required placeholder="Ej.: Pan de yuca">
                </p>
                <p class="mt-f-campo">
                    <label for="mt-precio">Precio</label>
                    <span class="mt-f-precio"><span>$</span><input id="mt-precio" type="number" name="precio" step="0.01" min="0" inputmode="decimal" required placeholder="2.50"></span>
                </p>
                <p class="mt-f-campo">
                    <label for="mt-categoria">Categoría</label>
                    <select id="mt-categoria" name="categoria">
                        <option value="0">Sin categoría</option>
                        <?php foreach ($categorias as $categoria) : ?>
                            <option value="<?php echo (int) $categoria->term_id; ?>"><?php echo esc_html($categoria->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p class="mt-f-campo ancho">
                    <label for="mt-descripcion">Descripción corta</label>
                    <textarea id="mt-descripcion" name="descripcion" rows="4" placeholder="Qué es, tamaño o peso y por qué es especial."></textarea>
                </p>
            </div>
            <div class="mt-f-lado">
                <span class="mt-f-etiqueta">Foto del producto</span>
                <input class="mt-f-oculto" id="mt-foto" type="file" name="foto" accept="image/*" data-vista="mt-foto-vista" aria-label="Foto del producto">
                <label class="mt-f-zona" for="mt-foto">
                    <img id="mt-foto-vista" alt="" hidden>
                    <span class="mt-f-zona-txt"><?php echo mercadito_icono('subir'); ?><strong>Toca para elegir la foto</strong><small>JPG o PNG, mejor si es cuadrada</small></span>
                </label>
            </div>
            <div class="mt-f-enviar">
                <button type="submit" class="mt-f-btn"><?php echo mercadito_icono('mas'); ?>Publicar producto</button>
            </div>
        </form>
    </section>

    <section id="mt-qr" class="mt-f-panel" role="tabpanel" aria-labelledby="tab-qr" data-mt-panel<?php echo 'qr' === $activa ? '' : ' hidden'; ?>>
        <div class="mt-f-cab">
            <div>
                <h3>Mi código QR</h3>
                <p>Aparece solo cuando el cliente elige «Pago con código QR» al terminar su compra.</p>
            </div>
        </div>
        <div class="mt-f-qr">
            <div class="mt-f-qr-vista">
                <?php if ($qr) : ?>
                    <img src="<?php echo esc_url($qr); ?>" alt="Código QR de pago actual">
                <?php else : ?>
                    <span class="mc-icono"><?php echo mercadito_icono('qr'); ?></span>
                <?php endif; ?>
                <small><?php echo $qr ? 'Así lo ven tus clientes' : 'Todavía no hay un código QR'; ?></small>
            </div>
            <form class="mt-f-qr-form" method="post" enctype="multipart/form-data">
                <?php echo $nonce; ?>
                <input type="hidden" name="mercadito_accion" value="qr">
                <ol class="mt-f-pasos">
                    <li>Guarda en tu celular la foto de tu código QR (DeUna o la app de tu banco).</li>
                    <li>Toca el recuadro de abajo y elige esa foto.</li>
                    <li>Pulsa «Guardar nuevo QR».</li>
                </ol>
                <input class="mt-f-oculto" id="mt-qr-archivo" type="file" name="qr_archivo" accept="image/*" required data-vista="mt-qr-vista" aria-label="Foto de tu código QR">
                <label class="mt-f-zona baja" for="mt-qr-archivo">
                    <img id="mt-qr-vista" alt="" hidden>
                    <span class="mt-f-zona-txt"><?php echo mercadito_icono('qr'); ?><strong>Toca para elegir la foto del QR</strong><small>JPG o PNG</small></span>
                </label>
                <div class="mt-f-enviar">
                    <button type="submit" class="mt-f-btn"><?php echo mercadito_icono('subir'); ?>Guardar nuevo QR</button>
                </div>
            </form>
        </div>
    </section>

    <section id="mt-clientes" class="mt-f-panel" role="tabpanel" aria-labelledby="tab-clientes" data-mt-panel<?php echo 'clientes' === $activa ? '' : ' hidden'; ?>>
        <div class="mt-f-cab">
            <div>
                <h3>Clientes</h3>
                <p>Quién te compró, cuántas veces y por cuánto. Salen de los pedidos, con cuenta o sin ella.</p>
            </div>
            <a class="mt-f-enlace" href="<?php echo esc_url(admin_url('admin.php?page=wc-admin&path=%2Fcustomers')); ?>">Informe completo<?php echo mercadito_icono('flecha'); ?></a>
        </div>
        <?php if ($clientes) : ?>
            <table class="mt-f-tabla">
                <thead>
                    <tr><th>Cliente</th><th>Teléfono</th><th>Pedidos</th><th>Total</th><th>Último pedido</th><th>Tipo</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente) : ?>
                        <tr>
                            <td data-label="Cliente"><strong><?php echo esc_html($cliente['nombre'] ? $cliente['nombre'] : 'Sin nombre'); ?></strong><small><?php echo esc_html($cliente['correo']); ?></small></td>
                            <td data-label="Teléfono"><?php echo esc_html($cliente['telefono'] ? $cliente['telefono'] : '—'); ?></td>
                            <td data-label="Pedidos"><?php echo (int) $cliente['pedidos']; ?></td>
                            <td data-label="Total"><strong><?php echo wp_kses_post(wc_price($cliente['total'])); ?></strong></td>
                            <td data-label="Último pedido"><?php echo esc_html(wc_format_datetime($cliente['ultimo'], 'd/m/Y')); ?></td>
                            <td data-label="Tipo"><span class="mc-chip<?php echo $cliente['cuenta'] ? ' verde' : ''; ?>"><?php echo $cliente['cuenta'] ? 'Con cuenta' : 'Invitado'; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="mc-vacio">
                <span class="mc-icono"><?php echo mercadito_icono('clientes'); ?></span>
                <strong>Todavía no hay clientes</strong>
                <small>Aparecen aquí con su primer pedido.</small>
            </div>
        <?php endif; ?>
    </section>

    <?php if (isset($pestanas['personalizar'])) :
        $color = mercadito_opcion('color');
        $paleta = mercadito_paleta();
        $hex = mercadito_opcion('color_hex') ? mercadito_opcion('color_hex') : $paleta[0];
        $logo = get_option('site_logo') ? wp_get_attachment_image_url((int) get_option('site_logo'), 'medium') : '';
        $pie_texto = mercadito_pie_texto();
        $fondo_actual = mercadito_estilo_color(array('color', 'background'));
        $fondo_actual = $fondo_actual ? $fondo_actual : '#FFF9F1';
        $fondo_clave = 'personalizado';
        foreach (mercadito_fondos() as $clave => $f) {
            if ($f[1] === $fondo_actual) {
                $fondo_clave = $clave;
            }
        }
        $botones_iguales = mercadito_estilo_color(array('elements', 'button', 'color', 'background')) === $paleta[0];
        $campo_oculto = '<input type="hidden" name="mercadito_accion" value="personalizar">';
        $pasos = array(1 => 'Nombre y logo', 2 => 'Colores', 3 => 'Fondo', 4 => 'Textos', 5 => 'WhatsApp y redes');
        ?>
        <section id="mt-personalizar" class="mt-f-panel" role="tabpanel" aria-labelledby="tab-personalizar" data-mt-panel<?php echo 'personalizar' === $activa ? '' : ' hidden'; ?>>
            <div class="mt-f-cab">
                <div>
                    <h3>Personalizar</h3>
                    <p>Cinco pasos para dejar la tienda a tu gusto. Cada tarjeta se guarda con su propio botón.</p>
                </div>
                <a class="mt-f-enlace" href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener">Ver cómo queda<?php echo mercadito_icono('externo'); ?></a>
            </div>
            <nav class="mt-f-indice" aria-label="Pasos de Personalizar">
                <?php foreach ($pasos as $n => $nombre_paso) : ?>
                    <a href="#mt-paso-<?php echo (int) $n; ?>"><span><?php echo (int) $n; ?></span><?php echo esc_html($nombre_paso); ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="mt-f-ajustes">
                <form id="mt-paso-1" class="mt-f-tarjeta" method="post" enctype="multipart/form-data">
                    <?php echo $nonce . $campo_oculto; ?>
                    <input type="hidden" name="seccion" value="tienda">
                    <div class="mt-f-tarjeta-cab"><span class="mt-f-paso">1</span><div><h4>Nombre y logo</h4><p>Salen arriba en todas las páginas y en el pie.</p></div></div>
                    <div class="mt-f-grupo dos">
                        <p class="mt-f-campo">
                            <label for="mt-p-nombre">Nombre de la tienda</label>
                            <input id="mt-p-nombre" type="text" name="nombre" value="<?php echo esc_attr(get_option('blogname')); ?>" maxlength="60" required>
                        </p>
                        <p class="mt-f-campo">
                            <label for="mt-p-frase">Frase corta</label>
                            <input id="mt-p-frase" type="text" name="frase" value="<?php echo esc_attr(get_option('blogdescription')); ?>" maxlength="120" placeholder="Ej.: Productos de nuestra tierra, a un clic">
                        </p>
                    </div>
                    <div class="mt-f-campo">
                        <span class="mt-f-etiqueta">Logo (opcional)</span>
                        <div class="mt-f-logo">
                            <input class="mt-f-oculto" id="mt-p-logo" type="file" name="logo" accept="image/*" data-vista="mt-p-logo-vista" aria-label="Logo de la tienda">
                            <label class="mt-f-logo-zona" for="mt-p-logo">
                                <img id="mt-p-logo-vista" src="<?php echo esc_url($logo); ?>" alt=""<?php echo $logo ? '' : ' hidden'; ?>>
                                <span><?php echo mercadito_icono('subir'); ?><?php echo $logo ? 'Cambiar logo' : 'Subir logo'; ?></span>
                            </label>
                            <?php if ($logo) : ?>
                                <label class="mt-f-check"><input type="checkbox" name="quitar_logo" value="1">Quitar el logo</label>
                            <?php endif; ?>
                        </div>
                        <small class="mt-f-nota">Mejor cuadrado y en PNG. Se ve a la izquierda del nombre.</small>
                    </div>
                    <div class="mt-f-enviar"><button type="submit" class="mt-f-btn">Guardar nombre y logo</button></div>
                </form>

                <form id="mt-paso-2" class="mt-f-tarjeta" method="post">
                    <?php echo $nonce . $campo_oculto; ?>
                    <input type="hidden" name="seccion" value="colores">
                    <div class="mt-f-tarjeta-cab"><span class="mt-f-paso">2</span><div><h4>Colores</h4><p>El color de tu panel y, si quieres, el de los botones de la tienda. Toca uno para verlo al instante.</p></div></div>
                    <div class="mt-f-colores" role="radiogroup" aria-label="Color del panel">
                        <?php foreach (mercadito_colores() as $clave => $c) : ?>
                            <label class="mt-f-color" style="--muestra:<?php echo esc_attr($c[1]); ?>">
                                <input type="radio" name="color" value="<?php echo esc_attr($clave); ?>" data-valor="<?php echo esc_attr(implode(',', array_slice($c, 1))); ?>"<?php checked($color, $clave); ?>>
                                <span class="mt-f-muestra"></span><small><?php echo esc_html($c[0]); ?></small>
                            </label>
                        <?php endforeach; ?>
                        <label class="mt-f-color mt-f-lapiz" style="--muestra:<?php echo esc_attr($hex); ?>">
                            <input type="radio" name="color" value="personalizado"<?php checked($color, 'personalizado'); ?>>
                            <span class="mt-f-muestra"><?php echo mercadito_icono('lapiz'); ?></span><small>Otro color</small>
                            <input class="mt-f-selector" type="color" name="color_hex" value="<?php echo esc_attr(strtolower($hex)); ?>" aria-label="Elegir cualquier color con el lápiz">
                        </label>
                    </div>
                    <div class="mt-f-muestra-hero" aria-hidden="true"><div><small><?php echo esc_html(get_bloginfo('name')); ?></small><strong><?php echo esc_html(mercadito_opcion('panel_titulo')); ?></strong></div><span>Botón</span></div>
                    <div class="mt-f-grupo">
                        <label class="mt-f-check"><input type="checkbox" name="botones_tienda" value="1"<?php checked($botones_iguales); ?>>Usar este color también en los botones y enlaces de toda la tienda</label>
                        <small class="mt-f-nota">Con el lápiz eliges cualquier color. Si es muy claro, se oscurece un poco para que las letras blancas se lean bien.</small>
                    </div>
                    <div class="mt-f-enviar"><button type="submit" class="mt-f-btn">Guardar color</button></div>
                </form>

                <form id="mt-paso-3" class="mt-f-tarjeta" method="post">
                    <?php echo $nonce . $campo_oculto; ?>
                    <input type="hidden" name="seccion" value="fondo">
                    <div class="mt-f-tarjeta-cab"><span class="mt-f-paso">3</span><div><h4>Fondo</h4><p>El color que va detrás de todas las páginas de la tienda.</p></div></div>
                    <div class="mt-f-colores mt-f-fondos" role="radiogroup" aria-label="Fondo de la tienda">
                        <?php foreach (mercadito_fondos() as $clave => $f) : ?>
                            <label class="mt-f-color" style="--muestra:<?php echo esc_attr($f[1]); ?>">
                                <input type="radio" name="fondo" value="<?php echo esc_attr($clave); ?>" data-valor="<?php echo esc_attr($f[1]); ?>"<?php checked($fondo_clave, $clave); ?>>
                                <span class="mt-f-muestra"></span><small><?php echo esc_html($f[0]); ?></small>
                            </label>
                        <?php endforeach; ?>
                        <label class="mt-f-color mt-f-lapiz" style="--muestra:<?php echo esc_attr($fondo_actual); ?>">
                            <input type="radio" name="fondo" value="personalizado"<?php checked($fondo_clave, 'personalizado'); ?>>
                            <span class="mt-f-muestra"><?php echo mercadito_icono('lapiz'); ?></span><small>Otro fondo</small>
                            <input class="mt-f-selector" type="color" name="fondo_hex" value="<?php echo esc_attr(strtolower($fondo_actual)); ?>" aria-label="Elegir cualquier color de fondo con el lápiz">
                        </label>
                    </div>
                    <small class="mt-f-nota">Es el mismo ajuste de Editar el sitio › Estilos › Colores › Fondo. Si eliges un fondo oscuro, se aclara para que el texto se lea.</small>
                    <div class="mt-f-enviar"><button type="submit" class="mt-f-btn">Guardar fondo</button></div>
                </form>

                <form id="mt-paso-4" class="mt-f-tarjeta" method="post">
                    <?php echo $nonce . $campo_oculto; ?>
                    <input type="hidden" name="seccion" value="textos">
                    <div class="mt-f-tarjeta-cab"><span class="mt-f-paso">4</span><div><h4>Textos</h4><p>Lo que leen tú y tus clientes. Si borras un texto, vuelve el original.</p></div></div>
                    <div class="mt-f-grupo dos">
                        <h5 class="mt-f-grupo-titulo">Tu panel «Mi tienda»</h5>
                        <p class="mt-f-campo">
                            <label for="mt-p-titulo">Título</label>
                            <input id="mt-p-titulo" type="text" name="panel_titulo" value="<?php echo esc_attr(mercadito_opcion('panel_titulo')); ?>" maxlength="60">
                        </p>
                        <p class="mt-f-campo">
                            <label for="mt-p-texto">Texto bajo el título</label>
                            <input id="mt-p-texto" type="text" name="panel_texto" value="<?php echo esc_attr(mercadito_opcion('panel_texto')); ?>" maxlength="140">
                        </p>
                    </div>
                    <div class="mt-f-grupo">
                        <h5 class="mt-f-grupo-titulo">Mi cuenta de tus clientes</h5>
                        <p class="mt-f-campo">
                            <label for="mt-p-acceso-titulo">Título de la pantalla para entrar</label>
                            <input id="mt-p-acceso-titulo" type="text" name="acceso_titulo" value="<?php echo esc_attr(mercadito_opcion('acceso_titulo')); ?>" maxlength="60">
                        </p>
                        <p class="mt-f-campo">
                            <label for="mt-p-acceso-texto">Texto de la pantalla para entrar</label>
                            <textarea id="mt-p-acceso-texto" name="acceso_texto" rows="2" maxlength="220"><?php echo esc_textarea(mercadito_opcion('acceso_texto')); ?></textarea>
                        </p>
                        <p class="mt-f-campo">
                            <label for="mt-p-bienvenida">Bienvenida dentro de Mi cuenta</label>
                            <textarea id="mt-p-bienvenida" name="bienvenida" rows="2" maxlength="200"><?php echo esc_textarea(mercadito_opcion('bienvenida')); ?></textarea>
                        </p>
                    </div>
                    <?php if (null !== $pie_texto) : ?>
                        <div class="mt-f-grupo">
                            <h5 class="mt-f-grupo-titulo">Pie de página</h5>
                            <p class="mt-f-campo">
                                <label for="mt-p-pie">Última línea del pie</label>
                                <input id="mt-p-pie" type="text" name="pie_texto" value="<?php echo esc_attr($pie_texto); ?>" maxlength="160">
                            </p>
                        </div>
                    <?php endif; ?>
                    <div class="mt-f-grupo">
                        <h5 class="mt-f-grupo-titulo">Páginas de la tienda</h5>
                        <small class="mt-f-nota">Toca una para editarla. Deja una línea en blanco entre párrafos y escribe <b>#</b> y un espacio al inicio de un subtítulo.</small>
                        <div class="mt-f-paginas">
                            <?php foreach (mercadito_paginas() as $clave => $titulo) :
                                $pagina = mercadito_pagina($clave);
                                $texto = $pagina ? mercadito_pagina_a_texto($pagina) : null;
                                $publicada = $pagina && 'publish' === $pagina->post_status;
                                ?>
                                <details class="mt-f-pagina">
                                    <summary><strong><?php echo esc_html($pagina ? get_the_title($pagina) : $titulo); ?></strong><span class="mc-chip<?php echo $publicada ? ' verde' : ''; ?>"><?php echo $pagina ? ($publicada ? 'Publicada' : 'Borrador') : 'No existe'; ?></span></summary>
                                    <?php if (!$pagina) : ?>
                                        <p class="mt-f-nota">Esta página todavía no existe. Se crea en WordPress › Páginas › Añadir.</p>
                                    <?php else : ?>
                                        <?php if (null !== $texto) : ?>
                                            <textarea name="pagina[<?php echo esc_attr($clave); ?>]" rows="12" aria-label="Texto de <?php echo esc_attr($titulo); ?>"><?php echo esc_textarea($texto); ?></textarea>
                                        <?php elseif ('contacto' === $clave) : ?>
                                            <p class="mt-f-nota">Se llena sola con tus datos del paso 5, «WhatsApp y redes».</p>
                                        <?php else : ?>
                                            <p class="mt-f-nota">Tiene botones, enlaces u otros bloques: se edita en WordPress para no romper su diseño.</p>
                                        <?php endif; ?>
                                        <div class="mt-f-pagina-acciones">
                                            <a class="mt-f-ver" href="<?php echo esc_url(get_permalink($pagina)); ?>" target="_blank" rel="noopener">Ver página<?php echo mercadito_icono('externo'); ?></a>
                                            <a class="mt-f-ver" href="<?php echo esc_url(get_edit_post_link($pagina->ID)); ?>">Editar en WordPress</a>
                                        </div>
                                    <?php endif; ?>
                                </details>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mt-f-enviar"><button type="submit" class="mt-f-btn">Guardar textos</button></div>
                </form>

                <form id="mt-paso-5" class="mt-f-tarjeta" method="post">
                    <?php echo $nonce . $campo_oculto; ?>
                    <input type="hidden" name="seccion" value="contacto">
                    <div class="mt-f-tarjeta-cab"><span class="mt-f-paso">5</span><div><h4>WhatsApp y redes</h4><p>Tus clientes te escriben con un toque. Lo que dejes vacío no se muestra.</p></div></div>
                    <div class="mt-f-grupo dos">
                        <h5 class="mt-f-grupo-titulo">WhatsApp</h5>
                        <p class="mt-f-campo">
                            <label for="mt-p-wa">Número de WhatsApp</label>
                            <input id="mt-p-wa" type="tel" name="whatsapp" inputmode="numeric" value="<?php echo esc_attr(mercadito_opcion('whatsapp')); ?>" placeholder="593991234567">
                            <small class="mt-f-nota">Con el código de Ecuador (593) y sin el 0 inicial: 0991234567 se escribe 593991234567.</small>
                        </p>
                        <p class="mt-f-campo">
                            <label for="mt-p-wa-texto">Mensaje que ya aparece escrito</label>
                            <input id="mt-p-wa-texto" type="text" name="whatsapp_texto" value="<?php echo esc_attr(mercadito_opcion('whatsapp_texto')); ?>" maxlength="140">
                        </p>
                    </div>
                    <div class="mt-f-grupo dos">
                        <h5 class="mt-f-grupo-titulo">Correo y horario</h5>
                        <p class="mt-f-campo">
                            <label for="mt-p-correo">Correo de contacto</label>
                            <input id="mt-p-correo" type="email" name="correo" value="<?php echo esc_attr(mercadito_opcion('correo')); ?>" placeholder="tutienda@correo.com">
                        </p>
                        <p class="mt-f-campo">
                            <label for="mt-p-horario">Horario de atención</label>
                            <input id="mt-p-horario" type="text" name="horario" value="<?php echo esc_attr(mercadito_opcion('horario')); ?>" maxlength="100" placeholder="Lunes a sábado, de 08:00 a 18:00">
                        </p>
                    </div>
                    <div class="mt-f-grupo tres">
                        <h5 class="mt-f-grupo-titulo">Redes sociales</h5>
                        <?php foreach (array('facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok') as $red => $nombre_red) : ?>
                            <p class="mt-f-campo">
                                <label for="mt-p-<?php echo esc_attr($red); ?>">Enlace de <?php echo esc_html($nombre_red); ?></label>
                                <input id="mt-p-<?php echo esc_attr($red); ?>" type="url" name="<?php echo esc_attr($red); ?>" value="<?php echo esc_attr(mercadito_opcion($red)); ?>" placeholder="https://www.<?php echo esc_attr($red); ?>.com/tutienda">
                            </p>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-f-enviar">
                        <button type="submit" class="mt-f-btn">Guardar contacto</button>
                        <?php if (mercadito_enlace_red('whatsapp')) : ?>
                            <a class="mt-f-ver" href="<?php echo esc_url(mercadito_enlace_red('whatsapp')); ?>" target="_blank" rel="noopener">Probar WhatsApp<?php echo mercadito_icono('externo'); ?></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </section>
    <?php endif; ?>

    <p class="mt-f-pie">¿Necesitas algo más, como borrar un producto o agregar varias fotos? <a href="<?php echo esc_url(mercadito_tienda_url()); ?>">Abre el panel de WordPress</a>.</p>
</div>
