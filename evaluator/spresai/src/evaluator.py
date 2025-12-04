# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
#
# VPL for Moodle is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# VPL for Moodle is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with VPL for Moodle.  If not, see <http://www.gnu.org/licenses/>.

"""
This code is part of
SPRESAI - Student Programming Review & Evaluation Sytem using AI

copyright 2025 Juan Carlos Rodríguez-del-Pino
license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
"""

import os
import re
import sys
import traceback
import random
from utils import get_language_name, get_string, I18nCode

os.environ['NO_COLOR'] = '1'
os.environ['TERM'] = 'dumb'
os.environ['ANSI_COLORS_DISABLED'] = '1'
os.environ['FORCE_COLOR'] = '0'
dist_dir = 'spresai'

def end_app(exit_code=0):
    """End app with cleaning up dist_dir."""
    try:
        if os.path.exists(dist_dir):
            import shutil
            shutil.rmtree(dist_dir)
    except Exception as e:
        print(f"Error removing directory {dist_dir}: {e}")
    sys.exit(exit_code)

def get_api_key_env_varname(model_name: str) -> str:
    """
    Given a LiteLLM model name in the format 'provider/model',
    return the correct API key environment variable name.
    
    Args:
        model_name: Model name in format 'provider/model' (e.g., 'openai/gpt-4')
    
    Returns:
        Environment variable name for the API key
    """
    # Extract provider from model name
    provider = model_name.split("/", 1)[0].lower() 
    # Special cases with non-standard environment variable names
    special_cases = {
        "vertex_ai": "GOOGLE_APPLICATION_CREDENTIALS",
        "vertexai": "GOOGLE_APPLICATION_CREDENTIALS",
        "vertex": "GOOGLE_APPLICATION_CREDENTIALS",
        "bedrock": "AWS_ACCESS_KEY_ID",
        "sagemaker": "AWS_ACCESS_KEY_ID",
        "perplexity": "PERPLEXITYAI_API_KEY",
        "hf": "HUGGINGFACE_API_KEY",
        "google": "GEMINI_API_KEY",
        "togetherai": "TOGETHER_API_KEY",
    }
    
    if provider in special_cases:
        return special_cases[provider]
    
    # Standard format: PROVIDER_API_KEY
    return f"{provider.upper()}_API_KEY"

def vpl_output_answer(answer):
    print("\n<|--")
    # remove any --|> from answer if exists for security
    answer = answer.replace("--|>", "--?>")
    print(answer)
    print("--|>")

def vpl_output_grade(gradestr):
    try:
        grade = float(gradestr)
    except:
        grade = float(os.getenv("VPL_GRADEMIN", "0"))
    print(f"\nGrade :=>>{grade:.2f}")

def vpl_output_error(message):
    vpl_output_answer(f"⛔ SPRESAI Error: {message}")
    end_app(1)

def vpl_output_completion_error(message, type):
    print(message)
    traceback.print_exc(limit=0)
    vpl_output_error(get_string(I18nCode.STR_ERROR_CONTACT_MODEL).format(error=type))

