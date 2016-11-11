<?php

namespace Fbeen\UserBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Fbeen\UserBundle\Validator\Constraints\PasswordConstraint as Password;

/**
 * User trait that you could include into your own User class
 * 
 * @link https://github.com/Fbeen/UserBundle
 * 
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
trait UserTrait
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
     * @ORM\Column(name="hidden", type="boolean")
     */
    private $hidden = false;

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
     * Set hidden
     *
     * @param boolean $hidden
     *
     * @return User
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Get hidden
     *
     * @return boolean
     */
    public function getHidden()
    {
        return $this->hidden;
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
