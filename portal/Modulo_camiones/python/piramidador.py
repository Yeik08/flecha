import math





def bruto_neto(bruto, riesgo, periodicidad, sub, mecanica):

    salario_ingresado = bruto
    salario = 0
    dias = 0
    tabla_mensual = []
    tabla_mensual_sup = []
    cuota_mensual = []
    porc_mensual = []
    ing_sub_mensual = []
    can_sub_mensual = []

# Determinamos la mecánica de cálculo y la periodicidad
    if mecanica == "PROYECTADA":
        if periodicidad == "vsemanal":
            dias = 7
            salario = (bruto / 7) * 30
            periodicidad_calculo_isr = "vmensual"
        elif periodicidad == "vquincenal":
            dias = 15
            salario = bruto * 2
            periodicidad_calculo_isr = "vmensual"
        elif periodicidad == "vmensual":
            dias = 30
            salario = bruto
            periodicidad_calculo_isr = "vmensual"
        elif periodicidad == "vanual":
            dias = 365 # Asumiendo 365 días para anual
            salario = bruto
            periodicidad_calculo_isr = "vanual" # Se mantiene anual para la tabla ISR
        
        # Tabla mensual
        tabla_mensual = [0.01, 746.05, 6332.06, 11128.02, 12935.83, 15487.72, 31236.50, 49233.01, 93993.91, 125325.21, 375975.62]
        tabla_mensual_sup = [746.04, 6332.05, 11128.00, 12935.8, 15487.7, 31236.5, 49233.00, 93993.9, 125325, 375976, 10000000000]
        cuota_mensual = [0.00, 14.32, 371.83, 893.63, 1182.88, 1640.18, 5004.12, 9236.89, 22665.17, 32691.18, 117912.32]
        porc_mensual = [1.92, 6.40, 10.88, 16.00, 17.92, 21.36, 23.52, 30.00, 32.00, 34.00, 35.00]
        ing_sub_mensual = [1768.96, 2653.38, 3472.84, 3537.87, 4446.15, 4717.18, 5335.42, 6224.67, 7113.90, 7382.33]
        can_sub_mensual = [407.02, 406.83, 406.62, 392.77, 382.46, 354.23, 324.87, 294.63, 253.54, 217.61, 0.00]

    elif mecanica == "FIJA":
        if periodicidad == "vsemanal":
            dias = 7
            salario = bruto
            tabla_mensual = [0.01, 171.79, 1458.04, 2562.36, 2978.65, 3566.23, 7192.65, 11336.58, 21643.31, 28857.79, 86573.35]
            tabla_mensual_sup = [171.78, 1458.03, 2562.35, 2978.64, 3566.22, 7192.64, 11336.57, 21643.30, 28857.78, 86573.34, 10000000000]
            cuota_mensual = [0.00, 3.29, 85.61, 205.80, 272.37, 377.65, 1152.27, 2126.95, 5218.92, 7527.59, 27150.83]
            porc_mensual = [1.92, 6.40, 10.88, 16.00, 17.92, 21.36, 23.52, 30.00, 32.00, 34.00, 35.00]
            ing_sub_mensual = [407.33, 610.96, 799.68, 814.66, 1023.75, 1228.57, 1433.32, 1638.07, 1699.88]
            can_sub_mensual = [93.73, 93.66, 93.66, 90.44, 88.06, 81.55, 74.83, 67.83, 58.38, 50.12, 0.00]
        elif periodicidad == "vquincenal":
            dias = 15
            salario = bruto
            tabla_mensual = [0.01, 368.11, 3124.36, 5490.76, 6382.81, 7641.91, 15412.81, 24292.66, 46378.51, 61838.11, 185514.31]
            tabla_mensual_sup = [368.10, 3124.35, 5490.75, 6382.80, 7641.90, 15412.80, 24292.65, 46378.50, 61838.10, 185514.30, 10000000000]
            cuota_mensual = [0.00, 7.05, 183.45, 441.00, 583.65, 809.25, 2469.15, 4557.75, 11183.40, 16130.55, 58180.35]
            porc_mensual = [1.92, 6.40, 10.88, 16.00, 17.92, 21.36, 23.52, 30.00, 32.00, 34.00, 35.00]
            ing_sub_mensual = [872.85, 1309.20, 1713.60, 1745.70, 2193.75, 2327.55, 2632.65, 3071.40, 3510.15, 3642.60]
            can_sub_mensual = [200.85, 200.70, 200.70, 193.80, 188.70, 174.75, 160.35, 145.35, 125.10, 107.40, 0.00]
        elif periodicidad == "vmensual":
            dias = 30
            salario = bruto
            tabla_mensual = [0.01, 746.05, 6332.06, 11128.02, 12935.83, 15487.72, 31236.50, 49233.01, 93993.91, 125325.21, 375975.62]
            tabla_mensual_sup = [746.04, 6332.05, 11128.00, 12935.8, 15487.7, 31236.5, 49233.00, 93993.9, 125325, 375976, 10000000000]
            cuota_mensual = [0.00, 14.32, 371.83, 893.63, 1182.88, 1640.18, 5004.12, 9236.89, 22665.17, 32691.18, 117912.32]
            porc_mensual = [1.92, 6.40, 10.88, 16.00, 17.92, 21.36, 23.52, 30.00, 32.00, 34.00, 35.00]
            ing_sub_mensual = [1768.96, 2653.38, 3472.84, 3537.87, 4446.15, 4717.18, 5335.42, 6224.67, 7113.90, 7382.33]
            can_sub_mensual = [407.02, 406.83, 406.62, 392.77, 382.46, 354.23, 324.87, 294.63, 253.54, 217.61, 0.00]
        elif periodicidad == "vanual":
            dias = 365 # Asumiendo 365 días para anual
            salario = bruto
            tabla_mensual = [0.01, 8952.50, 75984.56, 133536.08, 155229.81, 185852.58, 374837.89, 590796.00, 1127926.85, 1503902.47, 4511707.38]
            tabla_mensual_sup = [2238.12, 75984.55, 133536.07, 155229.80, 185852.57, 374837.88, 590795.99, 1127926.84, 1503902.46, 4511707.37, 10000000000]
            cuota_mensual = [0.00, 171.88, 4461.94, 10723.55, 14194.54, 19682.13, 60049.40, 110842.74, 271981.99, 392294.17, 1414947.85]
            porc_mensual = [1.92, 6.40, 10.88, 16.00, 17.92, 21.3, 23.52, 30.00, 32.00, 34.00, 35.00]

    # Tablas subsidio
    tabla_subsidio_mensual_inf = [0.01, 10171.01]
    tabla_subsidio_mensual_sup = [10171.00, 100000000000]
    subsidio_isr = [475.00, 0.0]

    # Variables Globales estaticas
    FACTOR_INTEGRACION = 1.0493   # Factor de integracion para 2025
    UMA = 113.14   # Valor de UMA 2025
    ISN_PORCENTAJE = 4.00 / 100   # Porcentaje de ISN CDMX 2025
    TOPE_UMAS_IMSS = UMA * 25

    if mecanica == "PROYECTADA":
        sbc = salario_ingresado * FACTOR_INTEGRACION   # Calculo de Salario Base de Cotizacion
    elif mecanica == "FIJA":
        sbc = salario * FACTOR_INTEGRACION   # Calculo de Salario Base de Cotizacion

    sdi = sbc / dias   # Calculo de Salario diario integrado

    if sdi > TOPE_UMAS_IMSS:
        sdi = TOPE_UMAS_IMSS

    # Porcentajes a aplicar cuotas IMSS
    PORCENTAJE_CUOTA_FIJA = 20.4 / 100 #Cuota fija del 20.4% para 2025
    PORCENTAJE_P_I_EX = 1.10 / 100 #excedente cuota fija patron
    PORCENTAJE_E_I_EX = 0.40 / 100 #excedente cuota fija empleado
    PORCENTAJE_P_I_PD = 0.70 / 100 # Prestaciones en dinero patron
    PORCENTAJE_E_I_PD = 0.25 / 100 # Prestaciones en dinero empleado
    PORCENTAJE_P_I_GM = 1.05 / 100 # Gastos medicos para pensionados patron
    PORCENTAJE_E_I_GM = 0.375 / 100 # Gastos medicos para pensionados empleado
    PORCENTAJE_P_I_RT = riesgo / 100 # Riesgo de trabajo patron
    PORCENTAJE_P_I_IV = 1.75 / 100 # Invalidez y vida patron
    PORCENTAJE_E_I_IV = 0.625 / 100 # Invalidez y vida empleado
    PORCENTAJE_P_I_GG = 1.00 / 100 # Guarderia patron
    PORCENTAJE_P_I_RE = 2.00 / 100 # Retiro patron
    PORCENTAJE_P_I_IN = 5.00 / 100 # Invalidez patron
    PORCENTAJE_E_I_CV = 1.125 / 100 # censetia y vejez

    # Calculo de ISR
    limite_inferior = 0
    limite_superior = 0
    cuota_fija = 0
    porcentaje_sobreex = 0

    for i in range(len(tabla_mensual)):
        if tabla_mensual[i] <= salario <= tabla_mensual_sup[i]:
            limite_inferior = tabla_mensual[i]
            limite_superior = tabla_mensual_sup[i]
            cuota_fija = cuota_mensual[i]
            porcentaje_sobreex = porc_mensual[i]
            break

    # Buscamos el salario en la tabla de subsidio para determinar si es acreedor
    limite_inf_subsidio = 0
    limite_sup_subsidio = 0
    valor_subsidio = float(sub)   # Usamos el valor de subsidio pasado como argumento
    
    excedente = 0
    porcentaje_excedente = 0
    cuota_aplicada = 0
    isr_calculado = 0 # Renombrado para evitar confusión con el isr final
    proe_i_ex = 0
    prop_i_ex = 0
    proe_i_pd = 0
    prop_i_pd = 0
    proe_i_gm = 0
    prop_i_gm = 0
    prop_i_rt = 0
    proe_i_iv = 0
    prop_i_iv = 0
    prop_i_gg = 0
    prop_i_re = 0
    prop_i_in = 0
    ccv = 0
    prop_i_cv = 0
    proe_i_cv = 0
    imssp = 0
    neto = 0
    imss = 0
    isnp = 0
    costo = 0
    tisr = 0
    pro_i_cf = 0

    # Se calcula el excedente, restando el salario menos el limite inferior
    excedente = salario - limite_inferior
    # Se calcula el porcentaje de excedente multiplicando el excedente anteriormente calculado por el % obtenido de la tabla de isr /100
    porcentaje_excedente = (excedente * porcentaje_sobreex) / 100
    # Se calcula la cuota fija sumando el porcentaje de excedente mas la cuota obtenida en la tabla de isr
    cuota_aplicada_base = porcentaje_excedente + cuota_fija
    
    isr_calculado = (salario - cuota_aplicada_base) + valor_subsidio # Este isr_calculado no se usa para el tisr final.

    # Se calcula el subsidio al empleo
    if valor_subsidio <= 0:
        for j in range(len(tabla_subsidio_mensual_sup)):
            if salario <= tabla_subsidio_mensual_sup[j]:
                limite_inf_subsidio = tabla_subsidio_mensual_inf[j]
                limite_sup_subsidio = tabla_subsidio_mensual_sup[j]
                valor_subsidio = subsidio_isr[j]
                break

    # ISR PROYECTADO
    isr4 = 0
    if mecanica == "PROYECTADA":
        # EL salario debe ser mensualizado
        # Buscar el limite inferior en la tabla mensual con salario mensualizado
        # resta de salario mensualizado menos limite inferior
        isr1 = salario - limite_inferior
        # multiplicar por porcentaje de tablas
        isr2 = isr1 * (porcentaje_sobreex / 100)
        # despues sumar mas cuota fija de tablas
        isr3 = cuota_fija + isr2
        # de la suma entre 30* dias
        isr4 = (isr3 / 30) * dias
    
    # SUBSIDIO PROYECTADO
    sub_proyectado = 0
    if mecanica == "PROYECTADA":
        sub_proyectado = (valor_subsidio / 30) * dias

    if mecanica == "PROYECTADA":
        cuota_aplicada = isr4
        valor_subsidio = sub_proyectado
    elif mecanica == "FIJA":
        cuota_aplicada = cuota_aplicada_base
        valor_subsidio = valor_subsidio

    # calculo de cuota imss
    # Primer Bloque: Cuota Fija
    pro_i_cf = (dias * UMA) * PORCENTAJE_CUOTA_FIJA

    # Segundo bloque: Excedente de cuota fija
    z = UMA * 3
    if sdi >= (UMA * 3):   # si el sbc supera 3 veces la uma
        # calculo de excedente patron
        prop_i_ex = sdi - (UMA * 3)
        prop_i_ex = prop_i_ex * dias
        prop_i_ex = prop_i_ex * PORCENTAJE_P_I_EX
        # Calculo de excedente empleado
        proe_i_ex = sdi - (UMA * 3)
        proe_i_ex = proe_i_ex * dias
        proe_i_ex = proe_i_ex * PORCENTAJE_E_I_EX

    # Tercer bloque: Prestaciones en dinero
    # Calculo de parte patronal
    prop_i_pd = (sdi * dias) * PORCENTAJE_P_I_PD
    # Calculo de parte empleado
    proe_i_pd = (sdi * dias) * PORCENTAJE_E_I_PD

    # Cuarto bloque: Gastos medicos para pensionados
    prop_i_gm = (sdi * dias) * PORCENTAJE_P_I_GM
    proe_i_gm = (sdi * dias) * PORCENTAJE_E_I_GM

    # Quinto bloque: Riesgo de trabajo
    prop_i_rt = (sdi * dias) * PORCENTAJE_P_I_RT

    # Sexto Bloque: Invalidez y vida
    prop_i_iv = (sdi * dias) * PORCENTAJE_P_I_IV
    proe_i_iv = (sdi * dias) * PORCENTAJE_E_I_IV

    # Septimo Bloque: Guarderia
    prop_i_gg = (sdi * dias) * PORCENTAJE_P_I_GG

    # Octavo bloque: Retiro
    prop_i_re = (sdi * dias) * PORCENTAJE_P_I_RE

    # Noveno bloque: infonavit
    prop_i_in = (sdi * dias) * PORCENTAJE_P_I_IN

    # Onceavo bloque: Cesentia y vejez
    # Tablas de cesentia
    tabla_mensual_cv_inf = [248.93, 251.41, 283.98, 340.55, 397.12, 453.69]
    tabla_mensual_cv_sup = [248.93, 282.85, 339.42, 395.99, 452.56, 10000000000]
    tabla_mensual_cv_cv = [3.15, 4.954, 5.307, 5.559, 5.747, 6.422]
    
    for k in range(len(tabla_mensual_cv_sup)):
        if sdi <= tabla_mensual_cv_sup[k]:

            ccv = tabla_mensual_cv_cv[k]
            break

    # Cesentia y vejez patronal y empleado
    proe_i_cv = (sdi * dias) * PORCENTAJE_E_I_CV
    prop_i_cv = (sdi * dias) * ccv / 100

    # Calculo de ISN 4% CDMX 2025
    isnp = salario_ingresado * ISN_PORCENTAJE

    # Calculo de IMSS empleado
    imss = proe_i_ex + proe_i_pd + proe_i_gm + proe_i_iv + proe_i_cv

    # Calculo IMSS para patron
    imssp = pro_i_cf + prop_i_ex + prop_i_pd + prop_i_gm + prop_i_rt + prop_i_iv + prop_i_gg + prop_i_cv + prop_i_re + prop_i_in
    costo = imssp + isnp + prop_i_in

    # Total de ISR menos el subsidio al empleo
    tisr = cuota_aplicada - valor_subsidio
    
    if valor_subsidio >= cuota_aplicada:
        tisr = 0

    # Neto a pagar para empleado
    if mecanica == "PROYECTADA":
        nuevo_neto = salario_ingresado - (tisr + imss)
    elif mecanica == "FIJA":
        nuevo_neto = (salario - tisr) - imss

    # Impresion de valores en consola para validar en caso de requerir una revision detallada
    print("++++ INICIO de proceso Bruto a Neto ++++")
    print(f"Salario capturado: {salario}")
    print(f"Salario original: {salario_ingresado}")
    print(f"Factor de integracion capturado: {FACTOR_INTEGRACION}")
    print(f"Prima de riesgo capturada: {riesgo}")
    print(f"SDI a considerar: {sdi}")
    print(f"el SBC a considerar: {sbc}")
    print(f"Salario para cálculo ISR: {salario}")
    print(f"TABLA QUE CONSIDERA: {tabla_mensual}")
    print(f"ISR PROYECTADOS: {isr4}")
    print(f"Limite inferior: {limite_inferior}")
    print(f"Limite superior: {limite_superior}")
    print(f"Cuota fija: {cuota_fija}")
    print(f"Porcentaje: {porcentaje_sobreex}")
    print(f"Limite inferior de subsidio: {limite_inf_subsidio}")
    print(f"Limite superior de subsidio: {limite_sup_subsidio}")
    print(f"Valor de subsidio: {valor_subsidio}")
    print(f"Excedente: {excedente}")
    print(f"Porcentaje de excedente: {porcentaje_excedente}")
    print(f"ISR a aplicar: {cuota_aplicada}")
    print(f"Subsidio calculado: {valor_subsidio}")
    print(f"Salario menos ISR: {isr_calculado}")
    print(" /// DETERMINACION CUOTAS IMSS /// ")
    print(f"Salario Base Mensual: {salario}")
    print(f"Cuota fija determinada: {pro_i_cf}")
    print(f"SDI: {sdi} condicion 3 veces la uma: {z}")
    print(f"Excedente empleado: {proe_i_ex}")
    print(f"Excedente patron: {prop_i_ex}")
    print(f"Prestaciones en dinero empleado: {proe_i_pd}")
    print(f"Prestaciones en dinero patron: {prop_i_pd}")
    print(f"Gastos medicos para pensionados empleado: {proe_i_gm}")
    print(f"Gastos medicos para pensionados patron: {prop_i_gm}")
    print(f"Riesgo de trabajo patron: {prop_i_rt}")
    print(f"Invalidez y vida empleado: {proe_i_iv}")
    print(f"Invalidez y vida patron: {prop_i_iv}")
    print(f"Guarderia patron: {prop_i_gg}")
    print(f"Retiro patron: {prop_i_re}")
    print(f"Infonavit patron: {prop_i_in}")
    print(f"Porcentaje a aplicar (CCV): {ccv}")
    print(f"Cesentia y vejez patronal: {prop_i_cv}")
    print(f"Cesentia y vejez empleado: {proe_i_cv}")
    print(f"Suma de imss patron: {imssp}")
    print(f"Suma de imss empleado: {imss}")
    print(f"NETO!! :{nuevo_neto}")

    return (nuevo_neto, bruto, riesgo, cuota_aplicada, imss, valor_subsidio, imssp, prop_i_in, isnp, costo, tisr,
            limite_superior, limite_inferior, salario, cuota_fija, porcentaje_sobreex, proe_i_ex, proe_i_pd,
            proe_i_gm, proe_i_iv, proe_i_cv, prop_i_ex, prop_i_pd, prop_i_gm, prop_i_rt, prop_i_iv, prop_i_gg,
            prop_i_re, prop_i_in, prop_i_cv, pro_i_cf)



