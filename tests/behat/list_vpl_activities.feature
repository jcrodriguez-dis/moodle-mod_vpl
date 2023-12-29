@mod @mod_vpl @mod_vpl_list_activities
Feature: In a VPL activity get the list of VPL activities in the course
  In order to get the list of VPL activities in the course
  As a teacher, choose the "Virtual programming Labs" option in the administration
  menu of a VPL activity.
  And see list of VPL activities

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Virtual programming lab" to section "1" and I fill the form with:
      | id_name | VPL activity one |
      | id_startdate_enabled | 1 |
      | id_startdate_day | 1 |
      | id_startdate_month | 1 |
      | id_startdate_year | 2010 |
      | id_duedate_enabled | 1 |
      | id_duedate_day | 1 |
      | id_duedate_month | 1 |
      | id_duedate_year | 2050 |
      | id_grade_modgrade_type | Point |
      | id_grade_modgrade_point | 10 |
    And I add a "Virtual programming lab" to section "1" and I fill the form with:
      | id_name | VPL activity two |
      | id_startdate_enabled | 1 |
      | id_startdate_day | 1 |
      | id_startdate_month | 1 |
      | id_startdate_year | 2010 |
      | id_duedate_enabled | 1 |
      | id_duedate_day | 1 |
      | id_duedate_month | 1 |
      | id_duedate_year | 2010 |
      | id_grade_modgrade_type | None |
    And I add a "Virtual programming lab" to section "1" and I fill the form with:
      | id_name | VPL activity three |
      | id_startdate_enabled | 0 |
      | id_duedate_enabled | 0 |
      | id_grade_modgrade_type | Point |
      | id_grade_modgrade_point | 10 |
    And I add a "Virtual programming lab" to section "2" and I fill the form with:
      | id_name | VPL activity four |
      | id_startdate_enabled | 1 |
      | id_startdate_day | 1 |
      | id_startdate_month | 1 |
      | id_startdate_year | 2010 |
      | id_duedate_enabled | 1 |
      | id_duedate_day | 1 |
      | id_duedate_month | 1 |
      | id_duedate_year | 2050 |
      | id_example | 1 |
      | id_grade_modgrade_type | None |
    And I add a "Virtual programming lab" to section "3" and I fill the form with:
      | id_name | VPL activity five |
      | id_startdate_enabled | 0 |
      | id_duedate_enabled | 1 |
      | id_duedate_day | 1 |
      | id_duedate_month | 1 |
      | id_duedate_year | 2010 |
      | id_example | 0 |
      | id_grade_modgrade_type | Point |
      | id_grade_modgrade_point | 10 |
    And I add a "Virtual programming lab" to section "3" and I fill the form with:
      | id_name | VPL activity six |
      | id_duedate_enabled | 0 |
      | id_startdate_enabled | 1 |
      | id_startdate_day | 1 |
      | id_startdate_month | 1 |
      | id_startdate_year | 2010 |
      | id_example | 1 |
      | id_grade_modgrade_type | None |
    And I log out

  @javascript
  Scenario: A teacher see the list of VPL activities from activity page
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity one" "link" in the "region-main" "region"
    And I navigate to "Virtual programming labs" in current page administration
    Then I should see "VPL activity one"
    And I should see "VPL activity two"
    And I should see "VPL activity three"
    And I should see "VPL activity four"
    And I should see "VPL activity five"
    And I should see "VPL activity six"
    And I should not see " is deprecated"

  @javascript
  Scenario: A teacher filter by sections
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity one" "link" in the "region-main" "region"
    And I navigate to "Virtual programming labs" in current page administration
    Then I should see "VPL activity one"
    And I should see "VPL activity two"
    And I should see "VPL activity three"
    And I should see "VPL activity four"
    And I should see "VPL activity five"
    And I should see "VPL activity six"
    And I should not see " is deprecated"
    And I select "Topic 1" from the "Section" singleselect
    Then I should see "VPL activity one"
    And I should see "VPL activity two"
    And I should see "VPL activity three"
    And I should not see "VPL activity four"
    And I should not see "VPL activity five"
    And I should not see "VPL activity six"
    And I should not see " is deprecated"
    And I select "Topic 2" from the "Section" singleselect
    Then I should not see "VPL activity one"
    And I should not see "VPL activity two"
    And I should not see "VPL activity three"
    And I should see "VPL activity four"
    And I should not see "VPL activity five"
    And I should not see "VPL activity six"
    And I should not see " is deprecated"
    And I select "Topic 3" from the "Section" singleselect
    Then I should not see "VPL activity one"
    And I should not see "VPL activity two"
    And I should not see "VPL activity three"
    And I should not see "VPL activity four"
    And I should see "VPL activity five"
    And I should see "VPL activity six"
    And I should not see " is deprecated"
    And I select "All" from the "Section" singleselect
    Then I should see "VPL activity one"
    And I should see "VPL activity two"
    And I should see "VPL activity three"
    And I should see "VPL activity four"
    And I should see "VPL activity five"
    And I should see "VPL activity six"
    And I should not see " is deprecated"

  @javascript
  Scenario: A teacher filter instances
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity one" "link" in the "region-main" "region"
    And I navigate to "Virtual programming labs" in current page administration
    Then I should see "VPL activity one"
    And I should see "VPL activity two"
    And I should see "VPL activity three"
    And I should see "VPL activity four"
    And I should see "VPL activity five"
    And I should see "VPL activity six"
    And I should not see " is deprecated"
    And I select "Open" from the "Filter" singleselect
    Then I should see "VPL activity one"
    And I should not see "VPL activity two"
    And I should see "VPL activity three"
    And I should see "VPL activity four"
    And I should not see "VPL activity five"
    And I should see "VPL activity six"
    And I should not see " is deprecated"
    And I select "Closed" from the "Filter" singleselect
    Then I should not see "VPL activity one"
    And I should see "VPL activity two"
    And I should not see "VPL activity three"
    And I should not see "VPL activity four"
    And I should see "VPL activity five"
    And I should not see "VPL activity six"
    And I should not see " is deprecated"
    And I select "Time limited" from the "Filter" singleselect
    Then I should see "VPL activity one"
    And I should see "VPL activity two"
    And I should not see "VPL activity three"
    And I should see "VPL activity four"
    And I should see "VPL activity five"
    And I should not see "VPL activity six"
    And I should not see " is deprecated"
    And I select "Time unlimited" from the "Filter" singleselect
    Then I should not see "VPL activity one"
    And I should not see "VPL activity two"
    And I should see "VPL activity three"
    And I should not see "VPL activity four"
    And I should not see "VPL activity five"
    And I should see "VPL activity six"
    And I should not see " is deprecated"
    And I select "Automatic grade" from the "Filter" singleselect
    Then I should not see "VPL activity one"
    And I should not see "VPL activity two"
    And I should not see "VPL activity three"
    And I should not see "VPL activity four"
    And I should not see "VPL activity five"
    And I should not see "VPL activity six"
    And I should not see " is deprecated"
    And I select "Manual grading" from the "Filter" singleselect
    Then I should see "VPL activity one"
    And I should not see "VPL activity two"
    And I should see "VPL activity three"
    And I should not see "VPL activity four"
    And I should see "VPL activity five"
    And I should not see "VPL activity six"
    And I should not see " is deprecated"
    And I select "Examples" from the "Filter" singleselect
    Then I should not see "VPL activity one"
    And I should not see "VPL activity two"
    And I should not see "VPL activity three"
    And I should see "VPL activity four"
    And I should not see "VPL activity five"
    And I should see "VPL activity six"
    And I should not see " is deprecated"
