Feature: Replace tokens using entity data
  As a FillPDF user
  I want to replace the tokens I have mapped to my PDF on the edit screen with actual entity data
  In order to ensure my generated documents are correct.

  Background:
    Given I have installed FillPDF
    And I have uploaded a PDF
    And I have mapped fields to the PDF
    And two nodes with

  Scenario: Populate PDF with one entity
    When I generate the PDF with one entity
    Then I should see all matching tokens replaced
    And non-matching tokens should be cleared

  Scenario: Populate PDF with multiple entities - one token
    When I generate the PDF with multiple entities
    Then I should see all matching tokens replaced
    And the last passed-in entity's tokens should win (if they are not blank)
    And non-matching tokens should be cleared

  Scenario: Populate PDF with multiple entities and prioritize set values in earlier entities over empties in later ones - one token
    Given the last entity has en empty Body field
    When I generate the PDF with multiple entities
    Then I should see all matching tokens replaced
    And the last passed-in entity's tokens should win (if they are not blank)
    But the Body field value from the first entity should be used for the [node:body] token
    And non-matching tokens should be cleared

    # TODO: Fix this case. It's failing. fillpdf?fid=5&entity_ids[]=node:1&entity_ids[]=node:2&&entity_ids[]=node:3
  Scenario: Populate PDF with 3+ entities - two tokens in pattern
    Given the FillPDF Form's Body field is mapped to "Title [node:title] Body [node:body]"
    When I generate the PDF with multiple entities
    Then I should see all matching tokens replaced
    And the last passed-in entity's tokens should win (if they are not blank)
    And non-matching tokens should be cleared

  Scenario: Populate PDF with 3+ entities and prioritize set values in earlier entities over empties in later ones - two tokens in pattern
    Given the FillPDF Form's Body field is mapped to "Title [node:title] Body [node:body]"
    And the last entity has en empty Body field
    When I generate the PDF with multiple entities
    Then I should see all matching tokens replaced
    And the last passed-in entity's tokens should win (if they are not blank)
    But the Body field value from the first entity should be used for the [node:body] token
    And non-matching tokens should be cleared
