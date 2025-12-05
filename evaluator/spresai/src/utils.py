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
from enum import Enum, auto

"""
 Mapping of language codes to language names.
 TODO: remove when python version >= 3.12 is required, as it includes
 locale.languages() in the standard library.
"""
LANGUAGE_NAMES = {
    "af": "Afrikaans",
    "am": "Amharic",
    "ar": "Arabic",
    "az": "Azerbaijani",
    "be": "Belarusian",
    "bg": "Bulgarian",
    "bn": "Bengali",
    "bs": "Bosnian",
    "ca": "Catalan",
    "cs": "Czech",
    "cy": "Welsh",
    "da": "Danish",
    "de": "German",
    "el": "Greek",
    "en": "English",
    "eo": "Esperanto",
    "es": "Spanish",
    "et": "Estonian",
    "eu": "Basque",
    "fa": "Persian",
    "fi": "Finnish",
    "fil": "Filipino",
    "fr": "French",
    "ga": "Irish",
    "gl": "Galician",
    "gu": "Gujarati",
    "he": "Hebrew",
    "hi": "Hindi",
    "hr": "Croatian",
    "hu": "Hungarian",
    "hy": "Armenian",
    "id": "Indonesian",
    "is": "Icelandic",
    "it": "Italian",
    "ja": "Japanese",
    "ka": "Georgian",
    "kk": "Kazakh",
    "km": "Khmer",
    "kn": "Kannada",
    "ko": "Korean",
    "ku": "Kurdish",
    "ky": "Kyrgyz",
    "lo": "Lao",
    "lt": "Lithuanian",
    "lv": "Latvian",
    "mk": "Macedonian",
    "ml": "Malayalam",
    "mn": "Mongolian",
    "mr": "Marathi",
    "ms": "Malay",
    "mt": "Maltese",
    "my": "Burmese",
    "nb": "Norwegian Bokmål",
    "ne": "Nepali",
    "nl": "Dutch",
    "nn": "Norwegian Nynorsk",
    "no": "Norwegian",
    "pa": "Punjabi",
    "pl": "Polish",
    "ps": "Pashto",
    "pt": "Portuguese",
    "ro": "Romanian",
    "ru": "Russian",
    "si": "Sinhala",
    "sk": "Slovak",
    "sl": "Slovenian",
    "sq": "Albanian",
    "sr": "Serbian",
    "sv": "Swedish",
    "sw": "Swahili",
    "ta": "Tamil",
    "te": "Telugu",
    "tg": "Tajik",
    "th": "Thai",
    "tk": "Turkmen",
    "tr": "Turkish",
    "tt": "Tatar",
    "uk": "Ukrainian",
    "ur": "Urdu",
    "uz": "Uzbek",
    "vi": "Vietnamese",
    "xh": "Xhosa",
    "yi": "Yiddish",
    "zh": "Chinese",
    "zu": "Zulu",
}

def get_language_name(locale_code):
    # Lowercase and extract the language part of the locale code (eg. en_US.UTF-8 -> en_us)
    normalized = locale_code.lower().split('.')[0]
    # Extract the language code before any underscore (e.g., en_us -> en)
    lang_code = normalized.split('_')[0]
    return LANGUAGE_NAMES.get(lang_code, "English")

class I18nCode(Enum):
    """Enum for internationalization string names"""
    STR_ERROR_IMPORT_CONFIG = auto()
    STR_ERROR_IMPORT_LITELLM = auto()
    STR_ERROR_INVALID_MODE = auto()
    STR_ERROR_PROMPT_FILE_NOT_FOUND = auto()
    STR_ERROR_CONTACT_MODEL = auto()
    STR_ERROR_RESPONSE_MODEL = auto()
    STR_ERROR_UNKNOWN = auto()


class StrCodeStrDefault:
    """Container for string code and default string"""
    def __init__(self, code: str, default_str: str):
        self.code = code
        self.str = default_str


# Mapping from I18nCode to StrCodeStrDefault
I18N_CODE_STR_DEFAULT = {
    I18nCode.STR_ERROR_IMPORT_CONFIG: StrCodeStrDefault(
        "error_import_config",
        "Loading config.py file: {error}"
    ),
    I18nCode.STR_ERROR_IMPORT_LITELLM: StrCodeStrDefault(
        "error_import_litellm",
        "Loading LiteLLM library: {error}"
    ),
    I18nCode.STR_ERROR_INVALID_MODE: StrCodeStrDefault(
        "error_invalid_mode",
        "Invalid mode '{$a->mode}'. Must be one of 'evaluate', 'explain', 'fix', 'tip'."
    ),
    I18nCode.STR_ERROR_PROMPT_FILE_NOT_FOUND: StrCodeStrDefault(
        "error_prompt_file_not_found",
        "Prompt file {$a->file} not found and no default prompt provided."
    ),
    I18nCode.STR_ERROR_CONTACT_MODEL: StrCodeStrDefault(
        "error_contact_model",
        "Contacting AI model: {$a->error}"
    ),
    I18nCode.STR_ERROR_RESPONSE_MODEL: StrCodeStrDefault(
        "error_response_model",
        "Response from model: {$a->error}"
    ),
    I18nCode.STR_ERROR_UNKNOWN: StrCodeStrDefault(
        "error_unknown",
        "Unexpected unknown error occurred."
    ),
}


def get_string(code: I18nCode) -> str:
    """
    Get the internationalized string from the I18nCode.
    
    Retrieves the string from the environment variable VPLEVALUATOR_STR_<code>
    or uses the default string if the environment variable is not set.
    
    Args:
        code: I18nCode enum value
        
    Returns:
        The internationalized string or default string
    """
    if code not in I18N_CODE_STR_DEFAULT:
        return "Unknown code"
    
    str_code_default = I18N_CODE_STR_DEFAULT[code]
    env_var_name = f"VPLEVALUATOR_STR_{str_code_default.code}"
    i18n_str = os.getenv(env_var_name, str_code_default.str)
    return i18n_str.replace('{$a->', '{')

