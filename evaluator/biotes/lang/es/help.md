# Manual de BIOTES

**BIOTES** (Basic Input/Output Test Evaluation System — Sistema Básico de Evaluación de Entrada/Salida) es una funcionalidad proporcionada por **VPL**, disponible tanto de forma nativa como a través de un subcomplemento evaluador.
Este sistema permite a los profesores evaluar automáticamente los programas de los estudiantes definiendo casos de prueba que especifican la entrada del programa y la salida esperada.

## Introducción

Este documento describe el lenguaje utilizado por BIOTES para definir los casos de prueba.
Los profesores, usando este lenguaje, escriben los casos de prueba en el archivo `vpl_evaluate.cases` (menú de acción "Casos de prueba") para evaluar los programas de los estudiantes.

El lenguaje utiliza sentencias con el formato `"nombre_de_sentencia = valor"`.
Dependiendo del tipo de sentencia, el valor puede ocupar una sola línea o varias.
Un valor multilínea termina cuando aparece otra sentencia.
Este comportamiento limita el contenido válido de los valores de las sentencias.
El nombre de la sentencia **no distingue entre mayúsculas y minúsculas**.
Un caso de prueba básico incluye un nombre de caso, la entrada que queremos proporcionar al programa del estudiante y la salida que esperamos de él.
También se pueden configurar otros aspectos, como la penalización por fallos, el tiempo límite, etc.
VPL ejecutará la evaluación utilizando los casos definidos en `vpl_evaluate.cases` y generará un informe con los casos fallidos y la calificación obtenida.

---

## Pruebas básicas

### Case (Caso)

Esta sentencia inicia la definición de un nuevo caso e indica su descripción.

```
Formato: "Case = descripción del caso de prueba"
```

La descripción del caso ocupa solo una línea.
Esta descripción aparecerá en el informe si el caso falla.

**Ejemplo:**

![Definición mínima de casos de prueba](@@@IMAGESPATH@@@/basic_case_definition.png)

![Informe de evaluación mostrando el caso de prueba fallido](@@@IMAGESPATH@@@/basic_case_fail.png)

![Informe de evaluación mostrando el caso de prueba superado](@@@IMAGESPATH@@@/basic_case_pass.png)

---

### Input (Entrada)

Esta sentencia define el texto que se enviará al programa del estudiante como entrada.
Cada caso requiere **una y solo una** sentencia de entrada.
Su valor **puede ocupar varias líneas**.
El sistema no controla si el programa del estudiante lee o no la entrada.

```
Formato: "Input = texto"
```

**Ejemplo 1:**

![Ejemplo de entrada con una línea de números](@@@IMAGESPATH@@@/basic_input_definition1.png)

**Ejemplo 2:**

![Ejemplo de entrada con varias líneas de números](@@@IMAGESPATH@@@/basic_input_definition2.png)

**Ejemplo 3:**

![Ejemplo de entrada con una línea de texto](@@@IMAGESPATH@@@/basic_input_definition3.png)

**Ejemplo 4:**

![Ejemplo de entrada con varias líneas de texto](@@@IMAGESPATH@@@/basic_input_definition4.png)

---

### Output (Salida)

La sentencia de salida define una posible salida correcta del programa del estudiante para la entrada del caso actual.
Un caso de prueba puede tener **una o varias** sentencias de salida, pero al menos una.
Si la salida del programa coincide con alguna de las salidas esperadas, el caso se considera superado; de lo contrario, falla.
Existen cuatro tipos de valores para una sentencia de salida: números, texto, texto exacto y expresión regular.

```
Formato: "Output = valor"
```

El valor de la salida **puede ocupar varias líneas**.

---

#### Comprobación solo de números

Para definir este tipo de salida, deben usarse únicamente números en la sentencia `Output`.
Los valores pueden ser enteros o decimales.
Este tipo de comprobación analiza los números en la salida del programa del estudiante, ignorando el resto del texto.
La salida del programa se filtra, eliminando todo texto no numérico.
Finalmente, el sistema compara los números resultantes con los esperados, usando una tolerancia para números decimales.

**Ejemplo 1:**

![Ejemplo de salida numérica](@@@IMAGESPATH@@@/basic_input_definition1.png)

![Programa incorrecto para salida numérica](@@@IMAGESPATH@@@/basic_input_definition1_fail.png)

![Programa correcto para salida numérica](@@@IMAGESPATH@@@/basic_input_definition1_pass.png)

**Ejemplo 2:**

![Ejemplo de salida con números decimales](@@@IMAGESPATH@@@/basic_output_numbers2.png)

![Programa incorrecto con números decimales](@@@IMAGESPATH@@@/basic_output_numbers2_fail.png)

