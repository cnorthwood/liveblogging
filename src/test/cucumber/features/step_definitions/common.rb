Given(/^I am logged in as an? (admin)$/) do | role |
  ensure_login role.to_sym
end

Given(/^there are (?:some live blog entries|active live blogs|a number of active and inactive live blogs)$/) do
  # these cases are covered by the default database
end

Given(/^there are no active live blogs$/) do
  pending
end
