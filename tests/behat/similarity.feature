@mod @mod_vpl @mod_vpl_similarity @mod_vpl_similarity_each
Feature: In a VPL activity, similarity feature
  In order to search file similarity, students
  submit files and teachers search similarity and see report

  Background:
    Given the following "courses" exist:
    | fullname | shortname | category | groupmode |
    | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
    | username | firstname | lastname | email |
    | teacher1 | Teacher1 | lT | teacher1@example.com |
    | student1 | Student1 | L1 | student1@example.com |
    | student2 | Student2 | L2 | student2@example.com |
    | student3 | Student3 | l3 | student3@example.com |
    And the following "course enrolments" exist:
    | user | course | role |
    | teacher1 | C1 | editingteacher |
    | student1 | C1 | student |
    | student2 | C1 | student |
    | student3 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Virtual programming lab" to section "1" and I fill the form with:
    | id_name | VPL activity testing |
    | id_maxfiles | 8 |
    And I log out

  @javascript
  Scenario: A teacher search similarity for Ada
    ##### Student 1
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Edit"

    And I drop the file "similarity/adb_similarity.adb" on "#vpl_tabs" in VPL
    And I drop the file "similarity/ads_similarity.ads" on "#vpl_tabs" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

    ##### Student 2
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Edit"

    And I drop the file "similarity/adb_similarity.adb" on "#vpl_tabs" in VPL
    And I drop the file "similarity/ads_similarity.ads" on "#vpl_tabs" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

    ##### Student 3
    And I log in as "student3"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Edit"

    And I drop the file "similarity/adb_similarity.adb" on "#vpl_tabs" in VPL
    And I drop the file "similarity/ads_similarity.ads" on "#vpl_tabs" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

    ##### Teacher
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"

    And I follow "Submissions list"
    And I should see "Student1"
    And I should see "Student2"
    And I should see "Student3"

    And I follow "Similarity"
    And I press "Search"

    Then I should see "List of similarities found"
    And I should see "similarity/adb_similarity.adb"
    And I should see "similarity/ads_similarity.ads"
    And I should see "Student1"
    And I should see "Student2"
    And I should see "Student3"
    And I should see "100|100|100***"
    And I should see "Cluster 1"
    And I should see "Cluster 2"

  @javascript
  Scenario: A teacher search similarity for C
    ##### Student 1
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Edit"

    And I drop the file "similarity/c_similarity.c" on "#vpl_tabs" in VPL
    And I drop the file "similarity/h_similarity.h" on "#vpl_tabs" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

    ##### Student 2
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Edit"

    And I drop the file "similarity/c_similarity.c" on "#vpl_tabs" in VPL
    And I drop the file "similarity/h_similarity.h" on "#vpl_tabs" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

    ##### Student 3
    And I log in as "student3"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Edit"

    And I drop the file "similarity/c_similarity.c" on "#vpl_tabs" in VPL
    And I drop the file "similarity/h_similarity.h" on "#vpl_tabs" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

    ##### Teacher
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"

    And I follow "Submissions list"
    And I should see "Student1"
    And I should see "Student2"
    And I should see "Student3"

    And I follow "Similarity"
    And I press "Search"

    Then I should see "List of similarities found"
    And I should see "similarity/c_similarity.c"
    And I should see "similarity/h_similarity.h"
    And I should see "Student1"
    And I should see "Student2"
    And I should see "Student3"
    And I should see "100|100|100***"
    And I should see "Cluster 1"
    And I should see "Cluster 2"

  @javascript
  Scenario: A teacher search similarity for C++
    ##### Student 1
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Edit"

    And I drop the file "similarity/cpp_similarity.cpp" on "#vpl_tabs" in VPL
    And I drop the file "similarity/hxx_similarity.hxx" on "#vpl_tabs" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

    ##### Student 2
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Edit"

    And I drop the file "similarity/cpp_similarity.cpp" on "#vpl_tabs" in VPL
    And I drop the file "similarity/hxx_similarity.hxx" on "#vpl_tabs" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

    ##### Student 3
    And I log in as "student3"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Edit"

    And I drop the file "similarity/cpp_similarity.cpp" on "#vpl_tabs" in VPL
    And I drop the file "similarity/hxx_similarity.hxx" on "#vpl_tabs" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

    ##### Teacher
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"

    And I follow "Submissions list"
    And I should see "Student1"
    And I should see "Student2"
    And I should see "Student3"

    And I follow "Similarity"
    And I press "Search"

    Then I should see "List of similarities found"
    And I should see "similarity/cpp_similarity.cpp"
    And I should see "similarity/hxx_similarity.hxx"
    And I should see "Student1"
    And I should see "Student2"
    And I should see "Student3"
    And I should see "100|100|100***"
    And I should see "Cluster 1"
    And I should see "Cluster 2"

  @javascript
  Scenario: A teacher search similarity for Java
    ##### Student 1
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Edit"

    And I drop the file "similarity/java_similarity.java" on "#vpl_tabs" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

    ##### Student 2
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Edit"

    And I drop the file "similarity/java_similarity.java" on "#vpl_tabs" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

    ##### Student 3
    And I log in as "student3"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Edit"

    And I drop the file "similarity/java_similarity.java" on "#vpl_tabs" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

    ##### Teacher
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"

    And I follow "Submissions list"
    And I should see "Student1"
    And I should see "Student2"
    And I should see "Student3"

    And I follow "Similarity"
    And I press "Search"

    Then I should see "List of similarities found"
    And I should see "similarity/java_similarity.java"
    And I should see "Student1"
    And I should see "Student2"
    And I should see "Student3"
    And I should see "100|100|100***"
    And I should see "Cluster 1"

  @javascript
  Scenario: A teacher search similarity for Scheme
    ##### Student 1
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Edit"

    And I drop the file "similarity/scm_similarity.scm" on "#vpl_tabs" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

    ##### Student 2
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Edit"

    And I drop the file "similarity/scm_similarity.scm" on "#vpl_tabs" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

    ##### Student 3
    And I log in as "student3"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Edit"

    And I drop the file "similarity/scm_similarity.scm" on "#vpl_tabs" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

    ##### Teacher
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"

    And I follow "Submissions list"
    And I should see "Student1"
    And I should see "Student2"
    And I should see "Student3"

    And I follow "Similarity"
    And I press "Search"

    Then I should see "List of similarities found"
    And I should see "similarity/scm_similarity.scm"
    And I should see "Student1"
    And I should see "Student2"
    And I should see "Student3"
    And I should see "100|100|100***"
    And I should see "Cluster 1"

  @javascript
  Scenario: A teacher search similarity for multiple languages
    ##### Student 1
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Edit"

    And I drop the file "similarity/adb_similarity.adb" on "#vpl_tabs" in VPL
    And I drop the file "similarity/ads_similarity.ads" on "#vpl_tabs" in VPL
    And I drop the file "similarity/c_similarity.c" on "#vpl_tabs" in VPL
    And I drop the file "similarity/cpp_similarity.cpp" on "#vpl_tabs" in VPL
    And I drop the file "similarity/h_similarity.h" on "#vpl_tabs" in VPL
    And I drop the file "similarity/hxx_similarity.hxx" on "#vpl_tabs" in VPL
    And I drop the file "similarity/java_similarity.java" on "#vpl_tabs" in VPL
    And I drop the file "similarity/scm_similarity.scm" on "#vpl_tabs" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

    ##### Student 2
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Edit"

    And I drop the file "similarity/adb_similarity.adb" on "#vpl_tabs" in VPL
    And I drop the file "similarity/ads_similarity.ads" on "#vpl_tabs" in VPL
    And I drop the file "similarity/c_similarity.c" on "#vpl_tabs" in VPL
    And I drop the file "similarity/cpp_similarity.cpp" on "#vpl_tabs" in VPL
    And I drop the file "similarity/h_similarity.h" on "#vpl_tabs" in VPL
    And I drop the file "similarity/hxx_similarity.hxx" on "#vpl_tabs" in VPL
    And I drop the file "similarity/java_similarity.java" on "#vpl_tabs" in VPL
    And I drop the file "similarity/scm_similarity.scm" on "#vpl_tabs" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

    ##### Student 3
    And I log in as "student3"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I follow "Edit"

    And I drop the file "similarity/adb_similarity.adb" on "#vpl_tabs" in VPL
    And I drop the file "similarity/ads_similarity.ads" on "#vpl_tabs" in VPL
    And I drop the file "similarity/c_similarity.c" on "#vpl_tabs" in VPL
    And I drop the file "similarity/cpp_similarity.cpp" on "#vpl_tabs" in VPL
    And I drop the file "similarity/h_similarity.h" on "#vpl_tabs" in VPL
    And I drop the file "similarity/hxx_similarity.hxx" on "#vpl_tabs" in VPL
    And I drop the file "similarity/java_similarity.java" on "#vpl_tabs" in VPL
    And I drop the file "similarity/scm_similarity.scm" on "#vpl_tabs" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

    ##### Teacher
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"

    And I follow "Submissions list"
    And I should see "Student1"
    And I should see "Student2"
    And I should see "Student3"

    And I follow "Similarity"
    And I set the following fields to these values:
    | id_maxoutput | 15 |
    And I press "Search"

    Then I should see "List of similarities found"
    And I should see "similarity/adb_similarity.adb"
    And I should see "similarity/ads_similarity.ads"
    And I should see "similarity/c_similarity.c"
    And I should see "similarity/cpp_similarity.cpp"
    And I should see "similarity/h_similarity.h"
    And I should see "similarity/hxx_similarity.hxx"
    And I should see "similarity/java_similarity.java"
    And I should see "similarity/scm_similarity.scm"
    And I should see "Student1"
    And I should see "Student2"
    And I should see "Student3"
    And I should see "100|100|100***"
    And I should see "Cluster 1"
    And I should see "Cluster 2"
    And I should see "Cluster 3"
    And I should see "Cluster 4"