def consult(configuration, mode):
    retry = 3
    try:
        import litellm
        from litellm.exceptions import (
            AuthenticationError,
            RateLimitError,
            Timeout,
            NotFoundError,
            BadRequestError,
            ContextWindowExceededError,
            ServiceUnavailableError,
            ContentPolicyViolationError,
            APIError
        )
    except ImportError:
        vpl_output_error(get_string(I18nCode.STR_ERROR_IMPORT_LITELLM))

    litellm.suppress_debug_info = True
    litellm.set_verbose = False
    litellm.drop_params = True

    api_key_name = get_api_key_env_varname(configuration["model"])
    api_keys = configuration["api_key"]
    random.shuffle(api_keys)
    system_prompt = configuration["system_prompt"]
    user_prompt = configuration["user_prompt"]
    
    if len(user_prompt) > configuration["max_input_length"]:
        user_prompt = user_prompt[:configuration["max_input_length"]]
        print(f"Input prompt length {len(user_prompt)} exceeds maximum configuration")
        print(f"of {configuration['max_input_length']} characters and was truncated.")
    
    print("Mode:", configuration["mode"])
    print("AI Model:", configuration["model"])
    print("Temperature:", configuration["temperature"])
    print("Max output tokens:", configuration["max_output_tokens"])
    print("Max input length in chars:", configuration["max_input_length"])
    print("System prompt length in chars:", len(system_prompt))
    print("User prompt length in chars:", len(user_prompt))
    if os.getenv("VPL_DEBUG", "0") == "1":
        print("\n--- System Prompt ---\n")
        print(system_prompt)
        print("\n--- User Prompt ---\n")
        print(user_prompt)
        print("\n---------------------\n")
    
    for intent in range(1, retry + 1):
        try:
            print(f"Attempt {intent} to contact the model...")

            try:
                os.environ[api_key_name] = api_keys[(intent - 1) % len(api_keys)]
                response = litellm.completion(
                    model=configuration["model"],
                    messages=[
                        {"role": "system", "content": system_prompt},
                        {"role": "user", "content": user_prompt}
                    ],
                    drop_params=True,
                    temperature=configuration["temperature"],
                    max_tokens=configuration["max_output_tokens"],
                    timeout=configuration["api_timeout"]
                )
                
                print("Model:", response.model)
                print("Prompt tokens:", response.usage.prompt_tokens)
                print("Completion tokens:", response.usage.completion_tokens)
                print("Total tokens:", response.usage.total_tokens)
                print("Finish reason:", response.choices[0].finish_reason)
                
            except AuthenticationError as e:
                vpl_output_completion_error(f"Authentication error: {e}", 'authentication')
            
            except RateLimitError as e:
                vpl_output_completion_error(f"Rate limit error (attempt {intent}): {e}", 'rate limit exceeded')
        
            except Timeout as e:
                vpl_output_completion_error(f"Timeout error (attempt {intent}): {e}", 'timeout error')

            except NotFoundError as e:
                vpl_output_completion_error(f"Not found error (attempt {intent}): {e}", 'not found error')

            except ContextWindowExceededError as e:
                vpl_output_completion_error(f"Context window exceeded: {e}", 'context window exceeded') 
            
            except BadRequestError as e:
                vpl_output_completion_error(f"Bad request: {e}", 'bad request') 
            
            except ContentPolicyViolationError as e:
                vpl_output_completion_error(f"Content policy violation: {e}", 'content policy violation')
            
            except ServiceUnavailableError as e:
                if intent < retry:
                    import time
                    print("Service is down. Retrying in 5s...")
                    time.sleep(5)
                    continue
                else:
                    vpl_output_completion_error(f"Service unavailable: {e}", 'service unavailable') 
            
            except APIError as e:
                if intent >= retry:
                    vpl_output_completion_error(f"API error: {e}", 'API error')
                else:
                    import time
                    print("API error. Retrying in 5s...")
                    time.sleep(5)
                    continue
            
            except Exception as e:
                vpl_output_completion_error(f"Unexpected error: {e}", 'unexpected error')
            
            # Parse response
            answer = response.choices[0].message.content
            grade = "0"
            
            if mode== "evaluate":
                answerparts = re.split(r"^\s*FINAL GRADE:", answer, flags=re.MULTILINE)
                answer = answerparts[0]
                if len(answerparts) > 1:
                    grade = answerparts[-1].strip()
            
            return (answer, grade)
            
        except Exception as e:
            print(f"Error processing response (attempt {intent}): {e}")
            # traceback.print_exc()
            if intent == retry:
                vpl_output_error(get_string(I18nCode.STR_ERROR_RESPONSE_MODEL).format(error=str(e)))
    
    vpl_output_error(get_string(I18nCode.STR_ERROR_UNKNOWN))

def get_student_file(file_name):
    file_size_limit = 10 * 1024  # 10 KB
    with open(file_name, "r") as f:
        content = f.read()
    if len(content) > file_size_limit:
        content = "Ignored: File too long\n"
    else:
        lines = content.splitlines()
        if len(lines) < 100:
            lines_nl = [f"{nl:02}| {line}" for nl, line in enumerate(lines, start=1)]
        else:
            lines_nl = [f"{nl:03}| {line}" for nl, line in enumerate(lines, start=1)]
        content = "\n".join(lines_nl)

    wrap = f"""
### file: {file_name}
```
{content}
```

"""
    return wrap

