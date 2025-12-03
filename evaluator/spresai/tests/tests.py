import unittest
from unittest.mock import patch, mock_open
import os
import sys

# Add parent directory to path for imports
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))

from src.utils import get_language_name, get_string, I18nCode
from src.evaluator import get_api_key_env_varname, apply_placeholders


class TestGetLanguageName(unittest.TestCase):
    """Tests for get_language_name function"""
    
    def test_simple_language_code(self):
        self.assertEqual(get_language_name("en"), "English")
        self.assertEqual(get_language_name("es"), "Spanish")
        self.assertEqual(get_language_name("fr"), "French")
    
    def test_language_with_country(self):
        self.assertEqual(get_language_name("en_US"), "English")
        self.assertEqual(get_language_name("es_ES"), "Spanish")
        self.assertEqual(get_language_name("pt_BR"), "Portuguese")
    
    def test_language_with_encoding(self):
        self.assertEqual(get_language_name("en_US.UTF-8"), "English")
        self.assertEqual(get_language_name("de_DE.UTF-8"), "German")
    
    def test_unknown_language(self):
        self.assertEqual(get_language_name("xyz"), "English")
        self.assertEqual(get_language_name("unknown_XX"), "English")
    
    def test_case_sensitivity(self):
        self.assertEqual(get_language_name("EN"), "English")
        self.assertEqual(get_language_name("De"), "German")


class TestGetString(unittest.TestCase):
    """Tests for get_string function"""
    
    def test_default_strings(self):
        result = get_string(I18nCode.STR_ERROR_IMPORT_CONFIG)
        self.assertIn("config.py", result)
        self.assertIn("{error}", result)
    
    def test_invalid_mode_string(self):
        result = get_string(I18nCode.STR_ERROR_INVALID_MODE)
        self.assertIn("Invalid mode", result)
        self.assertIn("{mode}", result)
    
    @patch.dict(os.environ, {"VPLEVALUATOR_STR_error_import_config": "Custom error: {error}"})
    def test_environment_variable_override(self):
        result = get_string(I18nCode.STR_ERROR_IMPORT_CONFIG)
        self.assertEqual(result, "Custom error: {error}")
    
    @patch.dict(os.environ, {"VPLEVALUATOR_STR_error_contact_model": "AI Error: {$a->error}"})
    def test_moodle_placeholder_replacement(self):
        result = get_string(I18nCode.STR_ERROR_CONTACT_MODEL)
        self.assertEqual(result, "AI Error: {error}")
    
    def test_all_i18n_codes(self):
        for code in I18nCode:
            result = get_string(code)
            self.assertIsInstance(result, str)
            self.assertGreater(len(result), 0)


class TestGetApiKeyEnvVarname(unittest.TestCase):
    """Tests for get_api_key_env_varname function"""
    
    def test_openai_provider(self):
        self.assertEqual(get_api_key_env_varname("openai/gpt-4"), "OPENAI_API_KEY")
        self.assertEqual(get_api_key_env_varname("openai/gpt-3.5-turbo"), "OPENAI_API_KEY")
    
    def test_anthropic_provider(self):
        self.assertEqual(get_api_key_env_varname("anthropic/claude-2"), "ANTHROPIC_API_KEY")
    
    def test_special_cases(self):
        self.assertEqual(get_api_key_env_varname("vertex_ai/gemini-pro"), "GOOGLE_APPLICATION_CREDENTIALS")
        self.assertEqual(get_api_key_env_varname("bedrock/claude-v2"), "AWS_ACCESS_KEY_ID")
        self.assertEqual(get_api_key_env_varname("perplexity/pplx-7b"), "PERPLEXITYAI_API_KEY")
        self.assertEqual(get_api_key_env_varname("hf/mistral-7b"), "HUGGINGFACE_API_KEY")
        self.assertEqual(get_api_key_env_varname("google/palm-2"), "GEMINI_API_KEY")
        self.assertEqual(get_api_key_env_varname("togetherai/llama-2"), "TOGETHER_API_KEY")
    
    def test_case_insensitive_provider(self):
        self.assertEqual(get_api_key_env_varname("OpenAI/gpt-4"), "OPENAI_API_KEY")
        self.assertEqual(get_api_key_env_varname("ANTHROPIC/claude-2"), "ANTHROPIC_API_KEY")
    
    def test_standard_format(self):
        self.assertEqual(get_api_key_env_varname("cohere/command"), "COHERE_API_KEY")
        self.assertEqual(get_api_key_env_varname("replicate/llama-2"), "REPLICATE_API_KEY")
    
    def test_vertex_variants(self):
        self.assertEqual(get_api_key_env_varname("vertex/model"), "GOOGLE_APPLICATION_CREDENTIALS")
        self.assertEqual(get_api_key_env_varname("vertexai/model"), "GOOGLE_APPLICATION_CREDENTIALS")


