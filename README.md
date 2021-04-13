# Drupal 8 Single Sign On
    This repository contains two drupal 8 setups (Service Provider and Identity Provider) implemented in localhost using two separate lando yml files.

## Table of Contents

   [[_TOC_]]


## Features
    - Can be logged into SP portal with the user credentials of IDP portal
    - Used a localhost setup as an IDP portal by creating crt and pem files

## Development

### Tools and Prerequisites

The following tools are required for setting up the setup. Ensure you are using the latest version or at least the minimum version mentioned below.

   * [Composer](https://getcomposer.org/download/) - v2.0.11
   * [Docker](https://docs.docker.com/install/)  - v20.10.5
   * [Lando](https://docs.lando.dev/basics/installation.html) - v3.0.28
   * [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git) - v2.25.1

*Note: Ensure you have sufficient RAM (ideally 16 GB, minimum 8 GB)*

### Local Environment Setup
    Once you have the tools installed, proceed to clone the repository and install.

```bash
$ git clone https://github.com/happy047/drupal8-sso.git
```
Change to the directory of SP repository and run lando to start.

```bash
$ cd drupal8-sso/sp
$ lando start
```
Once Lando has been setup successfully, it will display the links in the terminal. Next run the following to fetch all dependencies.

```bash
$ lando composer install
```
Once the application has successfully started, run the configuration import and database update commands.

```bash
# Import drupal configuration
$ lando drush cim
```

```bash
# Update database
$ lando drush updb
```

*Note: Perform the same above steps for IDP also by going to IDP repository*

### Post Installation

Generate a one time login link for SP repository and reset the password through it.

```bash
$ lando drush uli
```

Clear the cache using drush

```bash
$ lando drush cr
```

You can access the site at: [http://sp.lndo.site/](http://sp.lndo.site/).

Generate a one time login link for IDP repository and reset the password through it.

```bash
$ lando drush uli
```

Clear the cache using drush

```bash
$ lando drush cr
```

You can access the site at: [http://idp.lndo.site/](http://idp.lndo.site/).

### Final Step

Now create some users in your IDP portal and then open your SP portal. You will see login button on the right most top section.
Click on login and you will see in your login page, there is an IDP login link. CLick on that link and enter your IDP user credentials.
You will be now logged in in your SP portal and if you see in the people's list from admin end, you will notice the same user of the IDP
portal has been created in your SP portal.


## How to setup your own Drupal 8 SP and IDP

### Configure SP portal

Create a drupal 8 instance in your local directory

```bash
$ composer create-project drupal/recommended-project:8.9.13 your-sp
```

*Note: your-sp is the name of drupal 8 directory. You can name whatever you want to.*

Navigate to your-sp directory and create lando yml file

```bash
$ lando init
``` 

*Note: you can copy the lando yml from the SP directory to your directory. In that case, you won't have to create lando*

Initialize your-sp portal by starting lando 

```bash
$ lando start
``` 
After lando is initialized go to your localhost site link provided at the end of lando initialization or you can
get your base url using the following command

```bash
$ lando info
``` 
After you have completed your drupal 8 setup, it's time to setup your sso sp configuration. For that you will need simplesamlphp library and module.
Thanks to composer you can download the module and all its dependencies using the following command

```bash
$ lando composer require drupal/simplesamlphp_auth
``` 

The above command will download all the dependencies along with the module. Dont enable it for now, let us first configure the simplesamlphp library first.
At first, lets create a symlink for the www folder in simplesamlphp library that can be found under vendor/simplesamlphp/simplesamlphp directory.
Generating symlink directly from root directory may cause some issues. So, lets keep it simple by navigating to web folder and run the following command

```bash
$ ln -s ../vendor/simplesamlphp/simplesamlphp/www simplesaml
``` 

If you have configured your drupal setup properly, it will create a symlink for the www folder under web directory. Next, we tell our htaccess file in the web directory to allow access to simplesaml path. Open the htaccess file and write the following command

```bash
    RewriteCond %{REQUEST_URI} !^/simplesaml
``` 
For reference, you can also checkout my SP portal's htaccess file.
After that, we navigate to vendor/simplesamlphp/simplesamlphp directory. Copy the config.php and authsources.php from config-templates folder to config folder. Now, open the config.php file and modify the following changes

```bash
    'secretsalt' => 'randomstring', // any random string
    'auth.adminpassword' => 'admin', // any random password
    'enable.saml20-idp' => true,
    'store.type'  => 'sql',
    'store.sql.dsn' => sprintf('mysql:host=%s;port=%s;dbname=%s', 'database', '', 'sp'), // sp was my db name and my host was database
    'store.sql.username' => 'drupal8', // your db username
    'store.sql.password' => 'drupal8', // your db password
``` 
Leave the other things intact.

Now create a directory named cert under vendor/simplesamlphp/simplesamlphp
```bash
$ mkdir cert
$ cd cert
``` 
To create saml.crt and saml.pem, run the following command

```bash
$ openssl req -new -x509 -days 3652 -nodes -out saml.crt -keyout saml.pem
``` 

Now navigate to baseurl/simplesaml. You will now see the simplesaml welcome modal. If you dont see the modal, please backtrace the steps and see if you have done something different. You can always refer to my SP portal for workflow guidance. For eg. http://sp.lndo.site/simplesaml

*Note: At this stage, we haven't enabled the module yet. We will modify the authsources.php file after we set up IDP portal.* 

### Configure IDP portal

You need to perform the exact same steps performed during the configuration of SP portal. 
Now create a directory named cert under vendor/simplesamlphp/simplesamlphp
```bash
$ mkdir cert
$ cd cert
``` 
To create server.crt and server.pem, run the following command

```bash
$ openssl req -new -x509 -days 3652 -nodes -out server.crt -keyout server.pem
``` 
After configuring exactly like SP portal, you should see the simplesaml welcome modal by navigating to baseurl/simplesaml. For eg. http://idp.lndo.site/simplesaml

*Note: We need to create server.crt and server.pem for IDP portal, otherwise it will show error when we validate the IDP metadata*

### Connect SP with IDP

At first we need to download and install a module in IDP portal which will configure the Drupal instance to act as IDP.
For that, go to your IDP directory and run the following command

```bash
$ lando composer require drupal/drupalauth4ssp
``` 
This will download a module called "DrupalAuth for SimpleSAMLphp" which will enable drupal IDP users authentication. After downloading, you will see
a module under vendor/simplesamlphp/simplesamlphp/modules directory has been downloaded apart from the contrib module. This will mean that the module has
successfully downloaded all the dependencies. Lets enable the module

```bash
$ lando drush en drupalauth4ssp -y
``` 
After that, go to the authsources.php file of your IDP folder under vendor/simplesamlphp/simplesamlphp/config directory and write the following syntax within
the config array

```bash
    'drupal-userpass' => array('drupalauth:External',

        // The filesystem path of the Drupal directory.
        'drupalroot' => '/app/web',

        // Whether to turn on debug
        'debug' => true,

        // the URL of the Drupal logout page
        'drupal_logout_url' => 'http://idp.lndo.site/user/logout', // paste your IDP base url before user/logout

        // the URL of the Drupal login page
        'drupal_login_url' => 'http://idp.lndo.site/user/login', // paste your IDP base url before user/logout

        // Which attributes should be retrieved from the Drupal site.
            'attributes' => array(
                array('field_name' => 'roles', 'attribute_name' => 'roles', 'field_property' => 'target_id'),
                array('field_name' => 'name', 'attribute_name' => 'uid'),
                array('field_name' => 'mail', 'attribute_name' => 'mail'),
            ),
        ),
``` 
The above syntax will ensure the sso authentication to be done using IDP's drupal login page. Since, the setup is in lando, my drupalroot is /app/web.

*Note: Refer to my IDP portal's authsources.php in case of any issue*

FOR IDP portal: copy saml20-idp-hosted.php and saml20-sp-remote.php from metadata-templates folder to metadata folder found under
vendor/simplesamlphp/simplesamlphp. Open saml20-idp-hosted.php and only modify the following

```bash
'auth' => 'drupal-userpass',
```
For SP portal: copy saml20-idp-remote.php from metadata templates folder to metadata folder found under vendor/simplesamlphp/simplesamlphp.

Open your Idp portal simplesaml welcome portal and go to the federation tab. You will see that IDP metadata has been generated. Under the metadata tab, click on the link show metadata. Copy the simplesaml file format metadata and paste it to saml20-idp-remote.php under 
SP portals vendor/simplesamlphp/simplesamlphp/metadata folder.

Go to authsources.php of your SP portal, copy the default-sp array and duplicate it below the default-sp array. Rename the default-sp as localhost-sp.
Modify the entity-id and idp with your idp metadata url.

Now go to you SP portals simplesaml. i.e baseurl/simplesaml and navigate to the federation tab. Open the metadata of localhost-sp and copy the simplesaml
metadata array and paste it to saml20-sp-remote.php of your IDP portal located under vendor/simplesamlphp/simplesamlphp/metadata folder.

Now enable your simplesamlphp_auth modules in your both SP and IDP portal. Automatically, externalauth module will be enabled.
```bash
$ lando drush en simplesamlphp_auth -y
```

Go to the simplesamlphp_auth configuration of your SP portal(relative path = 'admin/config/people/simplesamlphp_auth').

* Change the authentication source for the sp ['localhost-sp']. 
* Rename the Federated login to any link name you want to give.
* Check Register-users under User Provisioning.
* Do not Check the activate authentication checkbox before you configure the rest.
* Go to local authentication and check both the checkbox of drupal authentication.
* Select which roles you want to insert. For eg. authenticated user.
* Go to user info and syncing tab.
* Modify both the attribute values to uid. 
* For the email address value, change it to ['mail']
* Check both the syncronize checkboxes.
* Also check the automatically enable saml authentication.
* Lastly, activate the authentication checkbox in basic tab and save it.
* Clear the cache.

THATS IT. YOUR SP and IDP has been connected. Now create some users in your IDP portal. After that, got to your SP portal login page and click on the
SSO login link. You will be redirected to IDP portal for logging in. Enter the username and password and then you will see it will automatically redirect
you to SP portal and you have been logged in. If you go to users list from admin panel, you will see that the user has been created in your SP portal.