def get_student_submission():
    file_number_limit = 100
    files_content = ""
    for file_number in range(0, file_number_limit):
        file_name = os.getenv(f"VPL_SUBFILE{file_number}", "")
        if not file_name:
            break
        files_content += get_student_file(file_name)
    return get_prompt("student_submission_warning") + files_content

def get_placeholders(configuration):
    rubric = get_prompt("rubric", "")
    if rubric.strip():
        rubric = "# RUBRIC\n" + rubric
    return {
        "<<<student_submission>>>": get_student_submission(),
        "<<<language>>>": get_language_name(configuration["language"]),
        "<<<assignment>>>": get_prompt("assignment"),
        "<<<grade_min>>>": os.getenv("VPL_GRADEMIN", "0"),
        "<<<grade_max>>>": os.getenv("VPL_GRADEMAX", "10"),
        "<<<rubric>>>": rubric,
    }

def apply_placeholders(prompt, placeholders):
    for key, value in placeholders.items():
        prompt = prompt.replace(key, str(value))
    reg = re.compile(r'<?<<.{2,30}>>>?')
    for match in reg.findall(prompt):
        print(f"Warning: Text that looks like a placeholder '{match}' found in prompt, is it a error?")
    return prompt

def get_prompt(prompt_type, default_prompt=None):
    # if not exist file return default_prompt or error if None
    filepath = f"{dist_dir}/{prompt_type}_prompt.txt"
    if os.path.exists(filepath):
        with open(filepath, "r") as f:
            prompt = f.read().strip()
        return prompt
    else:
        if default_prompt is not None:
            return default_prompt
        else:
            vpl_output_error(get_string(I18nCode.STR_ERROR_PROMPT_FILE_NOT_FOUND).format(file=filepath))

def main(configuration):
    for mode in configuration["mode"]:
        if mode not in ["evaluate", "explain", "fix", "tip"]:
            vpl_output_error(get_string(I18nCode.STR_ERROR_INVALID_MODE).format(mode=mode))
        # Apply placeholders
        placeholders = get_placeholders(configuration)
        system_prompt = apply_placeholders(get_prompt("system"), placeholders)
        user_prompt = apply_placeholders(get_prompt(mode), placeholders)
        
        # Update configuration
        configuration["system_prompt"] = system_prompt
        configuration["user_prompt"] = user_prompt
        
        # Consult the model
        response = consult(configuration, mode)
        
        # Output the response
        vpl_output_answer(response[0])
        if mode == "evaluate":
            vpl_output_grade(response[1])

def get_configuration_attribute_list(value, name):
    if type(value) == str:
        return [value.strip()]
    elif type(value) == list and all(isinstance(key, str) for key in value):
        return [key.strip() for key in value]
    else:
        message = f"{name} must be a string or list of strings" 
        error = get_string(I18nCode.STR_ERROR_IMPORT_CONFIG).format(error=message)
        vpl_output_error(error)

def get_configuration():
    try:
        import config
        configuration = {}
        configuration['api_key'] = get_configuration_attribute_list(config.API_KEY, "API_KEY")
        configuration['model'] = str(config.MODEL_NAME)
        configuration['mode'] = get_configuration_attribute_list(config.MODE, "MODE")
        configuration['language'] = str(config.LANGUAGE)
        if configuration['language'].lower() == "current":
            configuration['language'] = os.getenv("VPL_LANG", "en")
        configuration['temperature'] = float(config.TEMPERATURE)
        configuration['max_output_tokens'] = int(config.MAX_OUTPUT_TOKENS)
        configuration['max_input_length'] = int(config.MAX_INPUT_LENGTH)
        configuration['api_timeout'] = int(config.API_TIMEOUT)
        return configuration
    
    except Exception as e:
        traceback.print_exc()
        vpl_output_error(get_string(I18nCode.STR_ERROR_IMPORT_CONFIG).format(error=str(e)))

if __name__ == "__main__":
    main(get_configuration())
    end_app()
