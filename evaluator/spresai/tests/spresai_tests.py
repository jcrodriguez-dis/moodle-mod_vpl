import unittest
from unittest.mock import patch, mock_open, MagicMock
import os
import sys

from utils import get_language_name, get_string, I18nCode
from evaluator import get_api_key_env_varname, apply_placeholders, get_prompt, get_configuration_attribute_list, main, consult


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
        from evaluator import get_student_file
        result = get_student_file("test.py")
        self.assertIn("### file: test.py", result)
        self.assertIn("print('Hello World')", result)
        self.assertIn("```", result)
    
    @patch("builtins.open", new_callable=mock_open, read_data="x" * 15000)
    def test_file_too_long(self, mock_file):
        from evaluator import get_student_file
        result = get_student_file("large.py")
        self.assertIn("### file: large.py", result)
        self.assertIn("Ignored: File too long", result)

class TestGetConfiguration(unittest.TestCase):
    """Tests for get_configuration function"""
    
    def test_valid_configuration(self):
        mock_config = MagicMock()
        mock_config.API_KEY = "test-key"
        mock_config.MODEL_NAME = "openai/gpt-4"
        mock_config.MODE = "evaluate"
        mock_config.LANGUAGE = "en"
        mock_config.TEMPERATURE = 0.7
        mock_config.MAX_OUTPUT_TOKENS = 1000
        mock_config.MAX_INPUT_LENGTH = 5000
        mock_config.API_TIMEOUT = 120
        
        # Patch config in sys.modules to ensure any import gets our mock
        with patch.dict(sys.modules, {'config': mock_config}):
            # Also patch evaluator.config in case it was already imported globally
            with patch('evaluator.config', mock_config, create=True):
                from evaluator import get_configuration
                configuration = get_configuration()
                
                self.assertEqual(configuration['api_key'], ["test-key"])
                self.assertEqual(configuration['model'], "openai/gpt-4")
                self.assertEqual(configuration['mode'], ["evaluate"])
                self.assertEqual(configuration['language'], "en")
                self.assertEqual(configuration['temperature'], 0.7)
                self.assertEqual(configuration['max_output_tokens'], 1000)
                self.assertEqual(configuration['max_input_length'], 5000)
                self.assertEqual(configuration['api_timeout'], 120)

    @patch.dict(os.environ, {"VPL_LANG": "es_ES"})
    def test_current_language_from_env(self):
        mock_config = MagicMock()
        mock_config.API_KEY = "test-key"
        mock_config.MODEL_NAME = "openai/gpt-4"
        mock_config.MODE = "evaluate"
        mock_config.LANGUAGE = "current"
        mock_config.TEMPERATURE = 0.7
        mock_config.MAX_OUTPUT_TOKENS = 1000
        mock_config.MAX_INPUT_LENGTH = 5000
        mock_config.API_TIMEOUT = 120
        
        with patch.dict(sys.modules, {'config': mock_config}):
            with patch('evaluator.config', mock_config, create=True):
                from evaluator import get_configuration
                configuration = get_configuration()
                
                self.assertEqual(configuration['language'], "es_ES")


class TestGetPrompt(unittest.TestCase):
    """Tests for get_prompt function"""

    @patch('evaluator.os.path.exists')
    @patch('builtins.open', new_callable=mock_open, read_data="file content")
    def test_file_exists(self, mock_file, mock_exists):
        mock_exists.return_value = True
        result = get_prompt("test")
        self.assertEqual(result, "file content")
        # dist_dir is 'spresai' in evaluator.py
        mock_file.assert_called_with("spresai/test_prompt.txt", "r")

    @patch('evaluator.os.path.exists')
    def test_file_not_exists_with_default(self, mock_exists):
        mock_exists.return_value = False
        result = get_prompt("test", default_prompt="default")
        self.assertEqual(result, "default")

    @patch('evaluator.os.path.exists')
    @patch('evaluator.vpl_output_error')
    def test_file_not_exists_no_default(self, mock_error, mock_exists):
        mock_exists.return_value = False
        get_prompt("test")
        mock_error.assert_called_once()


class TestGetConfigurationAttributeList(unittest.TestCase):
    """Tests for get_configuration_attribute_list function"""

    def test_string_input(self):
        result = get_configuration_attribute_list(" value ", "TEST")
        self.assertEqual(result, ["value"])

    def test_list_input(self):
        result = get_configuration_attribute_list([" val1 ", " val2 "], "TEST")
        self.assertEqual(result, ["val1", "val2"])

    @patch('evaluator.vpl_output_error')
    def test_invalid_input_int(self, mock_error):
        get_configuration_attribute_list(123, "TEST")
        mock_error.assert_called_once()

    @patch('evaluator.vpl_output_error')
    def test_invalid_input_mixed_list(self, mock_error):
        get_configuration_attribute_list(["val1", 123], "TEST")
        mock_error.assert_called_once()


