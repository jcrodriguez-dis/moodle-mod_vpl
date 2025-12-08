## MANUAL DE SPRESAI

**SPRESAI** (Student Programming Review & Evaluation System using AI) Sistema de Revisión y Evaluación de Programación Estudiantil usando IA es un subplugin evaluador para **VPL** que utiliza inteligencia artificial para la evaluación de código.
Este sistema permite a los profesores evaluar automáticamente los programas de los estudiantes y proporcionar consejos útiles, correcciones de código o explicaciones detalladas usando modelos de IA.

⚠️ **Aviso Importante:** El uso de IA para la evaluación es inherentemente impreciso y debe usarse principalmente como una guía o generador de evaluaciones preliminares. Siempre revise las evaluaciones generadas por IA antes de finalizar las calificaciones.

---

### ❓ ¿Qué es SPRESAI?

SPRESAI es un marco flexible de evaluación impulsado por IA para envíos de programación escritos en casi cualquier lenguaje.
Se ejecuta como un subplugin evaluador de VPL para Moodle ([VPL][1]) y genera informes, retroalimentación y calificaciones utilizando modelos de lenguaje grandes.

Los objetivos del marco son:

* **Integración con VPL.** Conectar y usar dentro del familiar entorno VPL para Moodle.
* **Evaluación impulsada por IA.** Aprovechar modelos de lenguaje de última generación para evaluación inteligente de código.
* **Múltiples modos de evaluación.** Evaluar, explicar, proporcionar consejos o sugerir correcciones.
* **Prompts personalizables.** Prompts de IA totalmente personalizables para diferentes estrategias de evaluación.
* **Soporte multi-proveedor.** Funciona con OpenAI, Anthropic, Google, Mistral, Groq y muchos otros proveedores de IA a través de LiteLLM.
* **Enfocado en seguridad.** Protecciones integradas contra ataques de inyección de prompts.

---

### ⚡ Inicio rápido

1. **Instale SPRESAI** como un subplugin evaluador de VPL en su instalación de Moodle.
2. **Seleccione SPRESAI** como el evaluador en la configuración de su actividad VPL.
3. **Habilite la evaluación automática** en las opciones de ejecución.
4. **Configure el plugin** en la página de "casos de prueba".
5. **Establezca su proveedor de IA y modelo** en `config.py`.
6. **Establezca su clave API** en `config.py`.
7. **Establezca su modo de ejecución** (evaluate, explain, tip o fix) en `config.py`.
8. Cuando los estudiantes o profesores **evalúen envíos**, SPRESAI lo procesará automáticamente usando el modelo de IA configurado.

---

## ⚙️ Configuración

SPRESAI se configura a través de `spresai/config.py` que es editable mediante la página de "casos de prueba" o más generalmente a través de "Archivos de ejecución". Este archivo es un módulo Python y debe **estar escrito en sintaxis Python válida**.

### 🔧 Parámetros de Configuración Básicos

Estos parámetros son **requeridos** para que SPRESAI funcione.

#### 🔑 **API_KEY**

**Descripción:** La(s) clave(s) API para su proveedor de modelo de IA.

**🚨 ADVERTENCIA CRÍTICA DE SEGURIDAD:**

  * Cualquier profesor o administrador con acceso a esta actividad VPL puede potencialmente ver esta clave.
  * Esta clave se transmitirá a los servidores de ejecución durante la evaluación.
  * Asegúrese de confiar en su infraestructura antes de establecer su clave.
  * Considere usar una **clave de alcance limitado** con límites de gasto y permisos restringidos.
  * Este archivo (con la clave) se guardará en el servidor Moodle y se incluirá en las copias de seguridad de Moodle.
  * **Elimine este archivo** si deja de usar el evaluador SPRESAI en esta actividad.

**Mejores prácticas:**

 * Configure alertas de facturación en su cuenta de proveedor de IA.
 * Use claves separadas para desarrollo y producción.
 * Rote las claves API regularmente.

**Formato:** Puede ser una cadena única o una lista de cadenas (para balanceo de carga o respaldo).

Ejemplo:

```python
# Clave API única
API_KEY = "su-clave-api-aquí"

# Múltiples claves API (balanceadas aleatoriamente)
API_KEY = [
    "clave-1-aquí",
    "clave-2-aquí",
    "clave-3-aquí"
]
```

---

#### 🤖 **PROVIDER**

**Descripción:** El proveedor de IA a usar para la evaluación.

