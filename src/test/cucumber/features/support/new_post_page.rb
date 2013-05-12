class NewPostPage < Page

  def visit!
    visit '/wp-admin/post-new.php'
  end

end