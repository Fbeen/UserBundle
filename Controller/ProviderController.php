<?php

namespace Fbeen\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * ProviderController processes authentication through social networks like Facebook, Google and Twitter
 * 
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
class ProviderController extends Controller
{
    public function connectAction(Request $request, $provider)
    {
        return $this->redirect(
                $this->get('fbeen.user.provider_helper')->connect($provider)
        );
    }
    
    public function processAction()
    {
        $this->get('fbeen.user.provider_helper')->process();
    }
}
