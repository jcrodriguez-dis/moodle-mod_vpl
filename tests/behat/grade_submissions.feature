@mod @mod_vpl
Feature: In a VPL activity teacher grade submissions
  In order to teacher grade submissions and
  students submit files and teachers grade submissions

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher1 | LN1 | teacher1@example.com |
      | teacher2 | Teacher2 | LN2 | teacher2@example.com |
      | student1 | Student1 | L1 | student1@example.com |
      | student2 | Student2 | L2 | student2@example.com |
      | student3 | Student3 | l3 | student3@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | teacher2 | C1 | teacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Virtual programming lab" to section "1" and I fill the form with:
      | id_name | VPL activity testing |
      | id_grade_modgrade_type | point |
      | id_grade_modgrade_point | 5 |
    And I log out
    # Student1's submission
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Edit"
    And I click on ".ui-dialog-titlebar-close" in VPL
    And I drop the file "hello.c" on "#vpl_tabs" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

  @javascript
  Scenario: A teacher grade submission
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Submissions list"
    And I click on "td.c3 a" in VPL
    And I click on "li.nav-item a[title='Grade']" in VPL
    And I set the following fields to these values:
      | id_grade | 0 |
    And I press "Grade"
    And I should see "Graded"
    And I press "Continue"
    And I click on "li.nav-item a[title='Submission view']" in VPL
    And I should see "Grade: 0"
    And I click on "li.nav-item a[title='Grade']" in VPL
    And I set the following fields to these values:
      | id_grade | 5 |
    And I press "Grade"
    And I should see "Graded"
    And I press "Continue"
    And I click on "li.nav-item a[title='Submission view']" in VPL
    And I should see "Grade: 5"
    And I click on "li.nav-item a[title='Grade']" in VPL
    And I set the following fields to these values:
      |id_grade | 5.001 |
    And I press "Grade"
    And I should see "Supplied grade is invalid"
    And I press "Continue"
    And I click on "li.nav-item a[title='Submission view']" in VPL
    And I should see "Grade: 5"
    And I click on "li.nav-item a[title='Grade']" in VPL
    And I press "Remove grade"
    And I should see "The grade has been removed"
    And I click on "li.nav-item a[title='Submission view']" in VPL
    And I should not see "Grade: 5"
    And I log out

    Given I log in as "teacher2"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Submissions list"
    And I click on "td.c3 a" in VPL
    And I click on "li.nav-item a[title='Grade']" in VPL
    And I set the following fields to these values:
      | id_grade | 0 |
    And I press "Grade"
    And I should see "Graded"
    And I press "Continue"
    And I click on "li.nav-item a[title='Submission view']" in VPL
    And I should see "Grade: 0"
    And I click on "li.nav-item a[title='Grade']" in VPL
    And I set the following fields to these values:
      | id_grade | 5 |
    And I press "Grade"
    And I should see "Graded"
    And I press "Continue"
    And I click on "li.nav-item a[title='Submission view']" in VPL
    And I should see "Grade: 5"
    And I click on "li.nav-item a[title='Grade']" in VPL
    And I set the following fields to these values:
      |id_grade | 5.001 |
    And I press "Grade"
    And I should see "Supplied grade is invalid"
    And I press "Continue"
    And I click on "li.nav-item a[title='Submission view']" in VPL
    And I should see "Grade: 5"
    And I click on "li.nav-item a[title='Grade']" in VPL
    And I press "Remove grade"
    And I should see "The grade has been removed"
    And I click on "li.nav-item a[title='Submission view']" in VPL
    And I should not see "Grade: 5"
    And I log out
