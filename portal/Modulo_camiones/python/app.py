from flask import Flask, request, jsonify
# Importamos las funciones de nuestro módulo refactorizado
from piramidador import bruto_neto, piramida

app = Flask(__name__)

@app.route('/api/calcular-nomina', methods=['POST'])
def calcular_nomina_endpoint():
    try:
        # 1. Obtener los datos JSON enviados desde JavaScript
        data = request.get_json()

        # 2. Extraer las variables necesarias
        salario = data.get('salario')
        prima_riesgo = data.get('prima_riesgo')
        periodicidad = data.get('periodicidad')
        metodo = data.get('metodo')
        subsidio_anual = data.get('subsidio_anual', 0.0) # Valor por defecto
        mecanica = data.get('mecanica', 'FIJA') # Valor por defecto

        # Validar que los datos necesarios están presentes
        if not all([salario, prima_riesgo, periodicidad, metodo, mecanica]):
            return jsonify({"error": "Faltan datos en la petición"}), 400

        # 3. Realizar los cálculos usando las funciones importadas
        resultado = None
        if metodo == "Bruto a Neto":
            resultado = bruto_neto(
                bruto=float(salario),
                riesgo=float(prima_riesgo),
                periodicidad=periodicidad,
                sub=float(subsidio_anual),
                mecanica=mecanica
            )
        elif metodo == "Neto a Bruto":
            bruto_estimado = piramida(
                neto_objetivo=float(salario),
                riesgo=float(prima_riesgo),
                periodicidad=periodicidad,
                sub=float(subsidio_anual),
                mecanica=mecanica
            )
            # Para "Neto a Bruto", devolvemos solo el bruto estimado
            resultado = {"bruto_estimado": bruto_estimado}
        else:
            return jsonify({"error": "Método no válido"}), 400

        # 4. Enviar el diccionario de resultados de vuelta a JavaScript
        return jsonify(resultado)

    except Exception as e:
        print(f"Error en el servidor: {e}")
        return jsonify({"error": "Ocurrió un error interno en el servidor"}), 500

if __name__ == '__main__':
    app.run(debug=True, port=5000)