class TestMain(unittest.TestCase):
    """Tests for main function"""

    def setUp(self):
        self.config = {
            "mode": ["explain"],
            "language": "en",
            "model": "openai/gpt-4",
            "api_key": ["key"],
            "temperature": 0.7,
            "max_output_tokens": 1000,
            "max_input_length": 5000,
            "api_timeout": 120
        }

    @patch('evaluator.end_app')
    @patch('evaluator.vpl_output_grade')
    @patch('evaluator.vpl_output_answer')
    @patch('evaluator.consult')
    @patch('evaluator.apply_placeholders')
    @patch('evaluator.get_prompt')
    @patch('evaluator.get_placeholders')
    def test_main_explain_mode(self, mock_get_placeholders, mock_get_prompt, mock_apply, mock_consult, mock_output_answer, mock_output_grade, mock_end_app):
        mock_get_placeholders.return_value = {}
        mock_get_prompt.return_value = "prompt"
        mock_apply.return_value = "processed prompt"
        mock_consult.return_value = ("Response", "0")
        
        main(self.config)
        
        mock_consult.assert_called_once()
        mock_output_answer.assert_called_with("Response")
        mock_output_grade.assert_not_called()
        mock_end_app.assert_called_once_with(0)

    @patch('evaluator.end_app')
    @patch('evaluator.vpl_output_grade')
    @patch('evaluator.vpl_output_answer')
    @patch('evaluator.consult')
    @patch('evaluator.apply_placeholders')
    @patch('evaluator.get_prompt')
    @patch('evaluator.get_placeholders')
    def test_main_evaluate_mode(self, mock_get_placeholders, mock_get_prompt, mock_apply, mock_consult, mock_output_answer, mock_output_grade, mock_end_app):
        self.config["mode"] = ["evaluate"]
        mock_get_placeholders.return_value = {}
        mock_get_prompt.return_value = "prompt"
        mock_apply.return_value = "processed prompt"
        mock_consult.return_value = ("Response", "10")
        
        main(self.config)
        
        mock_consult.assert_called_once()
        mock_output_answer.assert_called_with("Response")
        mock_output_grade.assert_called_with("10")
        mock_end_app.assert_called_once_with(0)

    @patch('evaluator.vpl_output_error')
    def test_main_invalid_mode(self, mock_error):
        self.config["mode"] = ["invalid_mode"]
        mock_error.side_effect = SystemExit
        with self.assertRaises(SystemExit):
            main(self.config)
        mock_error.assert_called_once()

    @patch('evaluator.end_app')
    @patch('evaluator.vpl_output_grade')
    @patch('evaluator.vpl_output_answer')
    @patch('evaluator.consult')
    @patch('evaluator.apply_placeholders')
    @patch('evaluator.get_prompt')
    @patch('evaluator.get_placeholders')
    def test_main_multiple_modes(self, mock_get_placeholders, mock_get_prompt, mock_apply, mock_consult, mock_output_answer, mock_output_grade, mock_end_app):
        self.config["mode"] = ["explain", "evaluate"]
        mock_get_placeholders.return_value = {}
        mock_get_prompt.return_value = "prompt"
        mock_apply.return_value = "processed prompt"
        mock_consult.return_value = ("Response", "10")
        
        main(self.config)
        
        self.assertEqual(mock_consult.call_count, 2)
        self.assertEqual(mock_output_answer.call_count, 2)
        mock_output_grade.assert_called_once() # Only for evaluate
        mock_end_app.assert_called_once_with(0)


