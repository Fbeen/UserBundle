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
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Fbeen\UserBundle\Validator\Constraints\PasswordConstraint as Password;

/**
 * @ORM\Table(name="users")
 * @ORM\Entity
 */
class User implements AdvancedUserInterface, \Serializable
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Assert\NotBlank(message="Please enter your name", groups={"registration", "profile"})
     * 
     * @ORM\Column(type="string", length=32)
     */
    private $username;

    /**
     * @Assert\NotBlank(message="Please enter your password", groups={"registration", "password"})
     * @Password(groups={"registration", "password"})
     */
    private $plainPassword;

    /**
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @Assert\NotBlank(message="Please enter your email", groups={"password", "registration"})
     * @Assert\Email()
     * 
     * @ORM\Column(type="string", length=128, unique=true)
     */
    private $email;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    private $created;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_request", type="datetime", nullable=true)
     */
    private $lastRequest;
    
    /**
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled;
    
    /**
     * @ORM\Column(name="locked", type="boolean")
     */
    private $locked;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $confirmation_token;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $tokenRequested;

    /**
     * @var array
     *
     * @ORM\Column(name="roles", type="array")
     */
    private $roles;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $facebook;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $google;

    public function __construct() {
        $this->created = new \DateTime();
        $this->lastRequest = new \DateTime();
        $this->enabled = true;
        $this->locked = true;
        $this->roles = array('ROLE_USER');
    }
    
    public function __toString()
    {
        return $this->username;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getSalt()
    {
        // you *may* need a real salt depending on your encoder
        // see section on salt below
        return null;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function hasRole($role)
    {
        return in_array($role, $this->roles);
    }

    public function eraseCredentials()
    {
    }

    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return !$this->locked;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }
    
   /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
            $this->enabled
            // see section on salt below
            // $this->salt,
        ));
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->password,
            $this->enabled
            // see section on salt below
            // $this->salt
        ) = unserialize($serialized);
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set username
     *
     * @param string $username
     *
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     *
     * @return User
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set locked
     *
     * @param boolean $locked
     *
     * @return User
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Get locked
     *
     * @return boolean
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * Set confirmationToken
     *
     * @param string $confirmationToken
     *
     * @return User
     */
    public function setConfirmationToken($confirmationToken)
    {
        $this->confirmation_token = $confirmationToken;

        return $this;
    }

    /**
     * Get confirmationToken
     *
     * @return string
     */
    public function getConfirmationToken()
    {
        return $this->confirmation_token;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return User
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set facebook
     *
     * @param string $facebook
     *
     * @return User
     */
    public function setFacebook($facebook)
    {
        $this->facebook = $facebook;

        return $this;
    }

    /**
     * Get facebook
     *
     * @return string
     */
    public function getFacebook()
    {
        return $this->facebook;
    }

    /**
     * Set google
     *
     * @param string $google
     *
     * @return User
     */
    public function setGoogle($google)
    {
        $this->google = $google;

        return $this;
    }

    /**
     * Get google
     *
     * @return string
     */
    public function getGoogle()
    {
        return $this->google;
    }

    /**
     * Set plainPassword
     *
     * @param string $plainPassword
     *
     * @return User
     */
    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * Get plainPassword
     *
     * @return string
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * Set tokenRequested
     *
     * @param \DateTime $tokenRequested
     *
     * @return User
     */
    public function setTokenRequested($tokenRequested)
    {
        $this->tokenRequested = $tokenRequested;

        return $this;
    }

    /**
     * Get tokenRequested
     *
     * @return \DateTime
     */
    public function getTokenRequested()
    {
        return $this->tokenRequested;
    }

    /**
     * Set lastRequest
     *
     * @param \DateTime $lastRequest
     *
     * @return User
     */
    public function setLastRequest($lastRequest)
    {
        $this->lastRequest = $lastRequest;

        return $this;
    }

    /**
     * Get lastRequest
     *
     * @return \DateTime
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * Set roles
     *
     * @param array $roles
     *
     * @return Test
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Get roles
     *
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Add a role
     *
     * @return array
     */
    public function addRole($role)
    {
        if(!$this->hasRole($role))
        {
            $this->roles[] = $role;
        }
    }
}
```
9) Update your database schema
    $ bin/console doctrine:schema:update --force

10) Create a new user and promote him to ROLE_SUPER_ADMIN
$  bin/console fbeen:user:create "My Name" "email@example.com" "password"
$  bin/console fbeen:user:promote "My Name" ROLE_SUPER_ADMIN

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