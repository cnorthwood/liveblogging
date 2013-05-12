class LiveBloggingWorld

  def ensure_login(role)
    unless @current_role == role
      login_page.visit!
      login_page.login_with(ENV['ADMIN_USERNAME'], ENV['ADMIN_PASSWORD'])
    end
    @current_role = role
  end

  def login_page
    LoginPage.new
  end

  def entry_list_page
    EntryListPage.new
  end

  def edit_post_page
    EditPostPage.new
  end

  def new_entry_page
    NewEntryPage.new
  end

  def new_post_page
    NewPostPage.new
  end

end