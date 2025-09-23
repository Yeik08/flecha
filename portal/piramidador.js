//cambiar de fija a proyectada
document.addEventListener('DOMContentLoaded', function() {
    // Seleccionamos el checkbox por su ID real (switch-label)
    const switchCheckbox = document.getElementById('switch-label');
    // Seleccionamos el input por su ID (mecanica)
    const mecanicaInput = document.getElementById('mecanica');

    actualizarTooltip();

    switchCheckbox.addEventListener('change', function() {
        // Actualizamos el valor y placeholder simultáneamente
        mecanicaInput.value = this.checked ? 'PROYECTADA' : 'FIJA';
        mecanicaInput.placeholder = this.checked ? 'PROYECTADA' : 'FIJA';

        actualizarTooltip();
    });
});



// Funcion para validar el metodo de piramidacion...
function valida_metodo() {
    // leemos todos los valores del formulario enviado..
    const periodi = document.getElementById("periodicidad").value;
    const s_ingresado = document.getElementById("salario").value;
    const pr_ingresado = document.getElementById("priesgo").value;
    const metodo = document.getElementById("piramida").value;
    const subsidio_anual = document.getElementById("id_vsubanual").value;

    var isr_determinado = document.getElementById("v_isr");
    var sub_determindado = document.getElementById("v_sub");
    var isrr_determinado = document.getElementById("v_isrr");
    var imss_empleado = document.getElementById("v_imss");
    var neto_calculado = document.getElementById("v_neto");


    // Variables para el calculo de isr..

    var nuevo_salario = document.getElementById("v_sala");
    var limitesuperior = document.getElementById("v_ls");
    var limiteinferior = document.getElementById("v_lf");
    var cuotafija = document.getElementById("v_cufij");
    var porcentaje = document.getElementById("v_porc");
    
    // Variables para el calculo de imss empleado..

    var exempleado = document.getElementById("ex_emple");
    var presemple= document.getElementById("pres_emple");
    var gastemple= document.getElementById("gast_emple");
    var invaemple= document.getElementById("inva_emple");
    var cesemple= document.getElementById("cen_emple");


    // variables para el calculo de imss patron..

    var expatron = document.getElementById("ex_patr");
    var prespatron = document.getElementById("pres_patr");
    var gastpatron = document.getElementById("gast_patr");
    var rtpatron = document.getElementById("rt_patr");
    var invapatron = document.getElementById("inv_patr");
    var guarpatron = document.getElementById("guar_patr");
    var retpatron = document.getElementById("ret_patr");
    var infonapatron = document.getElementById("infona_patr");
    var cenpatron = document.getElementById("cen_patr");



    var cufija = document.getElementById("cufija");
    var imss_patron = document.getElementById("v_imssp");

    var isn_patron = document.getElementById("v_isn");
    var neto_patron = document.getElementById("v_netop");


    // Evaluamos los valores mimimos que el usuario debe ingresar.. 

    if (isNaN(s_ingresado) || s_ingresado < 419.88 || isNaN(pr_ingresado) || pr_ingresado < 0.5) {
        alert("Por favor, ingresa un salario mayor al minimo vigente y una prima de riesgo mayor a 0.5");
        window.location.reload();//recarga la pagina
        return false;//evita el envio del formulario
    }
    else { }

    //Realizamos los calculos dependiendo la seleccion del usuario.. 
    switch (metodo) {
        case "nada":
            alert("Por favor, selecciona un método válido");
            window.location.reload();//recarga la pagina
            //return false; evita el envio del formulario
            break;
        case "Bruto a Neto":
            const n = [neto, bruto, riesgo, cuota_aplicada, imss, valor_subsidio, imssp, prop_i_in, isnp, costo, tisr, limite_superior, limite_inferior, salario, cuota_fija, porcentaje_sobreex] = bruto_neto(s_ingresado, pr_ingresado, periodi, subsidio_anual);
            isr_determinado.value = Number(cuota_aplicada.toFixed(2));
            sub_determindado.value = Number(valor_subsidio.toFixed(2));
            isrr_determinado.value = Number(tisr.toFixed(2));
            imss_empleado.value = Number(imss.toFixed(2));
            neto_calculado.value = Number(neto.toFixed(2));


        // valores de isr
            nuevo_salario.value = Number(salario);
            limitesuperior.value = Number(limite_superior.toFixed(2));
            limiteinferior.value = Number(limite_inferior);
            cuotafija.value = Number(cuota_fija.toFixed(2));
            porcentaje.value = Number(porcentaje_sobreex.toFixed(2));

        // valores de imss empleado
            exempleado.value = Number(proe_i_ex.toFixed(2));
            presemple.value = Number(proe_i_pd.toFixed(2));
            gastemple.value = Number(proe_i_gm.toFixed(2));
            invaemple.value = Number(proe_i_iv.toFixed(2));
            cesemple.value = Number(proe_i_cv.toFixed(2));


        // valores de imss patron
            expatron.value = Number(prop_i_ex.toFixed(2));
            prespatron.value = Number(prop_i_pd.toFixed(2));
            gastpatron.value = Number(prop_i_gm.toFixed(2));
            rtpatron.value = Number(prop_i_rt.toFixed(2));
            invapatron.value = Number(prop_i_iv.toFixed(2));
            guarpatron.value = Number(prop_i_gg.toFixed(2));
            retpatron.value = Number(prop_i_re.toFixed(2));
            infonapatron.value = Number(prop_i_in.toFixed(2));
            cenpatron.value = Number(prop_i_cv.toFixed(2));

            cufija.value = Number(pro_i_cf.toFixed(2));    
            imss_patron.value = Number(imssp.toFixed(2));
            isn_patron.value = Number(isnp.toFixed(2));
            neto_patron.value = Number(costo.toFixed(2));


            break;
        case "Neto a Bruto":
            var bruto_piramida = piramida(s_ingresado, pr_ingresado, periodi,subsidio_anual);
            neto_calculado.value = bruto_piramida;
            alert("El bruto estimado a considerar: " + bruto_piramida);
            break;



        default:
            alert("Favor de llenar todo el formulario");
            window.location.reload();//recarga la pagina
        //return false; evita el envio del formulario
    }

    return true;//permite envio exitoso
}

