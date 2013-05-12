class NewEntryPage < Page

  def visit!
    visit '/wp-admin/post-new.php?post_type=liveblog_entry'
  end

  def has_select_live_blog_dropdown?
    not select_live_blog_dropdown.nil? and select_live_blog_dropdown.visible?
  end

  def select_live_blog_dropdown_options
    all('#live_blogging_entry_post option').map { | node | node.text.strip }
  end

  private

  def select_live_blog_dropdown
    first(:id, 'live_blogging_entry_post')
  end

end