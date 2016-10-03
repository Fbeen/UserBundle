# FbeenUserBundle

This Bundle adds complete user integration on top of the Symfony security bundle. It lets you register and manage users for your website and let the users login with or without providers such as Facebook or Google, edit their profiles, change or reset their passwords.

### Features include:

* Doctrine ORM database storage of your own user class
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
    password_on_profile_edit: true
    register:
        confirm_email: true
        admin_approval: false
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
```

* to be continued