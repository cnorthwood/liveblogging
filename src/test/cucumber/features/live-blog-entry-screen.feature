Feature: Live Blog entry creation
  As an editor
  I want to be able to create live blog entries
  So my readers can see my content
  
  Background:
    Given I am logged in as an admin

  Scenario: Live blog entry overview screen
    Given there are some live blog entries
    And I am on the Live Blog Entries screen
    Then I should a table of entries with the following columns:
      | Column Header | Content                                         |
      | Title         | The live blog entry                             |
      | Author        | The username of the creator                     |
      | Date          | The date this entry was published               |
      | Live Blog     | The title of the post this live blog belongs to |
  
  Scenario: Live blog new post screen should allow you to select a live blog
    Given there are active live blogs
    And I am on the Add New Entry screen
    Then I should see the Select Live Blog dropdown

  Scenario: Live blog new post screen should not allow you to select a ligve
    Given there are no active live blogs
    And I am on the Add New Entry screen
    Then I should not see the Select Live Blog dropdown
  
  Scenario: Only active live blogs are in the Select Live Blog dropdown
    Given there are a number of active and inactive live blogs
    And I am on the Add New Entry screen
    Then the Select Live Blog dropdown should only contain active live blogs
