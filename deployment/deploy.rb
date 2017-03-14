# config valid only for Capistrano 3.1
lock '3.5.0'

set :application, 'catfooddb'
set :repo_url, 'git@bitbucket.org:tammyd/catfoodDB.git'

# Default branch is :master
ask :branch, proc { `git rev-parse --abbrev-ref HEAD`.chomp }.call

# Default deploy_to directory is /var/www/my_app
set :deploy_to, '/var/www/catchowder'

# Default value for :scm is :git
# set :scm, :git

# Default value for :format is :pretty
# set :format, :pretty

# Default value for :log_level is :debug
# set :log_level, :debug

# Default value for :pty is false
# set :pty, true

# Default value for :linked_files is []
# set :linked_files, %w{config/database.yml}

# Default value for linked_dirs is []
# set :linked_dirs, %w{bin log tmp/pids tmp/cache tmp/sockets vendor/bundle public/system}

# Default value for default_env is {}
# set :default_env, { path: "/opt/ruby/bin:$PATH" }

# Default value for keep_releases is 5
# set :keep_releases, 5

# set :stages,        %w(production staging)
# set :default_stage, "staging"

# http://www.talkingquickly.co.uk/2014/01/deploying-rails-apps-to-a-vps-with-capistrano-v3/
set :linked_files, %w{.env}
set(:config_files, %w())
set :linked_dirs, %w{}
set(:executable_config_files, %w())
set(:symlinks, [
])



namespace :deploy do

  desc 'Restart application'
  task :restart do
    on roles(:app), in: :sequence, wait: 5 do
      # Your restart mechanism here, for example:
      # execute :touch, release_path.join('tmp/restart.txt')
    end
  end

  after :publishing, :restart

  after :restart, :clear_cache do
    on roles(:web), in: :groups, limit: 3, wait: 10 do
      # Here we can do anything such as:
      # within release_path do
      #   execute :rake, 'cache:clear'
      # end
    end
  end
end


set :grunt_tasks, 'dist'
after 'npm:install', 'grunt'

set :file_permissions_paths, ["templates/cache", "logs"]
set :file_permissions_chmod_mode, "a+w"

before "deploy:updated", "deploy:set_permissions:chmod"