class Component
  include Capybara::DSL

  def all(*args)
    nodes = []
    super(*args).each do | node |
      nodes << node
    end
    nodes
  end
end

class Page < Component

end