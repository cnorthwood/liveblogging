require 'capybara/cucumber'
require 'capybara/rspec'

Capybara.app_host = 'http://192.168.20.10'
Capybara.default_driver = :selenium

World do
  LiveBloggingWorld.new
end