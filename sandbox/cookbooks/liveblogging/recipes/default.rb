bash "apt-get update" do
  user "root"
  code "apt-get update"
end
package "build-essential"

include_recipe "wordpress"

template "/tmp/fresh-install.sql" do
  source "fresh-install.sql.erb"
  action :create
end

bash "apt-get update" do
  code "mysql -u #{node['wordpress']['db']['user']} -p#{node['wordpress']['db']['password']} #{node['wordpress']['db']['database']} < /tmp/fresh-install.sql"
end