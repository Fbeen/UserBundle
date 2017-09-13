<?php

namespace Fbeen\UserBundle\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Fbeen\UserBundle\Validator\Constraints\PasswordConstraint;

class AdminController extends Controller
{
    public function credentialsAction(Request $request)
    {
        $object = $this->admin->getSubject();

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
        }
        
        $builder = $this->createFormBuilder();
        
        if($this->container->getParameter('fbeen_user.admin.create_password') === true)
        {
            $builder->add('plainPassword',  'text', array(
                'label' => 'Nieuw wachtwoord',
                'data' => $this->container->get('fbeen.user.user_manager')->generateRandomPassword(),
                'constraints' => array(
                    new PasswordConstraint(),
                )
            ));
        }
        
        $builder->add('send', SubmitType::class, array(
            'label' => 'Ja, versturen',
            'attr' => array(
                'class' => 'btn btn-warning'
            )
        ));
        
        $form = $builder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            if(isset( $form->getData()['plainPassword'])) {
                $newPassword = $form->getData()['plainPassword'];
            } else {
                $newPassword = $this->container->get('fbeen.user.user_manager')->generateRandomPassword();
            }
            
            $encoder = $this->container->get('security.password_encoder');
            $object->setPassword($encoder->encodePassword($object, $newPassword));
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($object);
            $em->flush();
            
            $this->container->get('fbeen.user.user_manager')->sendNewAccountDetailsEmail($object, $newPassword);

            
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