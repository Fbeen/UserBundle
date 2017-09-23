<?php

namespace Fbeen\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpKernel\Kernel;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Password controller.
 *
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
class PasswordController extends Controller
{
    /**
     * On this page a user that is logged in can change his password.
     * 
     * @Security("has_role('ROLE_USER')")
     * @Template()
     */
    public function changeAction(Request $request)
    {
        $user = $this->getUser();
        
        $formType = $this->container->getParameter('fbeen_user.form_types.change_password');
        if(Kernel::VERSION_ID < 30000) {
            $formType = new $formType;
        }
        
        $form = $this->createForm($formType, $user, array(
            'data_class' => $this->container->getParameter('fbeen_user.user_entity'),
        ));
        $form->handleRequest($request);

        if($form->isSubmitted())
        {
            $encoder = $this->container->get('security.password_encoder');
            $oldPassword = $form['oldPassword']->getData();
            if(!$encoder->isPasswordValid($user, $oldPassword, $user->getSalt())) {
                 $form->get('oldPassword')->addError(new FormError($this->get('translator')->trans('validator.old_password_incorrect', array(), 'fbeen_user')));
            }
            if ($form->isValid()) {
                $manager = $this->container->get('fbeen.user.user_manager');
                $manager->updateUser($user);

                $this->addFlash(
                     'fbeen.user.profile',
                     $this->get('translator')->trans('password.flash.has_changed', array(), 'fbeen_user')
                 );

                return $this->redirectToRoute('fbeen_user_profile_show');
            }
        }
        
        return array(
            'user' => $user,
            'form' => $form->createView(),
        );
    }
    
    /**
     * Step 1 of the password reset procedure: Ask the user to give his email.
     * 
     * @Template()
     */
    public function reset1Action(Request $request)
    {
        $user = $this->getUser();
        
        $form = $this->createFormBuilder()
            ->add('email', 'email', array(
                'label' => 'reset1.form.email',
                'constraints' => array(
                    new NotBlank(),
                    new Email()
                ),
                'translation_domain' => 'fbeen_user'
            ))
            ->getForm();
        
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $email = $form->getData()['email'];
            
            $manager = $this->container->get('fbeen.user.user_manager');
            $user = $manager->findUserByEmail($email);
            
            if($user) {
                if(NULL === $user->getTokenRequested() || $user->getTokenRequested()->diff(new \DateTime())->d > 0)
                {
                    $user->setConfirmationToken(sha1($user->getUsername() . 'fbeen' . date('Ymd')));
                    $user->setTokenRequested(new \DateTime());

                    $manager->updateUser($user);

                    $this->sendResetPasswordConfirmationEmail($user);

                    return $this->redirect($this->generateUrl('fbeen_user_password_reset2'));
                }
                
                $form->addError(new FormError($this->get('translator')->trans('validator.reset_already_requested', array(), 'fbeen_user')));
            } else {
                $form->get('email')->addError(new FormError($this->get('translator')->trans('validator.email_unknown', array(), 'fbeen_user')));
            }
        }
        
        return array(
            'form' => $form->createView(),
        );
    }
    
    /**
     * Step 2 of the password reset procedure: Give the user a message that he will receive an email.
     * 
     * @Template()
     */
    public function reset2Action(Request $request)
    {
    }
    
    /**
     * Step 3 of the password reset procedure: Let the user change his password.
     * 
     * @Template()
     */
    public function reset3Action(Request $request, $token)
    {
        $manager = $this->container->get('fbeen.user.user_manager');
        $user = $manager->findUserByConfirmationToken($token);

        if(!$user || $user->getConfirmationToken() !=  $token) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        }
        
        $formType = $this->container->getParameter('fbeen_user.form_types.change_password');
        if(Kernel::VERSION_ID < 30000) {
            $formType = new $formType;
        }
        
        $form = $this->createForm($formType, $user, array(
            'ask_old_password' => FALSE
        ));

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $encoder = $this->container->get('security.password_encoder');
            
            $user->setPassword($encoder->encodePassword($user, $user->getPlainPassword()));
            $user->setConfirmationToken(NULL);
            $user->setTokenRequested(NULL);

            $manager->updateUser($user);
            
            return $this->redirect($this->generateUrl('fbeen_user_password_reset4'));
        }
        
        return array(
            'form' => $form->createView(),
        );
    }
    
    /**
     * Step 4 of the password reset procedure: Give the user a confirmation that his password has changed.
     * 
     * @Template()
     */
    public function reset4Action()
    {
    }
    
    private function sendResetPasswordConfirmationEmail(UserInterface $user)
    {
        $confirmationUrl = $this->generateUrl('fbeen_user_password_reset3', array('token' => $user->getConfirmationToken()), UrlGeneratorInterface::ABSOLUTE_URL);
        
        $this->get('fbeen_mailer')
           ->setTo([$user->getEmail() => $user->getUsername()])
           ->setSubject($this->get('translator')->trans('email.reset_your_password_title', array(), 'fbeen_user'))
           ->setTemplate($this->getParameter('fbeen_user.emails_to_users.reset_your_password.template'))
           ->setData(array(
               'user' => $user,
               'confirmation_url' => $confirmationUrl
            ))
           ->sendMail()
       ;    
    }

}