![Programa correcto con números decimales](@@@IMAGESPATH@@@/basic_output_numbers2_pass.png)

---

#### Comprobación de texto

Este tipo de salida realiza una **comparación no estricta de texto**, considerando solo las palabras de la salida del programa.
La comparación es **insensible a mayúsculas/minúsculas** y **omite signos de puntuación, espacios, tabulaciones y saltos de línea**.
Para definir este tipo de comprobación, debe usarse texto que no sea exclusivamente numérico, ni comience con una barra `/`, ni esté entre comillas dobles.
El sistema elimina la puntuación, los espacios, las tabulaciones y los saltos de línea, dejando separadores entre palabras.
Finalmente, compara de forma insensible a mayúsculas el texto resultante con la salida esperada.

**Ejemplo:**

![Ejemplo de salida tipo texto](@@@IMAGESPATH@@@/basic_output_text1.png)

![Programa del estudiante que coincide con la salida definida](@@@IMAGESPATH@@@/basic_output_text1_program.png)

![Programa que pasa la prueba](@@@IMAGESPATH@@@/basic_output_text1_pass.png)

---

#### Comprobación de texto exacto

Este tipo de comprobación verifica el **texto exacto** de la salida del programa del estudiante.
Para definirlo, el texto debe estar **entre comillas dobles**.
El sistema compara la salida del programa con el texto definido (tras eliminar las comillas).

**Ejemplo 1:**

![Ejemplo de salida de texto exacto](@@@IMAGESPATH@@@/basic_output_exactext1.png)

![Salida del programa que coincide](@@@IMAGESPATH@@@/basic_output_exactext1_pass.png)

**Ejemplo 2:**

![Otro ejemplo de texto exacto](@@@IMAGESPATH@@@/basic_output_exactext2.png)

![Salida coincidente](@@@IMAGESPATH@@@/basic_output_exactext2_pass.png)

---

### Comprobación mediante expresión regular

Este tipo de comprobación se define comenzando el valor de salida con una barra `/` y terminándolo con otra `/`, pudiendo incluir modificadores opcionales.
El formato es similar al de las expresiones regulares literales de JavaScript, pero utiliza **expresiones regulares POSIX**.

**Ejemplo:**

![Ejemplo de salida tipo expresión regular](@@@IMAGESPATH@@@/basic_output_regex1.png)

![Salida del programa que coincide (ejemplo 1)](@@@IMAGESPATH@@@/basic_output_regex1_pass1.png)

![Salida del programa que coincide (ejemplo 2)](@@@IMAGESPATH@@@/basic_output_regex1_pass2.png)

*Salida del programa del estudiante que coincide con la definición de salida.*

---

### Comprobación de múltiples salidas

La definición de un caso de prueba puede incluir varias sentencias de salida; si alguna coincide, el caso se considera superado.

**Ejemplo 1:**

![Ejemplo de múltiples salidas de diferentes tipos](@@@IMAGESPATH@@@/basic_multioutput1.png)

![Salida del programa coincidente (ejemplo 1)](@@@IMAGESPATH@@@/basic_multioutput1_pass1.png)

![Salida del programa coincidente (ejemplo 2)](@@@IMAGESPATH@@@/basic_multioutput1_pass2.png)

![Salida del programa coincidente (ejemplo 3)](@@@IMAGESPATH@@@/basic_multioutput1_pass3.png)

*Salida del programa que coincide con la definición.*

![Salida que no coincide (ejemplo 1)](@@@IMAGESPATH@@@/basic_multioutput1_fail1.png)

![Salida que no coincide (ejemplo 2)](@@@IMAGESPATH@@@/basic_multioutput1_fail2.png)

*Salida del programa que no coincide con la definición.*

**Ejemplo 2:**

![Ejemplo con múltiples salidas numéricas](@@@IMAGESPATH@@@/basic_multioutput2.png)

---

### Penalizaciones y calificación final

Un caso de prueba falla si su salida no coincide con alguna esperada.
Por defecto, la penalización aplicada cuando un caso falla es `rango_de_nota / número_de_casos`.
Las penalizaciones de todos los casos fallidos se suman para obtener la penalización total.
La calificación final es la nota máxima menos la penalización total.
La calificación nunca será inferior a la nota mínima ni superior a la máxima de la actividad VPL.

---

## Pruebas avanzadas

### Penalización personalizada

El evaluador puede personalizar la penalización de un caso mediante la siguiente sentencia:

```
Formato: "Grade reduction = [ valor | porcentaje% ]"
```

La penalización puede ser un valor absoluto o un porcentaje.
La nota final será la máxima nota menos la penalización total.
Si el resultado es menor que la nota mínima, se aplicará esta última.

**Ejemplo:**

![Ejemplo de penalización personalizada](@@@IMAGESPATH@@@/advanced_grade_reduction1.png)

