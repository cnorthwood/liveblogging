When /^I load the QUnit test harness$/ do
  visit(wordpress_install('wp-content/plugins/live-blogging/qunit/TestRunner.html'))
end

Then /^I should see no failed tests$/ do
  find('.failed').text.should == "0"
end
