# CAS Attributes
[![https://drupalcode.org/project/cas_attributes/badges/3.x/pipeline.svg](https://drupalcode.org/project/cas_attributes/badges/3.x/pipeline.svg)](https://drupalcode.org/project/cas_attributes)

Allows you to assign user field values (text fields only) and user roles based
on attributes received from your CAS server during authentication.

It also exposes CAS attributes as tokens. One example where this is useful is if
you have a webform and you want to pre-fill certain webform fields with
attribute values from CAS (like name and email).

## Contributing

[DDEV](https://ddev.com), a Docker-based PHP development tool for a streamlined
and unified development process, is the recommended tool for contributing to the
module. The [DDEV Drupal Contrib](https://github.com/ddev/ddev-drupal-contrib)
addon makes it easy to develop a Drupal module by offering the tools to set up
and test the module.

### Install DDEV

* Install a Docker provider by following DDEV [Docker Installation](https://ddev.readthedocs.io/en/stable/users/install/docker-installation/)
  instructions for your Operating System.
* [Install DDEV](https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/),
  use the documentation that best fits your OS.
* DDEV is used mostly via CLI commands. [Configure shell completion &
  autocomplete](https://ddev.readthedocs.io/en/stable/users/install/shell-completion/)
  according to your environment.
* Configure your IDE to take advantage of the DDEV features. This is a critical
  step to be able to test and debug your module. Remember, the website runs
  inside Docker, so pay attention to these configurations:
    - [PhpStorm Setup](https://ddev.readthedocs.io/en/stable/users/install/phpstorm/)
    - [Configure](https://ddev.readthedocs.io/en/stable/users/debugging-profiling/step-debugging/)
      PhpStorm and VS Code for step debugging.
    - Profiling with [xhprof](https://ddev.readthedocs.io/en/stable/users/debugging-profiling/xhprof-profiling/),
      [Xdebug](https://ddev.readthedocs.io/en/stable/users/debugging-profiling/xdebug-profiling/)
      and [Blackfire](https://ddev.readthedocs.io/en/stable/users/debugging-profiling/blackfire-profiling/).

### Checkout the module

Normally, you check out the code form an [issue fork](https://www.drupal.org/docs/develop/git/using-gitlab-to-contribute-to-drupal/creating-issue-forks):

```shell
git clone git@git.drupal.org:issue/cas_attributes-[issue number].git
cd cas_attributes-[issue number]
```

### Start DDEV

Inside the cloned project run:

```shell
ddev start
```

This command will fire up the Docker containers and add all configurations.

### Install dependencies

```shell
ddev poser
```

This will install the PHP dependencies. Note that this is a replacement for
Composer _install_ command that knows how to bundle together Drupal core and the
module. Read more about this command at
https://github.com/ddev/ddev-drupal-contrib?tab=readme-ov-file#commands

```shell
ddev symlink-project
```

This symlinks the module inside `web/modules/custom`. Read more about this
command at https://github.com/ddev/ddev-drupal-contrib?tab=readme-ov-file#commands.
Note that as soon as `vendor/autoload.php` has been generated, this command runs
automatically on every `ddev start`.

This command should also be run when adding new directories or files to the root
of the module.

```shell
ddev exec "cd web/core && yarn install"
```

Install Node dependencies. This is needed for the `ddev eslint` command.

### Install Drupal

```shell
ddev install
```

This will install Drupal and will enable the module.

### Changing the Drupal core version

* Create a file `.ddev/config.local.yaml`
* Set the desired Drupal core version. E.g.,
  ```yaml
  web_environment:
    - DRUPAL_CORE=^10.3
  ```
* Run `ddev restart`

### Run tests

* `ddev phpunit`: run PHPUnit tests
* `ddev phpcs`: run PHP coding standards checks
* `ddev phpcbf`: fix coding standards findings
* `ddev phpstan`: run PHP static analysis
* `ddev eslint`: Run ESLint on Javascript and YAML files.
