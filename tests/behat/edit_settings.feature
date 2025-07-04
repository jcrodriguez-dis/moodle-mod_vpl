@mod @mod_vpl @mod_vpl_edit_setting
Feature: Create and change VPL activity settings
  In order to modify activity behaviour
  As an editing teacher
  I need to change setting and check the description and behavior change for teacher and other users

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode | format  | numsections | initsections |
      | Course 1 | C1        | 0        | 1         | topics  | 1           | 1            |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | teacher2 | Teacher2  | 1        | teacher2@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "user preferences" exist:
      | user     | preference | value    |
      | teacher1 | htmleditor | textarea |
      | teacher2 | htmleditor | textarea |
      | student1 | htmleditor | textarea |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | teacher        |
      | student1 | C1     | student        |

    And I log in as "teacher1"
    And I add a "vpl" activity to course "Course 1" section "1" and I fill the form with:
      | id_name | VPL activity default |
      | id_introeditor | No description |
    And I add a "vpl" activity to course "Course 1" section "1" and I fill the form with:
      | id_name | VPL activity full setting |
      | id_shortdescription | VPL activity short description |
      | id_introeditor | Full description |
      | id_showdescription | 1 |
      | id_duedate_enabled | "" |
      | id_startdate_enabled | 1 |
      | id_maxfiles | 13 |
      | id_worktype | Group work |
      | id_restrictededitor | 1 |
      | id_maxfilesize | 16384 |
      | id_password | key |
      | id_requirednet | 10.10.10.13 |
      | id_sebrequired | 1 |
      | id_sebkeys | 1234567890 |
      | id_grade_modgrade_type | Point |
      | id_grade_modgrade_point | 17 |
      | id_reductionbyevaluation | 1% |
      | id_freeevaluations | 3 |
    And I log out

  @javascript
  Scenario: An editing teacher sees default VPL setting values
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I click on "VPL activity default" "link" in the "region-main" "region"
    Then I should see "Due date:"
    And I should not see "Available from:"
    And I should see "Maximum number of files: 1"
    And I should see "Type of work:"
    And I should see "Individual work"
    And I should see "Grade settings: Maximum grade: 100"
    And I should not see "Reduction by automatic evaluation:"
    And I should not see "Free evaluations:"
    And I should not see "Password:"
    And I should not see "Require network address:"
    And I should not see "SEB browser required:"
    And I should not see "SEB exam Key/s:"
    And I should not see "Disable external file upload"
    And I should see "Run: No"
    And I should see "Evaluate: No"

  @javascript
  Scenario: A non-editing teacher sees default VPL setting values
    Given I log in as "teacher2"
    And I am on "Course 1" course homepage
    When I click on "VPL activity default" "link" in the "region-main" "region"
    Then I should see "Due date:"
    And I should not see "Available from:"
    And I should see "Maximum number of files: 1"
    And I should see "Type of work:"
    And I should see "Individual work"
    And I should see "Grade settings: Maximum grade: 100"
    And I should not see "Reduction by automatic evaluation:"
    And I should not see "Free evaluations:"
    And I should not see "Password:"
    And I should not see "Require network address:"
    And I should not see "SEB browser required:"
    And I should not see "SEB exam Key/s:"
    And I should not see "Disable external file upload"
    And I should see "Run: No"
    And I should see "Evaluate: No"

  @javascript
  Scenario: A student sees default VPL setting values
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    When I click on "VPL activity default" "link" in the "region-main" "region"
    Then I should see "Due date:"
    And I should see "Maximum number of files: 1"
    And I should see "Type of work:"
    And I should see "Individual work"
    And I should not see "Grade settings: Maximum grade: 100"
    And I should not see "Reduction by automatic evaluation:"
    And I should not see "Free evaluations:"
    And I should not see "Password:"
    And I should not see "Require network address:"
    And I should not see "SEB browser required:"
    And I should not see "SEB exam Key/s:"
    And I should not see "Disable external file upload"
    And I should not see "Run: No"
    And I should not see "Evaluate: No"

  @javascript
  Scenario: An editing teacher sees default VPL full setting values
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then I should see "Full description"
    And I click on "VPL activity full setting" "link" in the "region-main" "region"
    And I should not see "VPL activity short description"
    And I should see "Full description"
    And I should see "Available from:"
    And I should see "Due date:"
    And I should see "Maximum number of files: 13"
    And I should see "Type of work:"
    And I should see "Group work"
    And I should see "Grade settings: Maximum grade: 17"
    And I should see "Reduction by automatic evaluation: 1%"
    And I should see "Free evaluations: 3"
    And I should see "Password: Yes"
    And I should see "Require network address: 10.10.10.13"
    And I should see "SEB browser required: Yes"
    And I should see "SEB exam Key/s: Yes"
    And I should see "Disable external file upload"
    And I should see "Run: No"
    And I should see "Evaluate: No"

  @javascript
  Scenario: A non-editing teacher sees default VPL full setting values
    Given I log in as "teacher2"
    And I am on "Course 1" course homepage
    And I should see "Full description"
    When I click on "VPL activity full setting" "link" in the "region-main" "region"
    Then I should not see "VPL activity short description"
    And I should see "Full description"
    And I should see "Available from:"
    And I should see "Due date:"
    And I should see "Maximum number of files: 13"
    And I should see "Type of work:"
    And I should see "Group work"
    And I should see "Grade settings: Maximum grade: 17"
    And I should see "Reduction by automatic evaluation: 1%"
    And I should see "Free evaluations: 3"
    And I should see "Password: Yes"
    And I should see "Require network address: 10.10.10.13"
    And I should see "SEB browser required: Yes"
    And I should see "SEB exam Key/s: Yes"
    And I should see "Disable external file upload"
    And I should see "Run: No"
    And I should see "Evaluate: No"

  @javascript
  Scenario: A student does not access activity due to the network restriction
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Full description"
    When I click on "VPL activity full setting" "link" in the "region-main" "region"
    Then I should see "Action not allowed from"

  @javascript
  Scenario: A student sees default VPL full setting values
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity full setting" "link" in the "region-main" "region"
    And I navigate to "ettings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | id_name | VPL activity changed setting |
      | id_showdescription | "" |
      | id_worktype | Individual work |
      | id_requirednet | |
      | id_sebrequired | No |
      | id_sebkeys | |
      | id_cmidnumber | Full CMID |
    # The password field cannot be changed
    And I press "Save and display"
    And I should see "VPL activity changed setting"
    And I should see "Available from:"
    And I should see "Due date:"
    And I should see "Maximum number of files: 13"
    And I should see "Type of work:"
    And I should see "Individual work"
    And I should see "Grade settings: Maximum grade: 17"
    And I should see "Reduction by automatic evaluation: 1%"
    And I should see "Free evaluations: 3"
    And I should see "Password:"
    And I should not see "Require network address:"
    And I should not see "SEB browser required:"
    And I should not see "SEB exam Key/s:"
    And I should see "Disable external file upload"
    And I should see "Run: No"
    And I should see "Evaluate: No"
    And I navigate to "ettings" in current page administration
    And I expand all fieldsets
    And I click on "#id_cmidnumber[value='Full CMID']" in VPL
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    When I click on "VPL activity changed setting" "link" in the "region-main" "region"
    Then I should see "A password is required"
    And I set the following fields to these values:
    | id_password | key |
    And I press "Continue"
    Then I should see "Due date:"
    And I should see "Available from:"
    And I should see "Maximum number of files: 13"
    And I should see "Type of work:"
    And I should see "Individual work"
    And I should not see "Grade settings: Maximum grade:"
    And I should see "Reduction by automatic evaluation: 1%"
    And I should see "Free evaluations: 3"
    And I should not see "Password:"
    And I should not see "Require network address:"
    And I should not see "SEB browser required:"
    And I should not see "SEB exam Key/s:"
    And I should not see "Run: No"
    And I should not see "Evaluate: No"
