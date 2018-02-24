<?php

namespace Fbeen\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Security controller.
 */
class SecurityController extends Controller
{
    public function loginAction(Request $request)
    {
        $providers = $this->get('fbeen.user.provider_helper')->getProviders();
        
        $authenticationUtils = $this->get('security.authentication_utils');
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        
        try {
            $registerUrl = $this->generateUrl('fbeen_user_register');
        } catch (RouteNotFoundException $ex) {
            $registerUrl = NULL;
        }
        
        return $this->render('@FbeenUser/Security/login.html.twig', [
            // last username entered by the user
            'last_username' => $lastUsername,
            'error'         => $error,
            'providers' => $providers,
            'registerUrl' => $registerUrl
        ]);
    }
    
    public function loginCheckAction()
    {
        // this action will not be executed,
        // as the route is handled by the Security system
    }
}
