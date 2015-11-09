Feature: Replace tokens using entity data
  As a FillPDF user
  I want to replace the tokens I have mapped to my PDF on the edit screen with actual entity data
  In order to ensure my generated documents are correct.

  Background:
    Given I have installed FillPDF
    And I have uploaded a PDF
    And I have mapped fields to the PDF

  Scenario: Fill PDF with one entity
    When I generate the PDF with one entity
    Then I should see all matching tokens replaced
    And non-matching tokens should be cleared

  Scenario: Fill PDF with multiple entities
    When I generate the PDF with multiple entities
    Then I should see all matching tokens replaced
    And the last passed-in entity's tokens should win
    And non-matching tokens should be cleared

  Scenario: Fill PDF with multiple entities and prioritize set values in earlier entities over empties in later ones
    Given the last entity has en empty Body field
    When I generate the PDF with multiple entities
    Then I should see all matching tokens replaced
    And the last passed-in entity's tokens should win
    But the Body field value from the first entity should be used for the [node:body] token
    And non-matching tokens should be cleared
