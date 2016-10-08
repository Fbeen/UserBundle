<?php

namespace Fbeen\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Register controller.
 */
class RegisterController extends Controller
{
    /**
     * Shows and processes a registrationform
     * 
     * @Template()
     */
    public function registerAction(Request $request)
    {
        $userEntity = $this->getParameter('fbeen_user.user_entity');
        $user = new $userEntity();
        
        /*
         * read session to obtain some userdata requested from a provider like for example Facebook or Google
         */
        if($registerData = $request->getSession()->get('register.data'))
        {
            $user->setUsername($registerData['username']);
            $user->setEmail($registerData['email']);
            $request->getSession()->remove('register.data');
        }
        
        $form = $this->createForm($this->container->getParameter('fbeen_user.form_types.register'), $user, array(
            'data_class' => $this->container->getParameter('fbeen_user.user_entity'),            
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager = $this->container->get('fbeen.user.user_manager');
            
            $encoder = $this->container->get('security.password_encoder');
            $user->setPassword($encoder->encodePassword($user, $user->getPlainPassword()));
            $user->setLocked(true);
            
            try {
                // if the user have to confirm his mailadres then send the user an email and redirect to a page that gives the user the directions to check his mail
                if($this->getParameter('fbeen_user.register.confirm_email'))
                {
                    $user->setConfirmationToken(sha1($user->getUsername() . 'fbeen' . date('Ymd')));
                    $user->setTokenRequested(new \DateTime());
                    
                    $manager->createUser($user);
                
                    $this->sendConfirmYourMailaddressEmail($user);

                    return $this->redirect($this->generateUrl('fbeen_user_register_confirm_email'));
                }
                
                /*
                 * otherwise if the admininstrator needs to approve the registration then we need to send the administrator(s) an email
                 */
                if($this->getParameter('fbeen_user.register.admin_approval'))
                {
                    $this->sendApproveNewAccountEmail($user);
                } else {
                    /*
                     * Unlock this account, send confirmation email and directly login this user
                     */
                    $user->setLocked(false);
                    $this->sendRegisterConfirmationEmail($user);
                    $this->get('fbeen.user.provider_helper')->login($user);
                }
                
                $manager->createUser($user);
                
                // redirect to a page that gives the user a confirmation
                return $this->redirect($this->generateUrl('fbeen_user_register_confirmation'));

            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                $form->get('email')->addError(new FormError($this->get('translator')->trans('validator.email_exists', array(), 'fbeen_user')));
            }

        }

        return array(
            'user' => $user,
            'form' => $form->createView(),
        );
    }
    
    /**
     * Shows a page that tells the user to check his email to finish the registration
     * 
     * @Template("FbeenUserBundle:Register:confirmation.html.twig")
     */
    public function confirmEmailAction()
    {
        return array(
            'title' => 'confirm_email.title',
            'header' => 'confirm_email.header',
            'message' => 'confirm_email.text',
        );
    }
    
    /**
     * On this page the user will land after clicking the link in the email.
     * 
     * @Template("FbeenUserBundle:Register:confirmation.html.twig")
     */
    public function emailConfirmationAction($token)
    {
        $manager = $this->container->get('fbeen.user.user_manager');

        $user = $manager->findUserByConfirmationToken($token);

        if(!$user || $user->getConfirmationToken() !=  $token) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        }

        $user->setConfirmationToken(NULL);
        $user->setTokenRequested(NULL);

        /*
         * if the admininstrator needs to approve the registration
         */
        if($this->getParameter('fbeen_user.register.admin_approval'))
        {
            $this->sendApproveNewAccountEmail($user);
            
            $manager->updateUser($user);

            return array(
                'title' => 'email_confirmation.with_approval.title',
                'header' => 'email_confirmation.with_approval.header',
                'message' => 'email_confirmation.with_approval.message',
            );
        }
        
        /*
         * Otherwise unlock this account, send confirmation email and directly login this user
         */
        $user->setLocked(false);
        $this->sendRegisterConfirmationEmail($user);
        $this->get('fbeen.user.provider_helper')->login($user);

        $manager->updateUser($user);
        
        return array(
            'title' => 'email_confirmation.without_approval.title',
            'header' => 'email_confirmation.without_approval.header',
            'message' => 'email_confirmation.without_approval.message',
        );
    }

    /**
     * This is the end page of the registration with information for the user.
     * 
     * @Template("FbeenUserBundle:Register:confirmation.html.twig")
     */
    public function confirmationAction()
    {
        if($this->getParameter('fbeen_user.register.admin_approval')) {
            return array(
                'title' => 'confirmation.with_approval.title',
                'header' => 'confirmation.with_approval.header',
                'message' => 'confirmation.with_approval.message',
            );
        }
        
        return array(
            'title' => 'confirmation.without_approval.title',
            'header' => 'confirmation.without_approval.header',
            'message' => 'confirmation.without_approval.message',
        );
    }

    /*
     * ask the user to confirm his mailaddress
     */
    private function sendConfirmYourMailaddressEmail($user)
    {
        $confirmationUrl = $this->generateUrl('fbeen_user_register_email_confirmation', array('token' => $user->getConfirmationToken()), UrlGeneratorInterface::ABSOLUTE_URL);
        
        $this->get('fbeen_mailer')
           ->setTo($user->getEmail())
           ->setSubject($this->get('translator')->trans('email.confirm_your_mailaddress_title', array(), 'fbeen_user'))
           ->setTemplate($this->getParameter('fbeen_user.emails_to_users.confirm_your_mailaddress.template'))
           ->setData(array(
               'user' => $user,
               'confirmation_url' => $confirmationUrl
            ))
           ->sendMail()
       ;    
    }
    
    private function sendRegisterConfirmationEmail($user)
    {
        if($this->getParameter('fbeen_user.emails_to_admins.register_confirmation.enabled'))
        {
            /*
             * send a confirmation email to the admins
             */
             $this->get('fbeen_mailer')
                ->setSubject($this->get('translator')->trans('email.register_confirmation_admin_title', array(), 'fbeen_user'))
                ->setTemplate($this->getParameter('fbeen_user.emails_to_admins.register_confirmation.template'))
                ->setData(array(
                    'user' => $user,
                 ))
                ->sendMail()
            ;    
        }
        
        if($this->getParameter('fbeen_user.emails_to_users.register_confirmation.enabled'))
        {
            /*
             * send a confirmation email to the user
             */
             $this->get('fbeen_mailer')
                ->setTo($user->getEmail())
                ->setSubject($this->get('translator')->trans('email.register_confirmation_user_title', array(), 'fbeen_user'))
                ->setTemplate($this->getParameter('fbeen_user.emails_to_users.register_confirmation.template'))
                ->setData(array(
                    'user' => $user,
                 ))
                ->sendMail()
            ;    
        }
    }

    /*
     * ask the admins to approve the new user account
     */
    private function sendApproveNewAccountEmail($user)
    {
        $this->get('fbeen_mailer')
            ->setSubject($this->get('translator')->trans('email.approve_new_account_title', array(), 'fbeen_user'))
            ->setTemplate($this->getParameter('fbeen_user.emails_to_admins.approve_new_account.template'))
            ->setData(array(
                'user' => $user
             ))
            ->sendMail()
        ;    
    }
}
