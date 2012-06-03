Given /^I am logged in as an admin$/ do
  visit wordpress_url("wp-login.php")
  within("#loginform") do
    fill_in 'user_login', :with => ENV['ADMIN_USERNAME']
    fill_in 'user_pass', :with => ENV['ADMIN_PASSWORD']
    click_button "wp-submit"
  end
end

Then /^I should a table of entries with the following columns:$/ do |table|
  # table is a Cucumber::Ast::Table
  pending # express the regexp above with the code you wish you had
end

Then /^I should see the Select Live Blog dropdown$/ do
  page.should have_css("live_blogging_entry_post")
end

Then /^the Select Live Blog dropdown should only contain active live blogs$/ do
  pending # express the regexp above with the code you wish you had
end