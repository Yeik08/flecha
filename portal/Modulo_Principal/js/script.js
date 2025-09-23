// Script para manejar el menú desplegable

document.addEventListener('DOMContentLoaded', function() {

    // Manejar dropdowns
	const dropdowns = document.querySelectorAll('.dropdown');
		
	dropdowns.forEach(dropdown => {
		const toggle = dropdown.querySelector('.dropdown-toggle');
			
		toggle.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			dropdown.classList.toggle('active');
				
			// Cerrar otros dropdowns
			dropdowns.forEach(other => {
				if(other !== dropdown) other.classList.remove('active');
			});
		});
	});

	// Cerrar dropdowns al hacer clic fuera
	document.addEventListener('click', function(e) {
		if(!e.target.closest('.dropdown')) {
			dropdowns.forEach(dropdown => {
				dropdown.classList.remove('active');
				});
			}
		});
	});

// MINI RELOJ

function actualizarReloj() {
  const horaElemento = document.getElementById('hora-actual');
  const iconoElemento = document.getElementById('icono-dia-noche1');
  const now = new Date();

  const hora = now.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

    horaElemento.textContent = hora;

  const horaActual = now.getHours();
  iconoElemento.textContent = (horaActual >= 6 && horaActual < 18) ? '🌞' : '🌙';
}

setInterval(actualizarReloj, 1000);
actualizarReloj();


// Boton para checar - mini reloj

document.addEventListener('DOMContentLoaded', () => {
  const relojMenu = document.getElementById('reloj');
  const formularioReloj = document.getElementById('formulario-reloj');
  const botonChecar = document.getElementById('boton-checar1');

  relojMenu.addEventListener('click', () => {
    formularioReloj.style.display = (formularioReloj.style.display === 'none') ? 'block' : 'none';
  });

  botonChecar.addEventListener('click', () => {
    const hora = new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
    alert(`¡Entrada/Salida registrada a las ${hora}!`);
  });
});

    


//  ------------------------------------------- MODULOS  ------------------------------------------

// NOTIFICACIONES 

// Datos simulados de notificaciones son arreglos de objetos
const notificacionesData = {
    vacaciones: [
        { mensaje: "Tienes 3 días pendientes de aprobación", link: "vacaciones.php" },
        { mensaje: "Tu solicitud de vacaciones fue aceptada", link: "vacaciones.php" }
    ],
    facturas: [
        { mensaje: "Factura 00235 está pendiente de validación", link: "facturas.php" },
        { mensaje: "Nueva factura disponible para revisión", link: "facturas.php" }
    ],
    rh: [
        { mensaje: "Actualización de datos personales requerida", link: "rh.php" }
    ],
    ti: [
        { mensaje: "Soporte programado para el viernes", link: "ti.php" }
    ],
    legal: [
        { mensaje: "Revisión de contrato pendiente", link: "legal/legal.php" }
    ],
    contabilidad: [
        { mensaje: "Recibo de nómina de este mes disponible", link: "contabilidad.php" }
    ],
    gastos: [
        { mensaje: "Reporte de gastos del mes listo", link: "gastos.php" }
    ],
    permisos: [
        { mensaje: "Solicitud de permiso pendiente de revisión", link: "permisos.php" }
    ]
};

// Función para mostrar notificaciones
function mostrarNotificaciones(area) {
    // contenedor de notificaciones
    const contenedor = document.getElementById("contenedor-notificaciones");
    // Limpiar el contenedor antes de mostrar nuevas notificaciones
    contenedor.innerHTML = "";
    // Verificar si hay notificaciones para el área seleccionada
    if (notificacionesData[area] && notificacionesData[area].length > 0) {
        notificacionesData[area].forEach((notif, index) => {
            // Crear tarjeta de notificación
            const tarjeta = document.createElement("div");
            tarjeta.className = "tarjeta-notificacion";
            tarjeta.innerHTML = `
                <p>${notif.mensaje}</p>
                <div class="botones-notificacion">
                    <a href="${notif.link}" class="btn-vermas" target="_blank">Ver más</a>
                    <button onclick="eliminarNotificacion('${area}', ${index})" class="btn-eliminar">Eliminar</button>
                </div>
            `;
            // Agregar la tarjeta al contenedor
            contenedor.appendChild(tarjeta);
        });
    } else {
        // Si no hay notificaciones, mostrar mensaje
        contenedor.innerHTML = "<p style='text-align:center;'>No hay notificaciones en esta área.</p>";
    }
}


// Función para actualizar los contadores de notificaciones
function actualizarBadges() {  //busca elementos con id badge-area y le asigna el numero de notificaciones
    for (const area in notificacionesData) {
        const badge = document.getElementById(`badge-${area}`);
        if (badge) {
            const count = notificacionesData[area].length;
            
            if (count > 0) {
                badge.textContent=count;   //coloco el numero
                badge.style.display = "inline-block"; // Mostrar badge si hay notificaciones
            } else {
                badge.style.display = "none";   
            }
        }
    }
}


// Ejecutar al cargar la página
window.onload = actualizarBadges;

// Ejecutar cada vez que eliminas notificaciones
function eliminarNotificacion(area, index) {
    // sacamos la notificacion del arreglo
    notificacionesData[area].splice(index, 1);
    // Actualizar el contador de notificaciones
    mostrarNotificaciones(area);
    actualizarBadges();
}


















