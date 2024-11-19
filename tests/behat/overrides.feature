@mod @mod_vpl @mod_vpl_overrides
Feature: In an VPL activity, editing teacher change overrides
  In order to define/modify/delete activity overrides/exceptions
  As an editing teacher
  I access to a VPL activity overrides/exceptions page and create modify delete overrides

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher1  | Teacher  | teacher1@example.com |
      | teacher2 | Teacher2  | Teacher  | teacher2@example.com |
      | student1 | Student1  | Student  | student1@example.com |
      | student2 | Student2  | Student  | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | teacher        |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "groups" exist:
      | name | course | idnumber |
      | G1   | C1     | idn1     |
      | G2   | C1     | idn2     |
      | G3   | C1     | idn3     |
    And the following "group members" exist:
      | group | user     |
      | idn1  | student1 |
      | idn2  | student2 |
      | idn3  | student1 |
      | idn3  | student2 |

    And I log in as "teacher1"
    And I add a "vpl" activity to course "Course 1" section "1" and I fill the form with:
      | id_name                | VPL activity name |
      | id_shortdescription    | VPL activity short description |
      | id_duedate_enabled     | "" |
      | id_maxfiles            | 33 |
      | id_grade_modgrade_type | None |
    And I log out

  @javascript
  Scenario: A teacher set override
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I click on "VPL activity name" "link" in the "region-main" "region"
    And I navigate to "Overrides" in current page administration
    And I click on "Add an override" "link" in the "region-main" "region"
    Then I should see "Due date"
    And I click on "Save" "link" in the "region-main" "region"
    And I should not see "Due date"
    And I should see "None" in the "#page-content tbody" "css"
    When I click on "Edit" "link" in the "region-main" "region"
    And I open the autocomplete suggestions list in the "region-main" "region"
    And I click on "Student1 Student" item in the autocomplete list
    And I open the autocomplete suggestions list in the "region-main" "region"
    And I click on "Student2 Student" item in the autocomplete list
    And I set the following fields in the "region-main" "region" to these values:
    | override_startdate              | 1  |
    | override_duedate                | 1  |
    | override_password               | 1  |
    | password                        | si |
    | override_reductionbyevaluation  | 1  |
    | reductionbyevaluation           | 1% |
    | override_freeevaluations        | 1  |
    | freeevaluations                 | 5  |
    And I click on "Save" "link" in the "region-main" "region"
    Then I should not see "None" in the "region-main" "region"
    And I should see "Student1 Student" in the "region-main" "region"
    And I should see "Student2 Student" in the "region-main" "region"
    And I should see "Available from:" in the "region-main" "region"
    And I should see "Due date:" in the "region-main" "region"
    And I should see "Password" in the "region-main" "region"
    And I should see "Reduction by automatic evaluation:" in the "region-main" "region"
    And I should see "Free evaluations:" in the "region-main" "region"
    When I click on "Delete" "link" in the "region-main" "region"
# Steps not compatible with Behat for Moodle < 4.5
#    Then I should see "Yes" in the "body" "css_element"
#    And I click on "[data-action='save']" "css_element" in the "[data-region='modal-container']" "css_element"
#    Then I should not see "None" in the "region-main" "region"
#    And I should not see "Student1 Student" in the "region-main" "region"
#    And I should not see "Student2 Student" in the "region-main" "region"
#    And I should not see "Available from:" in the "region-main" "region"
#    And I should not see "Due date:" in the "region-main" "region"
#    And I should not see "Password" in the "region-main" "region"
#    And I should not see "Reduction by automatic evaluation:" in the "region-main" "region"
#    And I should not see "Free evaluations:" in the "region-main" "region"
