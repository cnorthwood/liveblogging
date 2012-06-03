Feature: Live Blogging functionality on the post screen
  As an editor
  I want to be able to add a live blog to my post
  So that I can start a live blog
  
  Background:
    Given I am logged in as an admin
  
  Scenario: 'Enable live blogging' checkbox appears
    Given I am on the Add New Post screen
    Then I should see an 'Enable live blogging on this post' checkbox
  
  Scenario: 'Enable live blogging' checkbox saves positive state
    Given I am on the Add New Post screen
    When I check the 'Enable live blogging on this post' checkbox
    And I save the post
    And I go to the Edit Post screen for that post
    Then the 'Enable live blogging on this post' checkbox is checked
  
  Scenario: 'Enable live blogging' checkbox saves negative state
    Given I am on the Edit Post screen for an active live blog
    When I uncheck the 'Enable live blogging on this post' checkbox
    And I save the post
    And I go to the Edit Post screen for that post
    Then the 'Enable live blogging on this post' checkbox is unchecked
  
  Scenario: Inserting a live blog short code inserts it into the editor
    Given I am on the Add New Post screen
    When I click the Insert Live Blog shortcode button
    Then the live blog shortcode appears in the editor