// Funcion para validar formulario de piramidador.. 
function formulario_piramidador() {
    const piramida = document.getElementById("piramida").value;
    const periodicidad = document.getElementById("periodicidad");
    const labelMensual = document.getElementById("vmensual");
    const labelNeto = document.getElementById("v_net");
    const opcionAnual = document.getElementById("idanual");
    const inputSubanual = document.getElementById("id_vsubanual");

    // Verificar si la opción seleccionada es "Bruto a Neto"
    if (piramida === "Bruto a Neto") {
        opcionAnual.style.display = "inline-block"; // Mostrar la opción Anual
        labelNeto.innerText = "Neto a pagar";
        switch (periodicidad.value) {
            case "vsemanal":
                labelMensual.innerText = "Base Semanal";
                inputSubanual.disabled = true;
                inputSubanual.value = "0.0";
                break;
            case "vquincenal":
                labelMensual.innerText = "Base Quincenal";
                inputSubanual.disabled = true;
                inputSubanual.value = "0.0";
                break;
            case "vmensual":
                labelMensual.innerText = "Base Mensual";
                inputSubanual.disabled = true;
                inputSubanual.value = "0.0";
                break;
            case "vanual":
                labelMensual.innerText = "Base Anual";
                inputSubanual.disabled = false; // Habilitar el input cuando se selecciona Anual
                break;
            default:
                labelMensual.innerText = "Bruto";
                inputSubanual.value = "0.0";
                inputSubanual.disabled = true;
                break;
        }
    } else if (piramida === "Neto a Bruto") {
        opcionAnual.style.display = "none"; // Ocultar la opción Anual
        inputSubanual.disabled = true; // Deshabilitar el input si no está seleccionado Anual
        inputSubanual.value = "0.0";
        labelMensual.innerText = "Neto";
        labelNeto.innerText = "Bruto estimado";
        switch (periodicidad.value) {
            case "vsemanal":
                labelMensual.innerText = "Neto Semanal";
                break;
            case "vquincenal":
                labelMensual.innerText = "Neto Quincenal";
                break;
            case "vmensual":
                labelMensual.innerText = "Neto Mensual";
                break;
            default:
                labelMensual.innerText = "Neto";
                break;
        }
        if (periodicidad.value === "vanual") {
            alert("¡Advertencia! No puedes seleccionar la opción 'Anual' cuando la pirámide es 'Neto a Bruto'.");
            location.reload();
        }
    } else {
        opcionAnual.style.display = "none"; // Ocultar la opción Anual
        inputSubanual.disabled = true; // Deshabilitar el input si no está seleccionado Anual
        labelMensual.innerText = "Bruto";
        labelNeto.innerText = "Neto a pagar";
    }
}


// funcion de calculo ISR, iMSS PATRON Y EMPLEADO
function toggle(section) {
    const additionalFields = document.getElementById('additionalFields');
    const additionalFields2 = document.getElementById('additionalFields2');
    const additionalFields3 = document.getElementById('additionalFields3');
    
    if (section === 'fields1') {
        additionalFields.style.display = additionalFields.style.display === 'none' ? 'block' : 'none';
    }
    
    if (section === 'fields2') {
        additionalFields2.style.display = additionalFields2.style.display === 'none' ? 'block' : 'none';
    }

    if (section === 'fields3') {
        additionalFields3.style.display = additionalFields3.style.display === 'none' ? 'block' : 'none';
    }
}


function actualizarTooltip() {
    const mecanica = document.getElementById('mecanica').value;
    const tooltip = document.getElementById('v_sala_tooltip');
    tooltip.textContent = mecanica === 'FIJA' ? 'Salario fijo' : 'Salario Mensualizado';
}