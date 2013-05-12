class EditPostPage < Page

  def initialize(post_id=1)
    @post_id = post_id
  end

  def visit!
    visit "/wp-admin/post.php?post=#{@post_id}&action=edit"
  end

end