class TestConsult(unittest.TestCase):
    """Tests for consult function"""

    def setUp(self):
        self.print_patcher = patch('builtins.print')
        self.mock_print = self.print_patcher.start()

        self.config = {
            "mode": "explain",
            "model": "openai/gpt-4",
            "api_key": ["key1"],
            "system_prompt": "System prompt",
            "user_prompt": "User prompt",
            "temperature": 0.5,
            "max_output_tokens": 100,
            "max_input_length": 1000,
            "api_timeout": 10
        }
        
        # Base mock exception that accepts kwargs
        class MockException(Exception):
            def __init__(self, *args, **kwargs):
                super().__init__(*args)

        # Mock exceptions class container
        class MockExceptions:
            class AuthenticationError(MockException): pass
            class RateLimitError(MockException): pass
            class Timeout(MockException): pass
            class NotFoundError(MockException): pass
            class BadRequestError(MockException): pass
            class ContextWindowExceededError(MockException): pass
            class ServiceUnavailableError(MockException): pass
            class ContentPolicyViolationError(MockException): pass
            class APIError(MockException): pass
            
        self.MockExceptions = MockExceptions
        
        # Setup default successful response
        self.mock_response = MagicMock()
        self.mock_response.choices[0].message.content = "AI Response"
        self.mock_response.usage.prompt_tokens = 10
        self.mock_response.usage.completion_tokens = 20
        self.mock_response.usage.total_tokens = 30
        self.mock_response.choices[0].finish_reason = "stop"

    def tearDown(self):
        self.print_patcher.stop()

    def get_mocks(self):
        mock_litellm = MagicMock()
        mock_litellm.completion.return_value = self.mock_response
        mock_litellm.exceptions = self.MockExceptions
        
        mock_exceptions_module = MagicMock()
        for name, cls in vars(self.MockExceptions).items():
            if isinstance(cls, type) and issubclass(cls, Exception):
                setattr(mock_exceptions_module, name, cls)
                
        return mock_litellm, mock_exceptions_module

    def test_successful_consult(self):
        mock_litellm, mock_exceptions = self.get_mocks()
        
        with patch.dict(sys.modules, {'litellm': mock_litellm, 'litellm.exceptions': mock_exceptions}):
            answer, grade = consult(self.config, "explain")
            
            self.assertEqual(answer, "AI Response")
            self.assertEqual(grade, "0")
            mock_litellm.completion.assert_called_once()

    def test_evaluate_grade_parsing(self):
        mock_litellm, mock_exceptions = self.get_mocks()
        self.mock_response.choices[0].message.content = "Feedback\nFINAL GRADE: 8.5"
        
        with patch.dict(sys.modules, {'litellm': mock_litellm, 'litellm.exceptions': mock_exceptions}):
            answer, grade = consult(self.config, "evaluate")
            
            self.assertEqual(answer, "Feedback\n")
            self.assertEqual(grade, "8.5")

    def test_input_truncation(self):
        mock_litellm, mock_exceptions = self.get_mocks()
        self.config["max_input_length"] = 10
        self.config["user_prompt"] = "This is a very long prompt"
        
        with patch.dict(sys.modules, {'litellm': mock_litellm, 'litellm.exceptions': mock_exceptions}):
            consult(self.config, "explain")
            
            # Check if warning was printed
            args_list = [args[0] for args, _ in self.mock_print.call_args_list]
            self.assertTrue(any("Input prompt length" in str(arg) for arg in args_list))
            
            call_args = mock_litellm.completion.call_args
            messages = call_args.kwargs['messages']
            user_message = next(m for m in messages if m['role'] == 'user')
            self.assertEqual(user_message['content'], "This is a ")

    @patch('evaluator.vpl_output_completion_error')
    def test_retry_logic(self, mock_completion_error):
        mock_litellm, mock_exceptions = self.get_mocks()
        
        # First call raises ServiceUnavailableError, second succeeds
        mock_litellm.completion.side_effect = [
            self.MockExceptions.ServiceUnavailableError("Service down", response=MagicMock(), llm_provider="openai", model="gpt-4"),
            self.mock_response
        ]
        
        with patch.dict(sys.modules, {'litellm': mock_litellm, 'litellm.exceptions': mock_exceptions}):
            with patch('time.sleep') as mock_sleep:
                answer, grade = consult(self.config, "explain")
                
                self.assertEqual(answer, "AI Response")
                self.assertEqual(mock_litellm.completion.call_count, 2)
                mock_sleep.assert_called_once()

    @patch('evaluator.vpl_output_error')
    @patch('evaluator.vpl_output_completion_error')
    def test_all_retries_fail(self, mock_completion_error, mock_error):
        mock_litellm, mock_exceptions = self.get_mocks()
        
        # All calls raise ServiceUnavailableError
        mock_litellm.completion.side_effect = self.MockExceptions.ServiceUnavailableError("Service down", response=MagicMock(), llm_provider="openai", model="gpt-4")
        
        mock_error.side_effect = SystemExit

        with patch.dict(sys.modules, {'litellm': mock_litellm, 'litellm.exceptions': mock_exceptions}):
            with patch('time.sleep'):
                with self.assertRaises(SystemExit):
                    consult(self.config, "explain")
                
                self.assertEqual(mock_litellm.completion.call_count, 3) # Default retry is 3
                mock_error.assert_called_once()


if __name__ == '__main__':
    unittest.main()