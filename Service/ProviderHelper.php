<?php

namespace Fbeen\UserBundle\Service;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserChecker;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use \Hybrid_Auth;
use \Hybrid_Endpoint;

/**
 * Layer between the application and the database
 *
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
class ProviderHelper
{
    private $container;
    private $userProfile;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->userProfile = NULL;
    }
    
    public function getProviders() 
    {
        $providers = array();
        
        foreach ($this->container->getParameter('fbeen_user.providers') as $name => $settings)
        {
            $providers[] = array(
                'name' => $name,
                'image' => $settings['image'],
                'title' => $settings['title']
            );
        }
        return $providers;
    }
    
    public function connect($provider)
    {
        $firewall = $this->container->getParameter('fbeen_user.firewall');

        $hybridauth = new Hybrid_Auth( $this->buildConfig() );

        $authProvider = $hybridauth->authenticate($provider);

        $this->userProfile = $authProvider->getUserProfile();
        
        $hybridauth->logoutAllProviders();

        if(!filter_var($this->userProfile->email, FILTER_VALIDATE_EMAIL)) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Provider "' . ucfirst($provider) . '" should return a valid mailaddress. Did you configure the "scope" option for this provider?');
        }

        /*
         * Try to find a user with the obtained mailaddress
         */
        $user = $this->container->get('fbeen.user.user_manager')->findUserByEmail($this->userProfile->email);
        $session = $this->container->get('request_stack')->getCurrentRequest()->getSession();

        if($user)
        {
            /* use the firewall's security checker to validate the user on things like locked, disabled and expired account status */
            $checker = $this->container->get('security.user_checker.' . $firewall);
             try {
                $checker->checkPreAuth($user);
                $checker->checkPostAuth($user);
            } catch (\Symfony\Component\Security\Core\Exception\AuthenticationException $exception) {
                $session->set(Security::AUTHENTICATION_ERROR, $exception);
                return $this->container->get('router')->generate('fbeen_user_security_login');
            }
 
            $this->login($user);
            
            /* try to find a redirect url in the session and if found return it */
            $redirectUrl = $session->get('_security.'.$firewall.'.target_path');
            if($redirectUrl)
                return $redirectUrl;
            
            /* otherwise return to the homepage */
            return $this->container->get('request_stack')->getCurrentRequest()->getUriForPath('/');
        }
        
        /* if no user is found with the given mailadress we will ask the user to register and we fill in the username and email fields */
        $session->set('register.data', array(
            'username' => $this->userProfile->displayName,
            'email' => $this->userProfile->email
        ));
        
        /* also give the user a message */
        $session->getFlashBag()->add('fbeen.user.register', $this->container->get('translator')->trans('register.flash.please_register', array(), 'fbeen_user'));
        
        /* and return the url to the registrate form */
        return $this->container->get('router')->generate('fbeen_user_register');;
    }
    
    /*
     * Login to the Symfony framework
     */
    public function login($user)
    {
        $firewall = $this->container->getParameter('fbeen_user.firewall');
        $token = new UsernamePasswordToken($user, null, $firewall, $user->getRoles());
        
        if(Kernel::VERSION_ID < 30000) {
            $this->container->get("security.context")->setToken($token); //now the user is logged in
        } else {
            $this->container->get("security.token_storage")->setToken($token); //now the user is logged in
        }

        //now dispatch the login event
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $event = new InteractiveLoginEvent($request, $token);
        $this->container->get("event_dispatcher")->dispatch("security.interactive_login", $event);
    }
    
    public function process()
    {
        Hybrid_Endpoint::process();
    }
    
    public function logout()
    {
        $hybridauth = new Hybrid_Auth( $this->buildConfig() );
        $hybridauth->logoutAllProviders();
    }
    
    public function getUserProfile()
    {
        if($this->userProfile && isset($this->userProfile->identifier))
        {
            return $this->userProfile;
        }
        
        return FALSE;
    }
    
    private function buildConfig()
    {
        $providers = array();
        
        foreach($this->container->getParameter('fbeen_user.providers') as $name => $settings)
        {
            $providers[$name] = array(
                'enabled' => true,
                'keys' => array(
                    'id' => $settings['key'],
                    'secret' => $settings['secret'],                    
                )
            );
            
            if(isset($settings['scope'])) {
                $providers[$name]['scope'] = $settings['scope'];
            }
        }
        
        return array(
            "base_url" => $this->container->get('router')->generate('fbeen_user_provider_process', array(), UrlGeneratorInterface::ABSOLUTE_URL), 
            "providers" => $providers,
            "debug_mode" => false,
            "debug_file" => "/var/www/projects/cmf/cmf/debug.log",
        );
    }
}
