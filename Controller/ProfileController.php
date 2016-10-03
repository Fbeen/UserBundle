<?php

namespace Fbeen\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Profile controller.
 */
class ProfileController extends Controller
{
    /**
     * @Security("has_role('ROLE_USER')")
     * @Template()
     */
    public function showAction(Request $request)
    {
        return array(
            'user' => $this->getUser()
        );
    }
    
    /**
     * @Security("has_role('ROLE_USER')")
     * @Template()
     */
    public function editAction(Request $request)
    {
        $user = $this->getUser();
        
        $form = $this->createForm($this->container->getParameter('fbeen_user.form_types.profile'), $user, array(
            'data_class' => $this->container->getParameter('fbeen_user.user_entity'),
            'password_on_profile_edit' => $this->container->getParameter('fbeen_user.password_on_profile_edit')
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if($this->container->getParameter('fbeen_user.password_on_profile_edit')) {
                $encoder = $this->container->get('security.password_encoder');
                $password = $form['password']->getData();
                if(!$encoder->isPasswordValid($user, $password, $user->getSalt())) {
                     $form->get('password')->addError(new FormError($this->get('translator')->trans('validator.password_incorrect', array(), 'fbeen_user')));
                }
            }
            if($form->isValid())
            {
                $manager = $this->container->get('fbeen.user.user_manager');
                $manager->updateUser($user);

                $this->addFlash(
                     'fbeen.user.profile',
                     $this->get('translator')->trans('edit.flash.has_changed', array(), 'fbeen_user')
                 );

                return $this->redirectToRoute('fbeen_user_profile_show');
            }
        }

        return array(
            'user' => $user,
            'form' => $form->createView(),
        );
    }
}
