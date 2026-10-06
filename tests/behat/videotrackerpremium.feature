@mod @mod_videotrackerpremium
Feature: Video Tracker Premium is available as a Moodle activity
  In order to manage operational video compliance
  As a teacher
  I need to be able to add Video Tracker Premium to a course

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  @javascript
  Scenario: The activity appears in the activity chooser
    Given I log in as "teacher1"
    When I am on "Course 1" course homepage with editing mode on
    And I open the activity chooser
    Then I should see "Video Tracker Premium" in the "Add an activity or resource" "dialogue"
