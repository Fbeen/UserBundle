# FbeenUserBundle

This Bundle adds complete user integration on top of the Symfony security bundle. It lets you register and manage users for your website and let the users login with or without providers such as Facebook or Google, edit their profiles, change or reset their passwords.

### Features include:

* Doctrine ORM database storage of your own user class
* Bootstrap ready pages and forms
* Login with traditional login form or with OAuth providers such as Facebook, Google or Twitter
* Login with just their mailaddress and password
* Registrate with or without email verification
* Registrate with or without admin approval
* Registrate with or without confirmation emails
* Show and edit profile (with or without password security)
* Change password
* Reset password
* Use your own User entity
* Use your own form types
* Configurable password constraints


## Installation

Using composer:

1) Add `"fbeen/userbundle": "dev-master"` to the require section of your composer.json project file.

```
    "require": {
        ...
        "fbeen/userbundle": "dev-master"
    },
```

2) run composer update:

    $ composer update

3) Add the bundles to the app/AppKernel.php:
```
        $bundles = array(
            ...
            new Fbeen\MailerBundle\FbeenMailerBundle(),
            new Fbeen\UserBundle\FbeenUserBundle(),
        );
```
4) add routes to app/config/routing.yml
```
fbeen_user:
    resource: "@FbeenUserBundle/Resources/config/routing.yml"
    prefix:   /
```

5) Place a empty `{% block fbeen_user %}` in your twig layout there where the content should appear. Typically between the {% block body %} and the {% endblock %} tags.
```
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>{% block title %}Welcome!{% endblock %}</title>
        {% block stylesheets %}{% endblock %}
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />
    </head>
    <body>
        {% block body %}
            {% block fbeen_user %}{% endblock %}
        {% endblock %}
        {% block javascripts %}{% endblock %}
    </body>
</html>
```
6) Enable Translation in `app/config/config.yml`
```
parameters:
    locale: en

framework:
    translator:      { fallbacks: ["%locale%"] }
```
7) Configure `app/config/security.yml` like below:
```
security:

    encoders:
        AppBundle\Entity\User:
            algorithm: bcrypt
            cost: 12
            
    role_hierarchy:
        ROLE_ADMIN:       ROLE_USER
        ROLE_SUPER_ADMIN: ROLE_ADMIN

    providers:
        our_db_provider:
            entity:
                class: AppBundle:User
                property: email

    firewalls:
        # disables authentication for assets and the profiler, adapt it according to your needs
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        main:
            anonymous: ~
            pattern:    ^/
            provider: our_db_provider
            form_login:
                login_path: /login
                check_path: /login_check
                csrf_token_generator: security.csrf.token_manager       # FOR SYMFONY 2.7 OR BELOW USE:   csrf_provider: security.csrf.token_manager
            logout:
                path:   /logout
                target: /

    access_control:
        # require ROLE_SUPER_ADMIN for /admin/app/user*
        - { path: ^/admin/app/user, roles: ROLE_SUPER_ADMIN }        
        # require ROLE_ADMIN for /admin*
        - { path: ^/admin, roles: ROLE_ADMIN }
```
8) add an User entity to your AppBundle `src/AppBundle/Entity/User.php`
```
<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

/**
 * @ORM\Table(name="users")
 * @ORM\Entity
 */
class User implements AdvancedUserInterface, \Serializable
{
    use \Fbeen\UserBundle\Model\UserTrait;
    
    /*
     * Extend your class here when you need
     */
}
```
9) Update your database schema
```
$ bin/console doctrine:schema:update --force
```
10) Create a new user and promote him to ROLE_SUPER_ADMIN
```
$ bin/console fbeen:user:create "My Name" "email@example.com" "password"
$ bin/console fbeen:user:promote "My Name" ROLE_SUPER_ADMIN
```
11) Add minimal configuration for the FbeenMailerBundle in `app/config/config.yml`
```
fbeen_mailer:
    mailaddresses:
        noreply: no-reply@example.com
        general: info@example.com
        admins: [admin1@gmail.com, admin2@hotmail.com]
```

## Configuration

Full configuration with default values:
```
fbeen_user:
    user_entity: AppBundle\Entity\User
    form_types:
        change_password: Fbeen\UserBundle\Form\ChangePasswordType
        profile: Fbeen\UserBundle\Form\ProfileType
        register: Fbeen\UserBundle\Form\RegisterType
    password_constraints:
        minlength: 6
        nummeric: 0
        letters: 0
        special: 0
    password_on_profile_edit: false
    register:
        confirm_email: true
        admin_approval: true
    available_roles:
        -
            role: ROLE_USER
            label: Normal user
        -
            role: ROLE_ADMIN
            label: Administrator           
    providers: # optional
        Facebook:
            key: xxxxxxxxxxxxxxx
            secret: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
            scope: email
            image: "bundles/fbeenuser/images/facebook.png"
        Google:
            key: xxxxxxxxxxx-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.apps.googleusercontent.com
            secret: xxxxxxxxxxxxxxxxxxxxxxxx
            image: "bundles/fbeenuser/images/google.png"
        # more providers
    emails_to_admins:
        approve_new_account:
            template: FbeenUserBundle:Email:approve_new_account.html.twig
        register_confirmation:
            enabled: false
            template: FbeenUserBundle:Email:register_confirmation_admin.html.twig
    emails_to_users:
        confirm_your_mailaddress:
            template: FbeenUserBundle:Email:confirm_your_mailaddress.html.twig
        register_confirmation:
            enabled: true
            template: FbeenUserBundle:Email:register_confirmation_user.html.twig
        new_account_details:
            enabled: true
            template: FbeenUserBundle:Email:new_account_details_user.html.twig
```
If you would like to render the formfields on bootstrap style:
```
# Twig Configuration
twig:
    debug:            "%kernel.debug%"
    strict_variables: "%kernel.debug%"
    form_themes:
        - 'bootstrap_3_layout.html.twig'
```
* to be continued
