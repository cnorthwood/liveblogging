Then /^I should see an? '(.+)' checkbox$/ do |checkbox_name|
  page.should have_css('#' + checkbox_element(checkbox_name))
end

When /^I check the '(.+)' checkbox$/ do |checkbox_name|
 page.check(checkbox_element(checkbox_name))
end

When /^I uncheck the '(.+)' checkbox$/ do |checkbox_name|
 page.uncheck(checkbox_element(checkbox_name))
end

Then /^the '(.+)' checkbox is checked$/ do |checkbox_name|
  find_by_id(checkbox_element(checkbox_name)).should be_checked
end

Then /^the '(.+)' checkbox is unchecked$/ do |checkbox_name|
  find_by_id(checkbox_element(checkbox_name)).should_not be_checked
end