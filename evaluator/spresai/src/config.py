# This file defines the configuration for SPRESAI VPL automatic evaluator.
# You must set here the parameters needed to run SPRESAI.

########### BASIC CONFIGURATION PARAMETERS ###########

# The AI API key
# CRITICAL SECURITY WARNING:
#   * Any teacher or admin with access to this activity can see this key.
#   * This key will be sent to execution servers.
#   * Make sure you trust them before setting your key or use a limited-scope key.
#   * This file with the key will be saved on the Moodle server and Moodle backups.
#   * Remove this file if you stop using the SPRESAI evaluator in this activity.
API_KEY = "your api key here"

# The AI model name to use in LiteLLM format: "provider/model"
# Valid providers are: openai, anthropic, google, mistral, groq, cohere,
#                      azure, openrouter, togetherai, deepinfra, huggingface

MODEL_NAME = "groq/llama-3.3-70b-versatile"

# Mode of operation: "evaluate|explain|fix|tip" 
MODE = "evaluate"

######### OPTIONAL CONFIGURATION PARAMETERS #########

# Language for the evaluator messages. "current" means using Moodle language
# you can set to a specific value e.g., "en", "es", "fr", "de"
LANGUAGE = "current"

# Maximum number of output tokens from the AI model
MAX_OUTPUT_TOKENS = 4 * 1024

# Maximum number of input chars to the AI model
MAX_INPUT_LENGTH = 16 * 1024

# Temperature for the AI model: low values make output more deterministic
# and high values make output more creative
TEMPERATURE = 0.2

# Timeout for AI API calls in seconds
API_TIMEOUT = 60

# End of config.py
