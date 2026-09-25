(function () {
    var raiz = document.querySelector('.mt-f');
    if (!raiz) {
        return;
    }
    var pestanas = raiz.querySelectorAll('[data-mt-tab]');
    var paneles = raiz.querySelectorAll('[data-mt-panel]');
    var barra = raiz.querySelector('.mt-f-tabs');

    // Se muestra una sola sección a la vez
    function mostrar(clave) {
        paneles.forEach(function (panel) {
            panel.hidden = panel.id !== 'mt-' + clave;
        });
        pestanas.forEach(function (pestana) {
            var activa = pestana.getAttribute('data-mt-tab') === clave;
            pestana.classList.toggle('activa', activa);
            pestana.setAttribute('aria-selected', activa ? 'true' : 'false');
        });
        var url = new URL(window.location.href);
        url.searchParams.set('ver', clave);
        url.searchParams.delete('producto');
        window.history.replaceState(null, '', url.toString());
    }

    raiz.addEventListener('click', function (evento) {
        var enlace = evento.target.closest('[data-mt-tab], [data-mt-ir]');
        if (!enlace) {
            return;
        }
        evento.preventDefault();
        mostrar(enlace.getAttribute('data-mt-tab') || enlace.getAttribute('data-mt-ir'));
        if (enlace.hasAttribute('data-mt-ir') && barra) {
            barra.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

    // Vista previa de la foto elegida antes de guardar
    raiz.querySelectorAll('input[type="file"][data-vista]').forEach(function (campo) {
        campo.addEventListener('change', function () {
            var archivo = campo.files && campo.files[0];
            var vista = document.getElementById(campo.getAttribute('data-vista'));
            if (!archivo || !vista) {
                return;
            }
            vista.src = URL.createObjectURL(archivo);
            vista.hidden = false;
            var zona = vista.closest('.mt-f-zona, .mt-f-foto');
            if (zona) {
                zona.classList.add('con-foto');
            }
            var tarjeta = campo.closest('.mt-f-producto');
            if (tarjeta) {
                tarjeta.classList.add('cambiado');
            }
        });
    });

    // Se marca la tarjeta con cambios sin guardar
    raiz.querySelectorAll('.mt-f-producto').forEach(function (tarjeta) {
        tarjeta.addEventListener('input', function () {
            tarjeta.classList.add('cambiado');
        });
    });

    // Colores del panel: vista previa inmediata con los mismos tonos que calcula el plugin
    function mezclar(hex, con, peso) {
        var a = hex.match(/\w\w/g).map(function (x) { return parseInt(x, 16); });
        var b = con.match(/\w\w/g).map(function (x) { return parseInt(x, 16); });
        return '#' + a.map(function (valor, i) {
            return ('0' + Math.round(valor * (1 - peso) + b[i] * peso).toString(16)).slice(-2);
        }).join('').toUpperCase();
    }

    function luz(hex) {
        return hex.match(/\w\w/g).reduce(function (suma, x, i) {
            var canal = parseInt(x, 16) / 255;
            canal = canal <= 0.03928 ? canal / 12.92 : Math.pow((canal + 0.055) / 1.055, 2.4);
            return suma + [0.2126, 0.7152, 0.0722][i] * canal;
        }, 0);
    }

    function contraste(a, b) {
        var la = luz(a);
        var lb = luz(b);
        return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
    }

    // Color del panel: si es muy claro se oscurece para que el texto blanco se lea
    function tonos(hex) {
        for (var i = 0; i < 25 && contraste(hex, '#FFFFFF') < 4.5; i++) {
            hex = mezclar(hex, '#000000', 0.08);
        }
        return [hex.toUpperCase(), mezclar(hex, '#000000', 0.2), mezclar(hex, '#FFFFFF', 0.88), mezclar(hex, '#000000', 0.3), mezclar(hex, '#FFFFFF', 0.2)];
    }

    // Fondo de la tienda: si es muy oscuro se aclara para que el texto se lea
    function fondoLegible(hex) {
        for (var i = 0; i < 30 && contraste(hex, '#2B2118') < 7; i++) {
            hex = mezclar(hex, '#FFFFFF', 0.1);
        }
        return hex.toUpperCase();
    }

    function aplicarPaleta(valores) {
        ['--mc-v', '--mc-v2', '--mc-v3', '--mc-g1', '--mc-g2'].forEach(function (nombre, i) {
            document.documentElement.style.setProperty(nombre, valores[i]);
        });
    }

    // Cada grupo de muestras tiene su lápiz, que abre el selector de color del navegador
    function prepararGrupo(grupo, alElegir, ajustar) {
        if (!grupo) {
            return;
        }
        var selector = grupo.querySelector('.mt-f-selector');
        var lapiz = grupo.querySelector('.mt-f-lapiz');
        grupo.querySelectorAll('input[type="radio"]').forEach(function (opcion) {
            opcion.addEventListener('change', function () {
                alElegir(opcion.getAttribute('data-valor') ? opcion.getAttribute('data-valor') : ajustar(selector.value), opcion);
            });
        });
        if (selector && lapiz) {
            selector.addEventListener('input', function () {
                lapiz.querySelector('input[type="radio"]').checked = true;
                var valor = ajustar(selector.value);
                lapiz.style.setProperty('--muestra', Array.isArray(valor) ? valor[0] : valor);
                alElegir(valor);
            });
        }
    }

    prepararGrupo(raiz.querySelector('.mt-f-colores:not(.mt-f-fondos)'), function (valor) {
        aplicarPaleta(Array.isArray(valor) ? valor : valor.split(','));
    }, tonos);
    prepararGrupo(raiz.querySelector('.mt-f-fondos'), function (valor) {
        document.body.style.backgroundColor = valor;
    }, fondoLegible);

    // Se evita que un formulario se envíe dos veces
    raiz.querySelectorAll('form').forEach(function (formulario) {
        formulario.addEventListener('submit', function () {
            var boton = formulario.querySelector('button[type="submit"]');
            if (boton) {
                boton.disabled = true;
                boton.classList.add('cargando');
                boton.textContent = 'Guardando…';
            }
        });
    });

    // Producto que viene desde el botón «Editar» de la tienda
    var resaltado = raiz.querySelector('.mt-f-producto.resaltado');
    if (resaltado) {
        resaltado.scrollIntoView({ block: 'center' });
        var precio = resaltado.querySelector('input[name="precio"]');
        if (precio) {
            precio.focus({ preventScroll: true });
        }
    }
})();