![Salida de programa que falla la evaluación](@@@IMAGESPATH@@@/advanced_grade_reduction1_fail.png)

---

### Control de mensajes en el informe de salida

Cuando un caso falla, BIOTES añade al informe los detalles de la entrada, salida esperada y salida obtenida.
Si el caso contiene la sentencia `"Fail message"`, el sistema mostrará ese mensaje en lugar del informe detallado.

Esto permite al evaluador ocultar los datos del caso, evitando que el estudiante deduzca los valores de entrada/salida y “fuerce” una solución.
Si el caso contiene `"Fail message"` y falla, solo se mostrará ese mensaje.

```
Formato: "Fail message = mensaje"
```

Esta sentencia debe ir **antes** de la entrada.

**Ejemplo:**

![Ejemplo de personalización de mensaje de fallo](@@@IMAGESPATH@@@/advanced_fail_message1.png)

![Programa que falla la evaluación](@@@IMAGESPATH@@@/advanced_fail_message1_fail.png)

---

### Ejecución de otro programa

El profesor puede usar otro programa para probar una característica distinta del programa del estudiante, por ejemplo, realizar análisis estático/dinámico (como *checkstyle* [#checkstyle] para verificar estilo Java).
La sentencia `"Program to run"` reemplaza el programa del estudiante por otro en ese caso de prueba.

```
Formato: "Program to run = ruta"
```

**Ejemplo:**

![Ejemplo del uso de "Program to run"](@@@IMAGESPATH@@@/advanced_program_to_run1.png)

> **Nota:** Si planea usar un script personalizado como en el ejemplo, recuerde marcarlo en los *archivos a conservar al ejecutar*.

---

### Argumentos del programa

Esta sentencia permite enviar información como argumentos de línea de comandos al programa del estudiante o al definido en `"Program to run"`.
Es compatible con la sentencia `Input`.

```
Formato: "Program arguments = arg1 arg2 …"
```

**Ejemplo 1:**

Ejemplo del uso de `"Program to run"` y `"Program arguments"` para verificar si el programa del estudiante crea un archivo con un nombre pasado como argumento.

![Ejemplo usando "Program arguments"](@@@IMAGESPATH@@@/advanced_program_arguments1.png)

![Código del script check\_file\_exist.sh](@@@IMAGESPATH@@@/check_file_exist_code.png)

**Ejemplo 2:**

Ejemplo del uso de `"Program to run"` y `"Program arguments"` para evaluar una consulta SQL usando distintos conjuntos de datos.

![Ejemplo usando "Program to run" y "Program arguments"](@@@IMAGESPATH@@@/advanced_program_arguments2.png)

> **Nota:** Si planea usar un script personalizado como en el ejemplo, recuerde marcarlo en los *archivos a conservar al ejecutar*.

---

### Código de salida esperado

Esta sentencia define el código de salida esperado de la ejecución del programa.
El caso se considera superado si el código coincide o si alguna salida coincide.

```
Formato: "Expected exit code = número"
```

El siguiente ejemplo muestra cómo las sentencias `"Program to run"` y `"Program arguments"` pueden ejecutar distintos programas:
el primero renombra un archivo, el segundo lo compila y el tercero ejecuta el programa resultante.

![Ejemplo usando sentencias de código de salida](@@@IMAGESPATH@@@/advanced_exit_code1.png)

---

### Variación

Esta sentencia indica que el caso de prueba solo debe aplicarse si la variación indicada fue asignada al estudiante actual.
Si la variación no coincide, el caso se ignora.

```
Formato: "Variation = identificación"
```

**Ejemplo:**

![Ejemplo del uso de "Variation"](@@@IMAGESPATH@@@/advanced_variation1.png)

---

### Referencias

[#checkstyle] [http://checkstyle.sourceforge.net/](http://checkstyle.sourceforge.net/)

Para más información sobre VPL, visite la [página principal de VPL](https://vpl.dis.ulpgc.es/) o la [página del complemento de VPL en Moodle](https://moodle.org/plugins/mod_vpl).

---

## Licencia y autoría

© Copyright 2021, Juan Carlos Rodríguez-del-Pino [jc.rodriguezdelpino@ulpgc.es](mailto:jc.rodriguezdelpino@ulpgc.es).

Esta documentación está licenciada bajo una
[Licencia Creative Commons Atribución/Reconocimiento-NoComercial-SinDerivados 4.0 Internacionalnternacional](https://creativecommons.org/licenses/by-nc-nd/4.0/deed.es).

[![Licencia CC BY-NC-ND 4.0](https://licensebuttons.net/l/by-nc-nd/4.0/88x31.png)](https://creativecommons.org/licenses/by-nc-nd/4.0/deed.es)
