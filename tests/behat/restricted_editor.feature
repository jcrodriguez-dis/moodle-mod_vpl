@mod @mod_vpl @mod_vpl_restricted_editor

Feature: Restricted editing prevents external content but permits typed work
  In order to complete an activity using only the permitted editing actions
  As a student
  I cannot upload or paste external content but can type and save my work

  Background:
    Given the default editor is set to "textarea"
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | name                | intro                | course | section | maxfiles | restrictededitor |
      | vpl      | Restricted activity | Restricted editing   | C1     | 1       | 2        | 1                |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "Restricted activity" "link" in the "region-main" "region"
    And I navigate to "Requested files" in current page administration
    And I set the field "vpl_ide_input_newfilename" to "answer.txt"
    And I click on "#vpl_ide_dialog_new + div button" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

  @javascript
  Scenario: A student can type and save but cannot import, drop or paste external content
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Restricted activity" "link" in the "region-main" "region"
    Then "a[href*='/forms/submission.php']" "css_element" should not exist in the "region-main" "region"
    And "a[href*='/forms/submissionview.php']" "css_element" should exist in the "region-main" "region"
    When I follow "Edit"
    Then I should see "answer.txt"
    And "#vpl_ide_import" "css_element" should not exist
    When I click on "#vpl_tabs .ace_editor" "css_element"
    And I press the x key
    And I press the y key
    And I press the z key
    And I click on "#vpl_ide_save" in VPL
    And I follow "Submission view"
    Then I should see "xyz"
    When I follow "Edit"
    And I drop the file "external.txt" containing "External dropped content" on "#vpl_tabs" in VPL
    Then I should not see "external.txt"
    When I drop the file "answer.txt" containing "External replacement content" on "#vpl_tabs" in VPL
    Then I should not see "External replacement content"
    And pasting "External pasted content" into the VPL editor should be blocked
    And I should not see "External pasted content"
    When I click on "#vpl_tabs .ace_editor" "css_element"
    And I press the end key
    And I press the q key
    And I click on "#vpl_ide_save" in VPL
    And I am on "Course 1" course homepage
    And I click on "Restricted activity" "link" in the "region-main" "region"
    And I follow "Submission view"
    Then I should see "xyzq"
    And I should not see "external.txt"
    And I should not see "External replacement content"
    And I should not see "External pasted content"
