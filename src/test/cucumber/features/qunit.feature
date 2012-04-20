Feature: QUnit tests
  As a developer
  I want to run the qunit tests as part of my automated test suite
  So that I know that my JavaScript is good
  
  Scenario: Run the QUnit tests
    When I load the QUnit test harness
    Then I should see no failed tests
