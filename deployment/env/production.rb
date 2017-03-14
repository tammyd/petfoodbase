# Simple Role Syntax
# ==================
# Supports bulk-adding hosts to roles, the primary server in each group
# is considered to be the first unless any hosts have the primary
# property set.  Don't declare `role :all`, it's a meta role.

# Extended Server Syntax
# ======================
# This can be used to drop a more detailed server definition into the
# server list. The second argument is a, or duck-types, Hash and is
# used to set extended properties on the server.

# Custom SSH Options
# ==================
# You may pass any option but keep in mind that net/ssh understands a
# limited set of options, consult[net/ssh documentation](http://net-ssh.github.io/net-ssh/classes/Net/SSH.html#method-c-start).
#
# Global options
# --------------
set :ssh_options, {
   keys: %w(/home/tammyd/.ssh/id_rsa),
   port: 2222,
   forward_agent: false,
   auth_methods: %w(publickey password)
}

# server 'catfooddb.com', user: 'deployer', roles: %w{web app}, port: 2222

server '138.68.193.111', user: 'deployer', roles: %w{web app}, port: 2222



