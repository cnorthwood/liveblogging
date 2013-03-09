def wordpress_url(path)
  "http://192.168.20.10/" + path
end

def get_path(name)
  case name
  when "Add New Post"
    wordpress_url "wp-admin/post-new.php"
  when "Add New Entry"
    wordpress_url "wp-admin/post-new.php?post_type=liveblog_entry"
  when "Live Blog Entries"
    wordpress_url "wp-admin/edit.php?post_type=liveblog_entry"
  when "Edit Post"
    wordpress_url "wp-admin/post.php?post=%<post_id>s&action=edit"
  else
    pending
  end
end
