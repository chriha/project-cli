# Project CLI
**ProjectCLI** is a command line tool that translates complex tasks into simple, single commands.
It also helps keeping a standard project structure across all projects. Here are some benefits of
using ProjectCLI:

- Initialize, setup and start whole environments (incl. web server, database, caching, mail server,
 etc.) in seconds
- Set up even complex projects with a single command
- Use (force) the same directory structure in **every** project
- Reduce amount of necessary commands for each developer
- The same environment, tools and versions for every developer
- Easier and colored log-tailing
- Write your own [commands](https://github.com/chriha/project-cli/wiki/Commands) and
 [plugins](https://github.com/chriha/project-cli/wiki/Plugins) to extend ProjectCLI
- Simple `/etc/hosts` and SSH config management

For the plugin registry, more info, the documentation and some examples, check out [cli.lazu.io](https://cli.lazu.io).


## TOC
- [Getting Started](#getting-started)
  - [Dependencies](#prerequisites)
  - [Install](#install)
  - [Update](#update)
- [Usage](#usage)
  - [Create a new project](#create-a-new-project)
  - [Start and stop environment and its services](#start-and-stop-environment-and-its-services)
  - [Run any service specific command](#run-any-service-specific-command)
  - [Show service status and resource statistics](#show-service-status-and-resource-statistics)
  - [Logging](#logging)
  - [Hosts File](#hosts-file)
  - [Xdebug](#xdebug)
  - [Docker commands](#docker-commands)
- [Uninstall](#uninstall)


## Getting Started
### Prerequisites
- PHP CLI 7.2 or newer (incl. extensions: json, intl, xml, curl)

### Install
After you've installed all [dependencies](#prerequisites), get the latest release [here](https://github.com/chriha/project-cli/releases) and move it to `/usr/local/bin/project` or `/usr/bin/project`, depending on which paths are included in your `$PATH` variable. The `project` command will be available after you restart your terminal session.

### Update
To manually update **ProjectCLI**, just use the `project self-update` command.


## Usage
> **It's mandatory, that the project has the according directory structure and files in order for ProjectCLI to work properly.**

### Project Structure
```
- commands
  | Contains project specific commands, created via 'project make:command'
- conf
  | Add configuration files for components (like nginx, PHP, crontab, supervisor, etc)
- scripts
  | Can contain scripts for deployment, HTTP requests or other complex tasks
- src
  | Contains the application src
- temp
  | Directory for temporary files, such as docker-compose service mounts
```

### Create a new project
```shell
project create DIRECTORY [--type=php|node|python] [--repository=URL_TO_YOUR_REPOSITORY]
```

### Clone and automatically install existing projects
To create an automated setup for an existing project, you need to add a `setup` command via
`project make:command SetupCommand`. In the `handle()` method, you specify the commands to set up
the project (eg. copy env files, run migrations, seed test data, compile static files, etc).

```shell
project clone REPOSITORY_URL [DIRECTORY]
```
The `clone` command will clone the repository and ask, if the project should be set up via the
existing `setup` command.


### Start and stop environment and its services
```shell
project [up|down|restart]
```

### Run any service specific command
ProjectCLI will run all commands inside the according Docker service.
```shell
# for the web service
project [artisan|composer|...]
# for node / npm
project [node|npm install|run|...]
```

### Show service status and resource statistics
```shell
project help status
```

### Logging
Find all your log files and see what's happening with your application. It'll also warn you, if your files get too big.
```shell
project help logs:tail
```

### Xdebug
Enable and disable Xdebug with a single command.
```shell
project help php:xdebug
```

### Hosts File
List, enable, disable, add, remove and check hosts for existence
```shell
project help hosts
```
Whenever you change the hosts file (eg. enable, disable, add, rm), you have to run the command with
sudo / as root.
> **ProjectCLI will create backups, but only keeps the last two versions.**

### Docker commands
Run Docker Compose commands with your `docker-compose.yml`
```shell
project help docker:compose
```
Using bash inside a container / service
```shell
project docker:exec [DOCKER_SERVICE] bash
```


## Uninstall
```shell
rm -rf $HOME/.project $(which project)
```
