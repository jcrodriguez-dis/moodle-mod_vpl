## SPRESAI MANUAL

**SPRESAI** (Student Programming Review & Evaluation System using AI) is an evaluator subplugin for **VPL** that uses artificial intelligence for code evaluation.
This system allows teachers to automatically assess student programs and provide helpful tips, code fixes, or detailed explanations using AI models.

⚠️ **Important Notice:** The use of AI for evaluation is inherently imprecise and should be used primarily as a guide or draft evaluation generator. Always review AI-generated evaluations before finalizing grades.

---

### ❓ What is SPRESAI?

SPRESAI is a flexible AI-powered evaluation framework for programming submissions written in almost any language.
It runs as a VPL evaluator sub-plugin for Moodle ([VPL][1]) and generates reports, feedback, and grades using large language models.

The framework's goals are:

* **Integration with VPL.** Plug-and-play inside the familiar VPL for Moodle environment.
* **AI-powered evaluation.** Leverage state-of-the-art language models for intelligent code assessment.
* **Multiple evaluation modes.** Evaluate, explain, provide tips, or suggest fixes.
* **Customizable prompts.** Fully customizable AI prompts for different evaluation strategies.
* **Multi-provider support.** Works with OpenAI, Anthropic, Google, Mistral, Groq, and many other AI providers via LiteLLM.
* **Security-focused.** Built-in protections against prompt injection attacks.

---

### ⚡ Quick start

1. **Install SPRESAI** as a VPL evaluator subplugin in your Moodle installation.
2. **Select SPRESAI** as the evaluator in your VPL activity settings.
3. **Enable automatic evaluation** in Execution options.
4. **Configure the plugin** in the "test cases" page.
5. **Set your AI provider and model** in `config.py`.
6. **Set your API key** in `config.py`.
7. **Set your execution mode** (evaluate, explain, tip, or fix) in `config.py`.
8. When students or teachers **evaluate submission**, SPRESAI will automatically process it using the configured AI model.

---

## ⚙️ Configuration

SPRESAI is configured through the `spresai/config.py` that is editable via the "test cases" page or more generally via "Execution files". This file is a Python module and must be **written in valid Python syntax**.

### 🔧 Basic Configuration Parameters

These parameters are **required** for SPRESAI to function.

#### 🔑 **API_KEY**

**Description:** The API key(s) for your AI model provider.

**🚨 CRITICAL SECURITY WARNING:**

  * Any teacher or admin with access to this VPL activity can potentially see this key.
  * This key will be transmitted to execution servers during evaluation.
  * Ensure you trust your infrastructure before setting your key.
  * Consider using a **limited-scope key** with spending limits and restricted permissions.
  * This file (with the key) will be saved on the Moodle server and included in Moodle backups.
  * **Remove this file** if you stop using the SPRESAI evaluator in this activity.

**Best practices:**

 * Set up billing alerts on your AI provider account.
 * Use separate keys for development and production.
 * Regularly rotate API keys.

**Format:** Can be a single string or a list of strings (for load balancing or fallback).

Example:

```python
# Single API key
API_KEY = "your-api-key-here"

# Multiple API keys (load balanced randomly)
API_KEY = [
    "key-1-here",
    "key-2-here",
    "key-3-here"
]
```

---

#### 🤖 **PROVIDER**

**Description:** The AI provider to use for evaluation.

**Supported providers:** SPRESAI uses LiteLLM and supports almost any public provider including:
- `openai` - OpenAI (GPT models)
- `anthropic` - Anthropic (Claude models)
- `google` - Google (Gemini models)
- `groq` - Groq (fast inference)
- `mistral` - Mistral AI
- `cohere` - Cohere
- `replicate` - Replicate
- `together_ai` - Together AI
- `vertex_ai` - Google Vertex AI
- `bedrock` - AWS Bedrock
- `azure` - Azure OpenAI
- And many more...

