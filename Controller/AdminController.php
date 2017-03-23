<?php

namespace Fbeen\UserBundle\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class AdminController extends Controller
{
    public function credentialsAction(Request $request)
    {
        $object = $this->admin->getSubject();

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
        }
        
        // $defaultData = array('message' => 'Type your message here');
        
        $form = $this->createFormBuilder()
            ->add('send', SubmitType::class, array(
                'label' => 'Ja, versturen',
                'attr' => array(
                    'class' => 'btn btn-warning'
                )
            ))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $randomPassword = $this->container->get('fbeen.user.user_manager')->generateRandomPassword();
            $encoder = $this->container->get('security.password_encoder');
            $object->setPassword($encoder->encodePassword($object, $randomPassword));
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($object);
            $em->flush();
            
            $this->container->get('fbeen.user.user_manager')->sendNewAccountDetailsEmail($object, $randomPassword);

            
            $this->addFlash('sonata_flash_success', 'Inloggegevens zijn verstuurd naar ' . $object->getEmail());
            return new RedirectResponse($this->admin->generateUrl('list'));
        }
        
        return $this->render('FbeenUserBundle:Admin:credentials.html.twig', array(
            'form' => $form->createView(),
            'action' => 'credentials',
            'object' => $object
        ));
    }
}