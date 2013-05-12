Given(/^I am on the Live Blog Entries screen$/) do
  entry_list_page.visit!
end

Given(/^I am on the Add New Entry screen$/) do
  new_entry_page.visit!
end

Then(/^I should see the Select Live Blog dropdown$/) do
  new_entry_page.should have_select_live_blog_dropdown
end

Then(/^I should not see the Select Live Blog dropdown$/) do
  new_entry_page.should_not have_select_live_blog_dropdown
end

Then(/^I should a table of entries with the following columns:$/) do |table|
  # table is a | Title         | The live blog entry                             |
  table.map_headers! 'Column Header' => :header, 'Content' => :content
  headers = table.hashes.map { | row | row[:header] }
  entry_list_page.entry_table_headers.should == headers
  entry_list_page.entry_table_content[0][:title].should == 'I should appear on the active live blog'
  entry_list_page.entry_table_content[0][:author].should == 'admin'
  entry_list_page.entry_table_content[0][:date].should include 'Published'
  entry_list_page.entry_table_content[0][:live_blog].should == 'I am an active live blog'
end

Then(/^the Select Live Blog dropdown should only contain active live blogs$/) do
  new_entry_page.select_live_blog_dropdown_options.should == ['I am an active live blog']
end
