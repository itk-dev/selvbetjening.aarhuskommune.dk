# selvbetjening.aarhuskommune.dk

This project is an instance of [OS2Forms8](https://github.com/os2forms/os2forms8) hosted om https://selvbetjening.aarhuskommune.dk.
Concretely this means that the master branch of the [OS2Forms8](https://github.com/os2forms/os2forms8) repository is considered as upstream for the develop branch in this repository.

## Project describtion (Aarhus)
OS2forms is a supporting system for when your Electronic document and records management system (EDRMS) does not have the opportunity to get information directly from the contributors. Via the solution, you can e.g. create forms and digitize multi-party workflows. The purpose of the solution is to further save time for the citizen and employees by providing transparency and overview.
Examples of workflows can be Requests for psycological assistence for children, ordering aids, hiring new employees or reporting maternity leave.


## Getting started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes. See deployment for notes on how to deploy the project on a live system.

### Prerequisites

* [Docksal](https://docksal.io/)

### Installing

1. Clone the git repository
   ```sh
   git clone git@github.com:itk-dev/selvbetjening.aarhuskommune.dk selvbetjening
   ```

2. Enter the newly created project directory
   ```sh
   cd selvbetjening
   ```

3. Start docksal environment
   ```sh
   fin start
   ```

4. Create local settings
   ```sh
   cp web/sites/example.settings.local.php web/sites/default/settings.local.php
   ```

5. Add file permission fix to settings.local.php. See https://docs.docksal.io/apps/drupal/#file-permissions
   ```php
   // web/sites/default/settings.local.php

   $settings['file_chmod_directory'] = 0777;
   $settings['file_chmod_file'] = 0666;
   ```

6. Install the OS2forms Forløb module with necessary configurations.
   ```sh
   fin build-forloeb
   ```

7. Configure trusted hosts in settings.local.php (add the following if not present)
   ```php
   // web/sites/default/settings.local.php

   $settings['trusted_host_patterns'] = ['^selvbetjening.docksal$', '^localhost$'];
   ```

You should now be able to browse to the application at `http://selvbetjening.docksal`

## Pulling new releases from [OS2Forms8](https://github.com/os2forms/os2forms8)

1. Add the OS2Forms8 repository to your local clone of this repository:
   ```shell
   git remote add os2forms8 git@github.com:OS2Forms/os2forms8.git
   ```

2. Fetch changes:
   ```shell
   git fetch
   ```

3. Create an update branch:
   ```shell
   git checkout -b feature/pull-changes-from-os2forms8
   git merge --ff os2forms/master
   ```

4. Fix any merge conflicts and commit the changes.

5. Create pull request with your changes with the develop branch as the target and tag a colleague for code review.

### Handling changes in [OS2Forms8](https://github.com/os2forms/os2forms8) install profile

@TODO

## Deployment

### Initially

These instructions will get you a copy of the project up and running on a live system.
For a more detailed description, you could look at the `web/core/INSTALL.txt` [here](./web/core/INSTALL.txt).

#### Prerequisites

* A HTTP server such as [Apache](https://httpd.apache.org/) that supports PHP
* A database service such as [MySQL](https://www.mysql.com/)
* PHP 7.4 with the following extensions enabled:
  * gd
  * curl
  * simplexml
  * xml
  * dom
  * soap
  * mbstring
  * zip
  * database specific extension such as the mysql extension
* [Composer](https://getcomposer.org/)
* [Drush launcher](https://github.com/drush-ops/drush-launcher)

#### Installing

1. Clone the git repository
   ```shell
   git clone git@github.com:itk-dev/selvbetjening.aarhuskommune.dk selvbetjening
   ```

2. Enter the newly created project directory
   ```shell
   cd selvbetjening
   ```

3. Make sure you are on the main branch:
   ```shell
   git checkout main
   ```

4. Install dependencies without development dependencies
   ```shell
   composer install --no-dev
   ```

5. Create local settings
   ```sh
   cp web/sites/default.settings.local.php web/sites/default/settings.local.php
   ```
6. Add databases settings to web/sites/default/settings.local.php
   ```php
   $databases['default']['default'] = array (
     'database' => '[dbname]',
     'username' => '[dbuser]',
     'password' => '[dbpass]',
     'prefix' => '',
     'host' => '[dbhost]',
     'port' => '',
     'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
     'driver' => 'mysql',
   );
   ```

7. Generate a salt string and insert it in web/sites/default/settings.local.php
   ```shell
   # Generate salt string - this will output a new salt string
   ./vendor/bin/drush php-eval 'echo \Drupal\Component\Utility\Crypt::randomBytesBase64(55) . "\n";'
   ```

   ```php
   // web/sites/default/settings.php
   $settings['hash_salt'] = ''; // Insert the generated salt string here
   ```

8. Configure trusted hosts in web/sites/default/settings.local.php.
   For more information on how to write this, see the section for [Trusted Host settings](https://www.drupal.org/docs/installing-drupal/trusted-host-settings)
   in the official Drupal installation guide.
   ```php
   // web/sites/default/settings.local.php

   $settings['trusted_host_patterns'] = [''];
   ```
9. Install Drupal

   Using drush command:
   ```shell
   # To install OS2Forms 2
   ./vendor/bin/drush si os2forms_forloeb_profile --account-pass=account_password --site-name="OS2Forms med forløb"
   ```

9. Enable OS2Forms modules
   ```shell
   ./vendor/bin/drush en os2forms, os2forms_nemid, os2forms_dawa, os2forms_sbsys
   ```

### Updating an already installed version

These instructions assume you already have an installed version of this repository on a live system.

1. Pull recent changes:
   ```shell
   git checkout main
   git pull
   ```

2. Install composer packages:
   ```shell
   composer install --no-dev
   ```

3. Import any configuration changes:
   ```shell
   ./vendor/bin/drush config import
   ```

4. Clear and rebuild the cache:
   ```shell
   ./vendor/bin/drush cr
   ```