class TestApplyPlaceholders(unittest.TestCase):
    """Tests for apply_placeholders function"""
    
    def test_simple_replacement(self):
        prompt = "Hello <<<name>>>, welcome!"
        placeholders = {"<<<name>>>": "John"}
        result = apply_placeholders(prompt, placeholders)
        self.assertEqual(result, "Hello John, welcome!")
    
    def test_multiple_replacements(self):
        prompt = "Grade: <<<grade_min>>> to <<<grade_max>>>"
        placeholders = {"<<<grade_min>>>": "0", "<<<grade_max>>>": "10"}
        result = apply_placeholders(prompt, placeholders)
        self.assertEqual(result, "Grade: 0 to 10")
    
    def test_no_placeholders(self):
        prompt = "This is a plain text"
        placeholders = {"<<<name>>>": "John"}
        result = apply_placeholders(prompt, placeholders)
        self.assertEqual(result, "This is a plain text")
    
    def test_numeric_values(self):
        prompt = "Max score: <<<max_score>>>"
        placeholders = {"<<<max_score>>>": 100}
        result = apply_placeholders(prompt, placeholders)
        self.assertEqual(result, "Max score: 100")
    
    def test_repeated_placeholder(self):
        prompt = "<<<name>>> says hello to <<<name>>>"
        placeholders = {"<<<name>>>": "Alice"}
        result = apply_placeholders(prompt, placeholders)
        self.assertEqual(result, "Alice says hello to Alice")
    
    def test_empty_placeholder_value(self):
        prompt = "Start <<<middle>>> End"
        placeholders = {"<<<middle>>>": ""}
        result = apply_placeholders(prompt, placeholders)
        self.assertEqual(result, "Start  End")


class TestGetStudentFile(unittest.TestCase):
    """Tests for get_student_file function"""
    
    @patch("builtins.open", new_callable=mock_open, read_data="print('Hello World')")
    def test_read_normal_file(self, mock_file):
        from src.evaluator import get_student_file
        result = get_student_file("test.py")
        self.assertIn("### file: test.py", result)
        self.assertIn("print('Hello World')", result)
        self.assertIn("```", result)
    
    @patch("builtins.open", new_callable=mock_open, read_data="x" * 15000)
    def test_file_too_long(self, mock_file):
        from src.evaluator import get_student_file
        result = get_student_file("large.py")
        self.assertIn("Ignored: File too long", result)


class TestGetConfiguration(unittest.TestCase):
    """Tests for get_configuration function"""
    
    @patch('src.evaluator.config')
    def test_valid_configuration(self, mock_config):
        mock_config.API_KEY = "test-key"
        mock_config.MODEL_NAME = "openai/gpt-4"
        mock_config.MODE = "evaluate"
        mock_config.LANGUAGE = "en"
        mock_config.TEMPERATURE = 0.7
        mock_config.MAX_OUTPUT_TOKENS = 1000
        mock_config.MAX_INPUT_LENGTH = 5000
        mock_config.API_TIMEOUT = 120
        
        from src.evaluator import get_configuration
        config = get_configuration()
        
        self.assertEqual(config['api_key'], "test-key")
        self.assertEqual(config['model'], "openai/gpt-4")
        self.assertEqual(config['mode'], "evaluate")
        self.assertEqual(config['temperature'], 0.7)
    
    @patch.dict(os.environ, {"VPL_LANG": "es_ES"})
    @patch('src.evaluator.config')
    def test_current_language_from_env(self, mock_config):
        mock_config.API_KEY = "test-key"
        mock_config.MODEL_NAME = "openai/gpt-4"
        mock_config.MODE = "evaluate"
        mock_config.LANGUAGE = "current"
        mock_config.TEMPERATURE = 0.7
        mock_config.MAX_OUTPUT_TOKENS = 1000
        mock_config.MAX_INPUT_LENGTH = 5000
        mock_config.API_TIMEOUT = 120
        
        from src.evaluator import get_configuration
        config = get_configuration()
        
        self.assertEqual(config['language'], "es_ES")


if __name__ == '__main__':
    unittest.main()