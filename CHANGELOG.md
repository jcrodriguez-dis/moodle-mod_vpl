# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Since this is a non-official Changelog, you may want to see release
notes section at [Official VPL documentation](https://vpl.dis.ulpgc.es/documentation/vpl-3.4.3+)

## [3.5.0++ - 5.1.2] - 2022-07-12

### Added

- Include more test cases for override_tokens
- Include "vpl_null" as a valid token for override_tokens (same as "")
- Include restriction to avoid the override of vpl_ token names
- Include tokenizer for C, C++, Fortran, Ada, and Scheme (not tested)
- Include behat tests for similarity for each new tokenizer

### Changed

- Improve test_static_check to include output with expected tokens for behat
- Private attributes at \mod_vpl\tokenizer\tokenizer are now protected
- Reduce CRAP index for get_line_tokens and now coverage is 100%

### Fixed

- Improve Java, Ada, and Fortran highlighter rules (not tested)
- Fix checkrules at new options (extension, name, and override_tokens)
- Fix init method for override_tokens at \mod_vpl\tokenizer\tokenizer

## [3.5.0++ - 5.1.1] - 2022-07-08

### Added

- Include test cases for \mod_vpl\tokenizer\tokenizer_factory
- Include test cases for vpl_tokenizer_factory.class.php
- Include c_highlight_rules.json at similarity/rules folder

### Changed

- Include extension at vpl_tokenizer_factory to new tokenizer_factory
- Include extension at vpl_token_type to new token_type
- Include extension at vpl_token to new token

### Deleted

- Remove deprecated arrays at coverage.php

### Fixed

- Provide support for str_starts_with and str_ends_with for Moodle 4.0.1
- Include import at tokenizer_factory to use vpl_token at similarity
- Fix \mod_vpl\tokenizer\tokenizer->parse method to be compatible with similarity classes
- Fix similarity_base to be compatible with new \mod_vpl\tokenizer\tokenizer->parse
- Fix tokenizer_factory when loading old tokenizers
- Adjust vpl_tokenizer_factory to use new tokenizer_factory class

## [3.5.0++ - 5.1.0] - 2022-07-07

### Added

- Include tokenizer_factory to build tokenizers easily
- More useful utilities at mod_vpl\tokenizer\token such as hash and show
- Include $line property at mod_vpl\tokenizer\token
- Include parse method at \mod_vpl\tokenizer\tokenizer for similarity use
- Include token_type.php with a list of integer tokens (same as vpl_token_type)
- Provide new option at highlighter rules to update AVAILABLETOKENS

### Changed

- Rename \mod_vpl\tokenizer\tokenizer::TEXTMATETOKENS to AVAILABLETOKENS
- Changed structure for AVAILABLETOKENS to have as index token's name and value vpl_token_type
- Include "tokens" attribute at \mod_vpl\tokenizer\tokenizer to be compatible with similarity

### Fixed

- Fix error messages at \mod_vpl\util\assertf when $filename is not defined

## [3.5.0++ - 5.0.1] - 2022-06-29

### Added

- More test cases for tokenizer to improve coverage
- Provide a setter for $max_token_count (at previous versions: tokenizer::MAXTOKENCOUNT)
- Flag at tokenizer to disable error messages of mod_vpl\util\assertf::showerr

### Changed

- Adjust coverage with @codeCoverageIgnore
- Adjust coverage with @codeCoverageIgnoreStart, and @codeCoverageIgnoreEnd

### Deleted

- Remove create_splitter_regex and code at tokenizer::get_line_tokens that uses it
- Remove old unit testing for create_splitter_regex
- Remove public access at testable_tokenizer_base for create_splitter_regex

### Fixed

- Fix errors at get_line_tokens at cases with overflow tokens
- Fix get_line_tokens to truly manage regex with capturing groups
- Fix errors related to pattern reference at get_line_tokens
- Fix data type for type at mod_vpl\tokenizer\token
- Fix null tokens at get_line_tokens method

## [3.5.0++ - 5.0.0] - 2022-06-28

### Added

- Include highlight_rules file for Fortran
- Include test cases for get_line_tokens
- Include coberture compatibility for VPL plugin
- Include test cases to have 100% of code coverage

### Changed

- Reduce number of methods at tokenizer_json_test
- Rename tokenizer_json to tokenizer

### Deleted

- Remove next when it is an array of objects
- Delete tokenizer's interface to avoid confunsions

### Fixed

- Fix get_line_tokens based on Ace Editor
- Fix prepare_tokenizer when $matchcount is less than 3
- Fix highlight rules for Java language

## [3.5.0++ - 4.0.0] - 2022-06-24

### Added

- Replace old "tokenizer" class to "tokenizer_base"
- Include interface "tokenizer" with required methods
- Include at "base_test" a class to be able to use protected methods
- Include test cases to check prepare_tokenizer
- Include test cases for check_type and check_token
- Include test cases for contains_rule

### Fixed

- Fix check_token when $token is an empty array
- Fix error messages setting filename with basename
- Fix codestyle at tokenizer classes and its tests

## [3.5.0++ - 3.0.0] - 2022-06-23

### Added

- Include default_token as a valid option for rules
- Include access for protected methods at tokenizer just for testing
- Include boolean type as an available value for check_type
- Include PHP Unit for tokenizer_base

### Changed

- Move static check method for rules to tokenizer_test
- Reduce setcheckrules and overridecheckrules to just one parameter
- Update highlight rules syntax to be similar as Ace Editor
- Rename utils to tokenizer_base and define an abstract method with generic code

### Fixed

- Fix code style at tokenizer_test.php
- Fix prepare_tokenizer based on Ace Editor

## [3.5.0++ - 2.1.2] - 2022-06-20

### Added

- Include more test cases for inheritance and merge
- Include check for "token" based on TextMate
- Include an option to always check rules ignoring check_rules and overridecheckrules
- Include asserts to check if "start" state exists
- Include tests for syntax check static method

### Changed

- Move static utilities of tokenizer to utils class
- Reduce test cases for tokenizer by putting some methods together
- Update test cases to have valid tokens
- Update JSON files to be compatible with TextMate tokens

### Fixed

- Fix merge operation when there are the same states
- Fix syntax check for extension option
- Fix num_next verbose message

## [3.5.0++ - 2.1.1] - 2022-06-14

### Fixed

- Update code style based on Moodle
- Refactor code based on code style

## [3.5.0++ - 2.1.0] - 2022-06-12

### Added

- Include an option to set tokenizer name
- Add an option to specify extension for programming language

### Changed

- Memory optimization at tokenizer to just save useful data
- Increase performance while preprocessing at tokenizer
- Update tests cases to be compatible with new tokenizer
- Update example templates with new syntax

### Deleted

- Remove next_token as a valid rule option
- Remove syntax check for possible groups

## [3.5.0++ - 2.0.0] - 2022-06-12

### Added

- Include some test cases for groups of options
- Provide utilities for customized error exceptions at utils
- Include namespaces for tokenizer classes and tests
- Include a flag to disable colors at error messages

### Changed

- Move tokenizer classes to classes/tokenizer and classes/util
- Specify data type at tokenizer for security reasons
- Upgrade VPL to v3.5.0+ to use Moodle 4.0
- Delete asserts related to data types
- Set associate array for states to improve performance

### Fixed

- Fix semantic versioning at CHANGELOG
- Fix some names for tokenizer methods to be more accurate
- Fix error messages when number of rule is printed
- Fix data types errors at tokenizer
- Fix code to be compatible for PHP 8.0

## [3.5.0++ - 1.3.0] - 2022-06-07

### Added

- Provide test cases for old and some new changes
- Provide option to disable syntax checker
- Include comprobation for next when it's an array of objects
- Include asserts to assure that a group of tokens could be defined at a rule
- Include asserts to assure that a required group of token is defined together

### Changed

- Replace die with exceptions at check_error
- Replace script testing to PHP Unit
- Improve error messages to be more accurate
- Update JSON example to explain new options

### Fixed

- Fix discard operation for comments
- Fix merge operation and apply_inheritance
- Replace dir_path function with standard PHP dirname utility

## [3.5.0++ - 1.2.0] - 2022-04-23

### Added

- Include examples of highlight rules JSON files for testing
- Provide support for testing using scripts
- Template of a class for PHP Unit
- Include syntax checker at tokenizer

## [3.5.0++ - 1.1.0] - 2022-04-23

### Added

- Provide token class based on vpl_token
- Include parser operation to parse an entire file
- Include utility to get all tokens from tokenizer
- Provide operation to prepare tokenizer
- Provide error management

### Fixed

- Update highlight rules JSON file for PHP

## [3.5.0++ - 1.0.0] - 2022-04-22

### Added

- Include examples of highlight rules JSON files
- Add initial tokenizer file

### Fixed

- Replace 3.4.3+ version to 3.4.3++
