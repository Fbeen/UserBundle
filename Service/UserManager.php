<?php

namespace Fbeen\UserBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Layer between the application and the database
 *
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
class UserManager
{
    private $container;
    private $em;


    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $this->container->get('doctrine.orm.entity_manager');
    }
    
    public function findUserById($id)
    {
        return $this->em->getRepository($this->container->getParameter('fbeen_user.user_entity'))->find($id);
    }
    
    public function findUserByUsername($username)
    {
        return $this->em->getRepository($this->container->getParameter('fbeen_user.user_entity'))->findOneBy(array('username' => $username));
    }
    
    public function findUserByEmail($email)
    {
        return $this->em->getRepository($this->container->getParameter('fbeen_user.user_entity'))->findOneBy(array('email' => $email));
    }
    
    public function findUserByConfirmationToken($token)
    {
        return $this->em->getRepository($this->container->getParameter('fbeen_user.user_entity'))->findOneBy(array('confirmation_token' => $token));
    }
    
    public function createUser(UserInterface $user)
    {
        $this->updatePassword($user);
        $user->setCreated(new \DateTime());
        
        $this->em->persist($user);
        $this->em->flush();
    }
    
    public function updateUser(UserInterface $user)
    {
        $this->updatePassword($user);
        $this->em->flush();
    }
    
    public function updatePassword(UserInterface $user)
    {
        $encoder = $this->container->get('security.password_encoder');
        
        if (0 !== strlen($user->getPlainPassword())) {
            $user->setPassword($encoder->encodePassword($user, $user->getPlainPassword()));
            $user->eraseCredentials();
        }
    }
    
    public function generateRandomPassword($length = 8)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890!@#$%^&*/-+';
        $str = '';
        
        $alphamax = strlen($alphabet) - 1;

        for ($i = 0; $i < $length; ++$i) {
            $str .= $alphabet[random_int(0, $alphamax)];
        }
        
        return $str;
    }

    public function sendNewAccountDetailsEmail($user, $password)
    {
        if($this->container->getParameter('fbeen_user.emails_to_users.new_account_details.enabled'))
        {
            /*
             * send a confirmation email to the user with his credentials
             */
             $this->container->get('fbeen_mailer')
                ->setTo([$user->getEmail() => $user->getUsername()])
                ->setSubject($this->container->get('translator')->trans('email.new_account_details_user_title', array(), 'fbeen_user'))
                ->setTemplate($this->container->getParameter('fbeen_user.emails_to_users.new_account_details.template'))
                ->setData(array(
                    'user' => $user,
                    'password' => $password
                 ))
                ->sendMail()
            ;
        }
    }
}