# Ejemplo
#salario_bruto_ejemplo = 6000
#riesgo_ejemplo = 0.50 # Porcentaje de riesgo
#periodicidad_ejemplo = "vquincenal"
#subsidio_ejemplo = 0.0 # Subsidio calculado anualmente, 0 si no aplica o se calcula dentro
#mecanica_ejemplo = "FIJA" # "PROYECTADA" o "FIJA"

#resultados = bruto_neto(salario_bruto_ejemplo, riesgo_ejemplo, periodicidad_ejemplo, subsidio_ejemplo, mecanica_ejemplo)


def piramida(neto_objetivo, riesgo, periodicidad, sub, mecanica):

    bruto_inicial_para_calculo = neto_objetivo 
    
    # Usando *resto_de_valores para capturar el resto de los elementos
    nuevo_neto, nuevo_bruto, *resto_de_valores = bruto_neto(bruto_inicial_para_calculo, riesgo, periodicidad, sub, mecanica)
    
    centavo = 0.10
    
    # nuevo_neto ya es un flotante si bruto_neto devuelve flotantes, pero lo mantenemos por seguridad si vienen de otra fuente
    nuevo_neto = float(nuevo_neto)
    
    while True:
        # Usando *resto_de_valores de nuevo
        nuevo_neto, nuevo_bruto, *resto_de_valores = bruto_neto(nuevo_bruto, riesgo, periodicidad, sub, mecanica)
        
        nuevo_bruto += centavo
        
        # Redondear a 2 decimales 
        nuevo_bruto = round(nuevo_bruto, 2)
        nuevo_neto = round(nuevo_neto, 2)

        if nuevo_neto >= neto_objetivo:
            break
            
    return nuevo_bruto


# Ejemplo de uso de piramida
neto_objetivo_ejemplo = 9930.81  
riesgo_piramida = 0.50
periodicidad_piramida = "vquincenal"
subsidio_piramida = 0.0
mecanica_piramida = "FIJA"

bruto_requerido = piramida(neto_objetivo_ejemplo, riesgo_piramida, periodicidad_piramida, subsidio_piramida, mecanica_piramida)

print(f"\nPara un neto objetivo de {neto_objetivo_ejemplo:.2f}, el bruto requerido es: {bruto_requerido:.2f}")