**Proveedores soportados:** SPRESAI usa LiteLLM y soporta casi cualquier proveedor público incluyendo:
- `openai` - OpenAI (modelos GPT)
- `anthropic` - Anthropic (modelos Claude)
- `google` - Google (modelos Gemini)
- `groq` - Groq (inferencia rápida)
- `mistral` - Mistral AI
- `cohere` - Cohere
- `replicate` - Replicate
- `together_ai` - Together AI
- `vertex_ai` - Google Vertex AI
- `bedrock` - AWS Bedrock
- `azure` - Azure OpenAI
- Y muchos más...

**Consejo:** Consulte la [documentación de proveedores de LiteLLM](https://docs.litellm.ai/docs/providers) para la lista completa de proveedores soportados.

Ejemplo:

```python
PROVIDER = "groq"
```

---

#### 🎯 **MODEL**

**Descripción:** El modelo de IA específico a usar del proveedor elegido.

**Ejemplos por proveedor:**

| Proveedor | Modelos de Ejemplo |
|----------|---------------|
| `openai` | `gpt-4o`, `gpt-4o-mini`, `gpt-3.5-turbo` |
| `anthropic` | `claude-3-5-sonnet-20241022`, `claude-3-opus-20240229` |
| `google` | `gemini-1.5-pro`, `gemini-1.5-flash` |
| `groq` | `llama-3.3-70b-versatile`, `mixtral-8x7b-32768` |
| `mistral` | `mistral-large-latest`, `mistral-medium` |

Ejemplo:

```python
MODEL = "llama-3.3-70b-versatile"
```

**Ejemplo combinado:**

```python
PROVIDER = "groq"
MODEL = "llama-3.3-70b-versatile"
```

---

#### 🎯 **MODE**

**Descripción:** Establece el modo(s) de operación para el evaluador.

**Formato:** Puede ser una cadena única o una lista de cadenas para ejecutar múltiples modos secuencialmente.

**Modos disponibles:**

| Modo | Descripción | Salida |
|------|-------------|--------|
| `evaluate` | Evaluación completa con calificación | Evaluación detallada + calificación numérica |
| `explain` | Explicación de código | Explicación educativa de lo que hace el código |
| `tip` | Orientación educativa | Un consejo útil para mejorar el código |
| `fix` | Sugerencia de corrección única | Una corrección específica para el problema más importante |

**Detalles de los modos:**

**1. Modo Evaluate** (`MODE = "evaluate"`)

* Proporciona evaluación completa de código, generando un informe y una calificación.
* El sistema obtiene la evaluación de la descripción en la actividad.
 También puede sobrescribir la **especificación de la tarea** escribiéndola en `spresai/assignment_prompt.txt` en los "archivos de ejecución".
* Los profesores pueden escribir una rúbrica en el archivo `spresai/rubric_prompt.txt` en los "archivos de ejecución" para ajustar mejor la evaluación.

**2. Modo Explain** (`MODE = "explain"`)

* Proporciona explicación educativa del código
* Explica qué hace el código, función por función
* Identifica errores sin sugerir correcciones
* NO proporciona calificaciones
* **Usable en:** Ejercicios de aprendizaje, práctica de revisión de código

**3. Modo Tip** (`MODE = "tip"`)

* Proporciona UN consejo educativo
* Guía a los estudiantes hacia la comprensión
* NO da soluciones de código concretas
* Se enfoca en enseñar conceptos
* **Usable en:** Evaluación formativa, orientación de aprendizaje

**4. Modo Fix** (`MODE = "fix"`)

* Sugiere UNA corrección específica
* Muestra la línea exacta a cambiar
* Mantiene las correcciones simples y educativas
* Se enfoca en el problema más importante
* **Usable en:** Asistencia de depuración, ayuda rápida

Ejemplo:

```python
# Modo único
MODE = "evaluate"

# Múltiples modos (ejecutados secuencialmente)
MODE = ["explain", "evaluate"]
```

**Nota:** Cuando se usan múltiples modos, cada modo se ejecutará independientemente y producirá salidas separadas.

---

### 🌐 Parámetros de Configuración Opcionales

Estos parámetros afinan el comportamiento del evaluador y pueden ajustarse según sus necesidades.

#### 🗣️ **LANGUAGE**

**Descripción:** Idioma para las respuestas de la IA.

**Opciones:**

* `"current"` — Usa el idioma actual de la interfaz de Moodle
* Código de idioma específico — por ejemplo, `"en"`, `"es"`, `"fr"`, `"de"`, `"pt"`, `"it"`, `"zh"`

**Ejemplos:**

```python
# Usar el idioma actual de Moodle (recomendado)
LANGUAGE = "current"

# Forzar inglés
LANGUAGE = "en"

# Forzar español
LANGUAGE = "es"
```

**Nota:** El modelo de IA proporcionará respuestas en el idioma especificado. Asegúrese de que su modelo elegido soporte adecuadamente el idioma objetivo.

---

#### 📊 **MAX_OUTPUT_TOKENS**

**Descripción:** Número máximo de tokens que el modelo de IA puede generar en su respuesta.

**Recomendaciones:**

| Modo | Valor Recomendado | Razón |
|------|------------------|---------|
| `evaluate` | 4k-16k | La evaluación detallada requiere más espacio |
| `explain` | 2k-4k | Las explicaciones completas necesitan espacio |
| `tip` | 1k-2k | Un consejo único es conciso |
| `fix` | 1k-2k | Una corrección única es breve |

**Ejemplos:**

```python
# Evaluación estándar (4K tokens)
MAX_OUTPUT_TOKENS = 4 * 1024  # 4K
```

**Consideración de costos:** Más tokens = mayores costos de API. Equilibre el detalle con el presupuesto.

---

#### 📏 **MAX_INPUT_LENGTH**

**Descripción:** Número máximo de **caracteres** (no tokens) enviados al modelo de IA en el prompt del usuario.

**Propósito:**

* Previene costos excesivos de API por envíos muy largos
* Se mantiene dentro de los límites de contexto del modelo
* Trunca la entrada si se excede

**Recomendaciones:**

| Tipo de Envío | Valor Recomendado |
|----------------|------------------|
| Programas pequeños (< 200 líneas) | 8K-16K caracteres |
| Programas medianos (200-500 líneas) | 16K-32K caracteres |
| Programas grandes (> 500 líneas) | 32K-64K caracteres |

**Ejemplos:**

```python
# Límite estándar (16K caracteres, ~400 líneas)
MAX_INPUT_LENGTH = 16 * 1024
```

**Mensaje de advertencia:** Si la entrada se trunca, el profesor verá un mensaje en el panel de ejecución sin procesar. Los estudiantes no reciben un mensaje.

---

#### 🌡️ **TEMPERATURE**

**Descripción:** Controla la aleatoriedad/creatividad de las respuestas de IA.

**Escala:** 0.0 (determinista) a 1.0 (muy creativo)

**Recomendaciones:**

| Temperatura | Comportamiento | Usable en |
|------------|----------|----------|
| 0.0 - 0.3 | Muy enfocado, consistente | Evaluación, calificación |
| 0.3 - 0.5 | Equilibrado, ligeramente variado | Explicaciones, consejos |
| 0.5 - 0.7 | Más creativo, diverso | Retroalimentación creativa |
| 0.7 - 1.0 | Muy creativo, impredecible | ⚠️ No recomendado para calificación |

**Ejemplos:**

```python
# Evaluación estricta (recomendado para calificación)
TEMPERATURE = 0.2
```

**Recomendación:** Mantenga `TEMPERATURE` bajo (0.2-0.3) para calificación consistente y confiable.

---

#### ⏱️ **API_TIMEOUT**

**Descripción:** Tiempo máximo (en segundos) para esperar la respuesta de la API de IA.

**Recomendaciones:**

| Escenario | Tiempo de Espera Recomendado |
|----------|-------------------|
| Modelos rápidos (Groq, modelos pequeños) | 30-60 segundos |
| Modelos estándar (GPT-4, Claude) | 60-90 segundos |
| Modelos lentos (modelos muy grandes) | 90-120 segundos |
| Evaluaciones complejas | 120-180 segundos |

**Ejemplos:**

```python
# Tiempo de espera estándar (la mayoría de los casos)
API_TIMEOUT = 60
```

**Comportamiento en tiempo de espera agotado:**
* SPRESAI reintentará hasta 3 veces
* Si todos los reintentos agotan el tiempo, se devuelve un error
* Los estudiantes ven un mensaje de error de tiempo agotado

---

### 📝 Ejemplo de Configuración Completa

```python
# filepath: config.py
# Archivo de Configuración de SPRESAI

########### PARÁMETROS DE CONFIGURACIÓN BÁSICOS ###########

# Clave API para proveedor de IA
# 🚨 SEGURIDAD: ¡Proteja esta clave! Vea la documentación para advertencias de seguridad.
API_KEY = "sk-proj-abc123def456..."

# Proveedor de IA
# Opciones: "openai", "anthropic", "google", "groq", "mistral", etc.
PROVIDER = "groq"

# Nombre del Modelo de IA
# Modelo específico del proveedor
MODEL = "llama-3.3-70b-versatile"

# Modo de Evaluación
# Opciones: "evaluate" | "explain" | "tip" | "fix" | lista de modos
MODE = "evaluate"

######### PARÁMETROS DE CONFIGURACIÓN OPCIONALES #########

# Idioma para retroalimentación
# "current" = usar el idioma de Moodle, o específico: "en", "es", "fr", etc.
LANGUAGE = "current"

# Longitud máxima de respuesta de IA (tokens)
# Recomendado: 4096 para evaluate, 2048 para explain, 1024 para tip/fix
MAX_OUTPUT_TOKENS = 4 * 1024

# Longitud máxima de código del estudiante (caracteres)
# Recomendado: 16K para programas típicos, aumentar para proyectos más grandes
MAX_INPUT_LENGTH = 16 * 1024

# Nivel de creatividad de IA (0.0 = determinista, 1.0 = creativo)
# Recomendado: 0.2 para calificación consistente
TEMPERATURE = 0.2

# Tiempo de espera de solicitud API (segundos)
# Recomendado: 60 para modelos estándar, ajustar según velocidad del modelo
API_TIMEOUT = 60

# Fin de config.py
```

---

## 🎨 Personalización de Prompts de IA

SPRESAI permite la personalización completa de los prompts de IA para cada modo de evaluación. Esto le permite adaptar los criterios de evaluación, el estilo de retroalimentación y el formato de salida a sus necesidades docentes específicas.

### 📂 Estructura de Archivos de Prompts, editables en "archivos de ejecución"

```
/spresai/
  ├── system_prompt.txt      ← prompt del sistema
  ├── evaluate_prompt.txt    ← prompt de usuario del modo Evaluación
  ├── explain_prompt.txt     ← prompt de usuario del modo Explicación
  ├── tip_prompt.txt         ← prompt de usuario del modo Consejo
  ├── fix_prompt.txt         ← prompt de usuario del modo Corrección
  ├── rubric_prompt.txt      ← contenido de variable de sustitución rubric
  └── assignment_prompt.txt  ← sobrescritura de contenido de variable de sustitución de tarea
  
```

### 🔄 Cómo Funciona la Personalización de Prompts

1. **Prompts predeterminados** están incluidos con la instalación de SPRESAI
2. **Sobrescriba prompts** creando y editando el archivo en los "archivos de ejecución"
3. **Personalización por actividad** cargando archivos de prompts personalizados a la actividad VPL
4. **Los marcadores de posición** se reemplazan en tiempo de ejecución con valores reales

**Consejo:** Para personalizar cualquier prompt, comience desde el predeterminado.

---

### 📋 Marcadores de Posición Disponibles

Los marcadores de posición usan el formato `<<<nombre_marcador>>>` y se reemplazan con valores reales cuando el prompt se envía a la IA.

| Marcador de Posición | Descripción |
|------------|-------------|
| `<<<assignment>>>` | Descripción de la tarea de la actividad VPL o archivo de prompt (si se proporciona) |
| `<<<grade_min>>>` | Calificación mínima (de la configuración de VPL) |
| `<<<grade_max>>>` | Calificación máxima (de la configuración de VPL) |
| `<<<rubric>>>` | Rúbrica de calificación (si se proporciona) |
| `<<<student_submission>>>` | Archivos de código enviados por el estudiante |
| `<<<language>>>` | Idioma natural de respuesta |

### 💬 Comunidad

* **Foro VPL:** [Foro de la comunidad VPL](https://vpl.dis.ulpgc.es/forum/)
* **Issues de GitHub:** [Reportar errores y solicitar funciones](https://github.com/jcrodriguez-dis/moodle-mod_vpl/issues)

### 📧 Contacto

* **Autor:** Juan Carlos Rodríguez-del-Pino
* **Email:** jc.rodriguezdelpino@ulpgc.es

---

## 📜 Licencia y Autoría

© Copyright 2025, Juan Carlos Rodríguez-del-Pino

Este software es parte de VPL para Moodle - http://vpl.dis.ulpgc.es/

VPL para Moodle es software libre: puede redistribuirlo y/o modificarlo
bajo los términos de la Licencia Pública General GNU publicada por
la Free Software Foundation, ya sea la versión 3 de la Licencia, o
(a su elección) cualquier versión posterior.

Esta documentación está licenciada bajo una
[Licencia Creative Commons Atribución-NoComercial-SinDerivadas 4.0 Internacional](https://creativecommons.org/licenses/by-nc-nd/4.0/).

[![Licencia CC BY-NC-ND 4.0](https://licensebuttons.net/l/by-nc-nd/4.0/88x31.png)](https://creativecommons.org/licenses/by-nc-nd/4.0/)

---

*¡Aproveche el poder de la IA para la educación en programación con SPRESAI!*

[1]: https://vpl.dis.ulpgc.es "Documentación del Laboratorio de Programación Virtual para Moodle (VPL)"