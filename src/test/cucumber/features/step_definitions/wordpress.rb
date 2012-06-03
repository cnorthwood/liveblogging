Given /^I am on the (.+) screen$/ do |screen|
  visit get_path(screen)
end

When /^I save the post$/ do
  click_button "publish"
end

When /^I go to the Edit Post screen for that post$/ do
  visit get_path('Edit Post') % {:post_id => @post_id}
end

Given /^I am on the Edit Post screen for an active live blog$/ do
  post_id = create_post :active_live_blog
  visit get_path('Edit Post') % {:post_id => post_id}
end

When /^I click the Insert Live Blog shortcode button$/ do
  click_link('insert-live-blog-shortcode')
end

Then /^the live blog shortcode appears in the editor$/ do
  find_by_id('tinymce').should have_content('[liveblog]')
end
