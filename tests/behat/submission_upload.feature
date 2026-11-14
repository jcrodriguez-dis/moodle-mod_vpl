@mod @mod_vpl @mod_vpl_submission_upload
Feature: Students submit and update files using the upload form
  In order to submit work without using the code editor
  As a student
  I upload files and archives and verify that updates preserve the intended files

  Background:
    Given the default editor is set to "textarea"
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "activities" exist:
      | activity | name            | intro          | course | section | maxfiles | maxfilesize |
      | vpl      | Upload activity | Upload testing | C1     | 1       | 5        | 1048576     |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Upload activity" "link" in the "region-main" "region"
    And I follow "Submission"

  @javascript @_file_upload
  Scenario: A student uploads individual files and replaces one while keeping another
    Given I select "Submit files individually" from the "Submit method" singleselect
    And I upload the VPL fixture "hello.c" to the file field "file0"
    And I upload the VPL fixture "notes.txt" to the file field "file1"
    And I set the field "comments" to "First uploaded submission"
    When I press "id_submitbutton"
    Then I should see "Saved"
    When I press "Continue"
    Then I should see "Submitted"
    And I should see "hello.c"
    And I should see "Hello from C language!"
    And I should see "notes.txt"
    And I should see "Submission notes to keep."
    And I should see "First uploaded submission"
    When I follow "Submission"
    And I select "Submit files individually" from the "Submit method" singleselect
    And I click on "input[name='file0action'][value='replace']" "css_element"
    And I upload the VPL fixture "hello.py" to the file field "file0"
    And I set the field "comments" to "Updated uploaded submission"
    And I press "id_submitbutton"
    And I should see "Saved"
    And I press "Continue"
    Then I should see "hello.c"
    And I should see "Hello from Python language!"
    And I should not see "Hello from C language!"
    And I should not see "hello.py"
    And I should see "notes.txt"
    And I should see "Submission notes to keep."
    When I am on "Course 1" course homepage
    And I click on "Upload activity" "link" in the "region-main" "region"
    And I follow "Submission view"
    Then I should see "Hello from Python language!"
    And I should see "Submission notes to keep."
    And I should see "Updated uploaded submission"

  @javascript @_file_upload
  Scenario: A student merges an archive and then replaces all files with the archive
    Given I select "Submit files individually" from the "Submit method" singleselect
    And I upload the VPL fixture "notes.txt" to the file field "file0"
    And I press "id_submitbutton"
    And I should see "Saved"
    And I press "Continue"
    And I should see "Submission notes to keep."
    When I follow "Submission"
    And I select "Submit ZIP archive" from the "Submit method" singleselect
    And I click on "input[name='archiveaction'][value='replace']" "css_element"
    And I upload the VPL fixture "files.zip" to the file field "archive"
    And I press "id_submitbutton"
    And I should see "Saved"
    And I press "Continue"
    Then I should see "hello.c"
    And I should see "hello.py"
    And I should see "logo.png"
    And I should see "hello world.pdf"
    And I should see "notes.txt"
    And I should see "Submission notes to keep."
    When I follow "Submission"
    And I select "Submit ZIP archive" from the "Submit method" singleselect
    And I click on "input[name='archiveaction'][value='replacedelete']" "css_element"
    And I upload the VPL fixture "files.zip" to the file field "archive"
    And I press "id_submitbutton"
    And I should see "Saved"
    And I press "Continue"
    And I am on "Course 1" course homepage
    And I click on "Upload activity" "link" in the "region-main" "region"
    And I follow "Submission view"
    Then I should see "hello.c"
    And I should see "Hello from C language!"
    And I should see "hello.py"
    And I should see "Hello from Python language!"
    And I should see "logo.png"
    And I should see "hello world.pdf"
    And I should not see "notes.txt"
    And I should not see "Submission notes to keep."

  @javascript @_file_upload
  Scenario: An oversized upload is rejected without replacing the previous submission
    Given the following "activities" exist:
      | activity | name                  | intro        | course | section | maxfiles | maxfilesize |
      | vpl      | Limited upload        | Size testing | C1     | 1       | 1        | 32          |
    And I am on "Course 1" course homepage
    And I click on "Limited upload" "link" in the "region-main" "region"
    And I follow "Submission"
    And I select "Submit files individually" from the "Submit method" singleselect
    And I upload the VPL fixture "notes.txt" to the file field "file0"
    And I press "id_submitbutton"
    And I should see "Saved"
    And I press "Continue"
    And I should see "Submission notes to keep."
    When I follow "Submission"
    And I select "Submit files individually" from the "Submit method" singleselect
    And I click on "input[name='file0action'][value='replace']" "css_element"
    And I upload the VPL fixture "hello.c" to the file field "file0"
    And I press "id_submitbutton"
    Then I should see "Maximum file size exceeded"
    When I press "Continue"
    And I follow "Submission view"
    Then I should see "notes.txt"
    And I should see "Submission notes to keep."
    And I should not see "Hello from C language!"

  @javascript @_file_upload
  Scenario: An archive reports additional files ignored by the file-count limit
    Given the following "activities" exist:
      | activity | name            | intro         | course | section | maxfiles |
      | vpl      | Limited archive | Count testing | C1     | 1       | 2        |
    And I am on "Course 1" course homepage
    And I click on "Limited archive" "link" in the "region-main" "region"
    And I follow "Submission"
    And I select "Submit ZIP archive" from the "Submit method" singleselect
    When I upload the VPL fixture "files.zip" to the file field "archive"
    And I press "id_submitbutton"
    Then I should see "2 additional archive files were ignored"
    When I press "Continue"
    Then I should see "hello world.pdf"
    And I should see "hello.py"
    And I should not see "logo.png"
    And I should not see "hello.c"
    When I am on "Course 1" course homepage
    And I click on "Limited archive" "link" in the "region-main" "region"
    And I follow "Submission view"
    Then I should see "hello world.pdf"
    And I should see "hello.py"
    And I should not see "logo.png"
    And I should not see "hello.c"
