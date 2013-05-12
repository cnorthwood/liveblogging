class EntryListPage < Page

  def visit!
    visit '/wp-admin/edit.php?post_type=liveblog_entry'
  end

  def entry_table_headers
    p all('table.wp-list-table thead th')
    all('table.wp-list-table thead th').slice(1..-1).map { | node | node.text.strip }
  end

  def entry_table_content
    all('table.wp-list-table tbody tr').map do | node |
      {
          :title => node.find('.column-title').text.strip,
          :author => node.find('.column-author').text.strip,
          :date => node.find('.column-date').text.strip,
          :live_blog => node.find('.column-liveblog').text.strip
      }
    end
  end

end