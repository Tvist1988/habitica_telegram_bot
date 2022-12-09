<?php
namespace Deployer;

require 'deployer/laravel_new.php';

// Config

set('repository', 'git@github.com:Tvist1988/habitica_bot.git');
set ('ssh_multiplexing', false);
set('git_tty', false);

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);


// Hosts

host('94.103.83.185')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '/var/www/habitica/');

// Hooks

after('deploy:failed', 'deploy:unlock');