**Tip:** Check [LiteLLM's provider documentation](https://docs.litellm.ai/docs/providers) for the complete list of supported providers.

Example:

```python
PROVIDER = "groq"
```

---

#### 🎯 **MODEL**

**Description:** The specific AI model to use from your chosen provider.

**Examples by provider:**

| Provider | Example Models |
|----------|---------------|
| `openai` | `gpt-4o`, `gpt-4o-mini`, `gpt-3.5-turbo` |
| `anthropic` | `claude-3-5-sonnet-20241022`, `claude-3-opus-20240229` |
| `google` | `gemini-1.5-pro`, `gemini-1.5-flash` |
| `groq` | `llama-3.3-70b-versatile`, `mixtral-8x7b-32768` |
| `mistral` | `mistral-large-latest`, `mistral-medium` |

Example:

```python
MODEL = "llama-3.3-70b-versatile"
```

**Combined example:**

```python
PROVIDER = "groq"
MODEL = "llama-3.3-70b-versatile"
```

---

#### 🎯 **MODE**

**Description:** Sets the operation mode(s) for the evaluator.

**Format:** Can be a single string or a list of strings to run multiple modes sequentially.

**Available modes:**

| Mode | Description | Output |
|------|-------------|--------|
| `evaluate` | Full evaluation with grade | Detailed assessment + numerical grade |
| `explain` | Code explanation | Educational explanation of what the code does |
| `tip` | Educational guidance | One helpful tip to improve the code |
| `fix` | Single fix suggestion | One specific fix for the first problem found |

**Mode details:**

**1. Evaluate mode** (`MODE = "evaluate"`)

* Provides comprehensive code assessment generating a report and a grade.
* The system gets the assessment from the description in the activity.
 You can also override the **assignment specification** by writing it in `spresai/assignment_prompt.txt` in the "execution files".
* Teachers can write a rubric in the file `spresai/rubric_prompt.txt` in the "execution files" to better adjust the evaluation.

**2. Explain mode** (`MODE = "explain"`)

* Provides educational explanation of the code
* Explains what the code does, function by function
* Identifies errors without suggesting fixes
* Does NOT provide grades
* **Best for:** Learning exercises, code review practice

**3. Tip mode** (`MODE = "tip"`)

* Provides ONE educational tip
* Guides students toward understanding
* Does NOT give concrete code solutions
* Focuses on teaching concepts
* **Best for:** Formative assessment, learning guidance

**4. Fix mode** (`MODE = "fix"`)

* Suggests ONE specific fix
* Shows the exact line to change
* Keeps fixes simple and educational
* Focuses on the most important problem
* **Best for:** Debugging assistance, quick help

Example:

```python
# Single mode
MODE = "evaluate"

# Multiple modes (run sequentially)
MODE = ["explain", "evaluate"]
```

**Note:** When using multiple modes, each mode will run independently and produce separate outputs.

---

### 🌐 Optional Configuration Parameters

These parameters fine-tune the evaluator's behavior and can be adjusted based on your needs.

#### 🗣️ **LANGUAGE**

**Description:** Language for the AI responses.

**Options:**

* `"current"` — Uses the current Moodle interface language
* Specific language code — e.g., `"en"`, `"es"`, `"fr"`, `"de"`, `"pt"`, `"it"`, `"zh"`

**Examples:**

```python
# Use Moodle's current language (recommended)
LANGUAGE = "current"

# Force English
LANGUAGE = "en"

# Force Spanish
LANGUAGE = "es"
```

**Note:** The AI model will provide responses in the specified language. Ensure your chosen model supports the target language adequately.

---

#### 📊 **MAX_OUTPUT_TOKENS**

**Description:** Maximum number of tokens the AI model can generate in its response.

**Guidelines:**

| Mode | Recommended Value | Reason |
|------|------------------|---------|
| `evaluate` | 4k-16k | Detailed evaluation requires more space |
| `explain` | 2k-4k | Comprehensive explanations need room |
| `tip` | 1k-2k | Single tip is concise |
| `fix` | 1k-2k | Single fix is brief |

**Examples:**

```python
# Standard evaluation (4K tokens)
MAX_OUTPUT_TOKENS = 4 * 1024  # 4K
```

**Cost consideration:** More tokens = higher API costs. Balance detail with budget.

---

#### 📏 **MAX_INPUT_LENGTH**

**Description:** Maximum number of **characters** (not tokens) sent to the AI model in the user prompt.

**Purpose:**

* Prevents excessive API costs from very long submissions
* Stays within model context limits
* Truncates input if exceeded

**Guidelines:**

| Submission Type | Recommended Value |
|----------------|------------------|
| Small programs (< 200 lines) | 8K-16K characters |
| Medium programs (200-500 lines) | 16K-32K characters |
| Large programs (> 500 lines) | 32K-64K characters |

**Examples:**

```python
# Standard limit (16K characters, ~400 lines)
MAX_INPUT_LENGTH = 16 * 1024
```

**Warning message:** If input is truncated, teacher will see a message in the raw execution panel. Students do not get a message.

---

#### 🌡️ **TEMPERATURE**

**Description:** Controls the randomness/creativity of AI responses.

**Scale:** 0.0 (deterministic) to 1.0 (very creative)

**Guidelines:**

| Temperature | Behavior | Best For |
|------------|----------|----------|
| 0.0 - 0.3 | Very focused, consistent | Evaluation, grading |
| 0.3 - 0.5 | Balanced, slightly varied | Explanations, tips |
| 0.5 - 0.7 | More creative, diverse | Creative feedback |
| 0.7 - 1.0 | Very creative, unpredictable | ⚠️ Not recommended for grading |

**Examples:**

```python
# Strict evaluation (recommended for grading)
TEMPERATURE = 0.2
```

**Recommendation:** Keep `TEMPERATURE` low (0.2-0.3) for consistent, reliable grading.

---

#### ⏱️ **API_TIMEOUT**

**Description:** Maximum time (in seconds) to wait for AI API response.

**Guidelines:**

| Scenario | Recommended Timeout |
|----------|-------------------|
| Fast models (Groq, small models) | 30-60 seconds |
| Standard models (GPT-4, Claude) | 60-90 seconds |
| Slow models (very large models) | 90-120 seconds |
| Complex evaluations | 120-180 seconds |

**Examples:**

```python
# Standard timeout (most cases)
API_TIMEOUT = 60
```

**Behavior on timeout:**
* SPRESAI will retry up to 3 times
* If all retries timeout, an error is returned
* Students see a timeout error message

---

### 📝 Complete Configuration Example

```python
# filepath: config.py
# SPRESAI Configuration File

########### BASIC CONFIGURATION PARAMETERS ###########

# API Key for AI provider
# 🚨 SECURITY: Protect this key! See documentation for security warnings.
API_KEY = "sk-proj-abc123def456..."

# AI Provider
# Options: "openai", "anthropic", "google", "groq", "mistral", etc.
PROVIDER = "groq"

# AI Model Name
# Specific model from the provider
MODEL = "llama-3.3-70b-versatile"

# Evaluation Mode
# Options: "evaluate" | "explain" | "tip" | "fix" | list of modes
MODE = "evaluate"

######### OPTIONAL CONFIGURATION PARAMETERS #########

# Language for feedback
# "current" = use Moodle's language, or specific: "en", "es", "fr", etc.
LANGUAGE = "current"

# Maximum AI response length (tokens)
# Recommended: 4096 for evaluate, 2048 for explain, 1024 for tip/fix
MAX_OUTPUT_TOKENS = 4 * 1024

# Maximum student code length (characters)
# Recommended: 16K for typical programs, increase for larger projects
MAX_INPUT_LENGTH = 16 * 1024

# AI creativity level (0.0 = deterministic, 1.0 = creative)
# Recommended: 0.2 for consistent grading
TEMPERATURE = 0.2

# API request timeout (seconds)
# Recommended: 60 for standard models, adjust based on model speed
API_TIMEOUT = 60

# End of config.py
```

---

## 🎨 Customizing AI Prompts

SPRESAI allows complete customization of AI prompts for each evaluation mode. This enables you to tailor the evaluation criteria, feedback style, and output format to your specific teaching needs.

### 📂 Prompt Files Structure, writable in "execution files"

```
/spresai/
  ├── system_prompt.txt      ← system prompt
  ├── evaluate_prompt.txt    ← Evaluation mode user prompt
  ├── explain_prompt.txt     ← Explanation mode user prompt
  ├── tip_prompt.txt         ← Tip mode user prompt
  ├── fix_prompt.txt         ← Fix mode user prompt
  ├── rubric_prompt.txt      ← rubric placeholder
  └── assignment_prompt.txt  ← assignment placeholder override
  
```

### 🔄 How Prompt Customization Works

1. **Default prompts** are included with SPRESAI installation
2. **Override prompts** by creating and editing the file in the "execution files"
3. **Per-activity customization** by uploading custom prompt files to the VPL activity
4. **Placeholders** are replaced at runtime with actual values

**Tip:** To customize any prompt start from the default one.

---

### 📋 Available Placeholders

Placeholders use the format `<<<placeholder_name>>>` and are replaced with actual values when the prompt is sent to the AI.

| Placeholder | Description |
|------------|-------------|
| `<<<assignment>>>` | Assignment description from VPL activity or prompt file (if provided) |
| `<<<grade_min>>>` | Minimum grade (from VPL settings) |
| `<<<grade_max>>>` | Maximum grade (from VPL settings) |
| `<<<rubric>>>` | Grading rubric (if provided) |
| `<<<student_submission>>>` | Student's submitted code files |
| `<<<language>>>` | Answer natural language |

### 💬 Community

* **VPL Forum:** [VPL community forum](https://vpl.dis.ulpgc.es/forum/)
* **GitHub Issues:** [Report bugs and request features](https://github.com/jcrodriguez-dis/moodle-mod_vpl/issues)

### 📧 Contact

* **Author:** Juan Carlos Rodríguez-del-Pino
* **Email:** jc.rodriguezdelpino@ulpgc.es

---

## 📜 License & Authorship

© Copyright 2025, Juan Carlos Rodríguez-del-Pino

This software is part of VPL for Moodle - http://vpl.dis.ulpgc.es/

VPL for Moodle is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This documentation is licensed under a 
[Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License](https://creativecommons.org/licenses/by-nc-nd/4.0/).

[![CC BY-NC-ND 4.0 License](https://licensebuttons.net/l/by-nc-nd/4.0/88x31.png)](https://creativecommons.org/licenses/by-nc-nd/4.0/)

---

*Leverage the power of AI for programming education with SPRESAI!*

[1]: https://vpl.dis.ulpgc.es "Virtual Programming Lab for Moodle (VPL) documentation"
