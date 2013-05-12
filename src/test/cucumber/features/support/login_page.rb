class LoginPage < Page

  def visit!
    visit '/wp-login.php'
    sleep 0.1
  end

  def login_with(username, password)
    within("#loginform") do
      fill_in 'user_login', :with => username
      fill_in 'user_pass', :with => password
      click_button "wp-submit"
    end
  end

end