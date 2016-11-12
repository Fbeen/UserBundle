<?php

namespace Fbeen\UserBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\CoreBundle\Validator\ErrorElement;

class UserAdmin extends Admin
{
    private $randomPassword;
    
    protected $datagridValues = array(
        '_page' => 1,
        '_sort_order' => 'ASC',
        '_sort_by' => 'username',
    );

    /*
     * Verberg mijn eigen account
     */
    public function createQuery($context = 'list')
    {
        $query = parent::createQuery($context);
        $query->andWhere(
            $query->expr()->eq($query->getRootAliases()[0] . '.hidden', ':hidden')
        );
        $query->setParameter('hidden', 0);
        
        return $query;
    }

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $choices = array();
        $roles = $this->getConfigurationPool()->getContainer()->getParameter('fbeen_user.available_roles');
        
        foreach($roles as $role)
        {
            $choices[$role['label']] = $role['role'];
        }
        
        $formMapper
            ->add('username', NULL, array('label' => 'Gebruiksersnaam'))
            ->add('email',  NULL, array('label' => 'Email'))
            ->add('enabled',  'checkbox', array('label' => 'Ingeschakeld'))
            ->add('roles',  'choice', array(
                'label' => 'Rechten',
                'choices' => $choices,
                'multiple' => true
            ))
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('username', NULL, array('label' => 'Gebruiksersnaam'))
            ->add('email', NULL, array('label' => 'Email'))
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('username', NULL, array('label' => 'Gebruiksersnaam'))
            ->addIdentifier('email',  NULL, array('label' => 'Email'))
            ->add('enabled',  NULL, array('label' => 'Ingeschakeld', 'editable' => true))
            ->add('_action', null, array(
                'actions' => array(
                    'show' => array(),
                    'edit' => array(),
                    'delete' => array(),
                )
            ))
        ;
    }
    
    // Fields to be shown on show action
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('username', NULL, array('label' => 'Gebruiksersnaam'))
            ->add('email',  NULL, array('label' => 'Email'))
            ->add('enabled',  NULL, array('label' => 'Ingeschakeld'))
            ->add('locked',  NULL, array('label' => 'Vergrendeld'))
            ->add('created',  NULL, array('label' => 'Created'))
            ->add('roles',  NULL, array('label' => 'Roles'))
        ;
    }
    
    protected function configureRoutes(RouteCollection $collection)
    {
        // to remove a single route
        //$collection->remove('create');
    }
    
    public function validate(ErrorElement $errorElement, $object)
    {
        // find object with the same uniqueField-value
        $other = $this->getConfigurationPool()->getContainer()->get('fbeen.user.user_manager')->findUserByEmail($object->getEmail());

        if (null !== $other && $other->getId() != $object->getId()) {
            $errorElement
                ->with('email')
                ->addViolation($this->getConfigurationPool()->getContainer()->get('translator')->trans('validator.email_exists', array(), 'fbeen_user'))
                ->end();
        }
    }

    /*
     * Nieuwe User? Genereer een password en stuur een email naar het mailadres
     */
    public function prePersist($user)
    {
        $container = $this->getConfigurationPool()->getContainer();

        $this->randomPassword = $this->random_string();
        $encoder = $container->get('security.password_encoder');
        $user->setPassword($encoder->encodePassword($user, $this->randomPassword));
        $user->setCreated(new \DateTime());
        $user->setLocked(false);
        
        }
    
    /*
     * Nieuwe User? Genereer een password en stuur een email naar het mailadres
     */
    public function postPersist($user)
    {
        $container = $this->getConfigurationPool()->getContainer();

        $this->sendNewAccountDetailsEmail($user, $this->randomPassword);
        
        $flashBag = $container->get('session')->getFlashBag();
        $flashBag->add('warning', $container->get('translator')->trans('flash.login_details_sent', array(), 'fbeen_user') . ' ' . $user->getEmail());
    }
    
    private function sendNewAccountDetailsEmail($user, $password)
    {
        $container = $this->getConfigurationPool()->getContainer();
        
        if($container->getParameter('fbeen_user.emails_to_users.new_account_details.enabled'))
        {
            /*
             * send a confirmation email to the user with his credentials
             */
             $container->get('fbeen_mailer')
                ->setTo($user->getEmail())
                ->setSubject($container->get('translator')->trans('email.new_account_details_user_title', array(), 'fbeen_user'))
                ->setTemplate($container->getParameter('fbeen_user.emails_to_users.new_account_details.template'))
                ->setData(array(
                    'user' => $user,
                    'password' => $password
                 ))
                ->sendMail()
            ;
        }
    }

    private function random_string($length = 8)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890!@#$%^&*/-+';
        $str = '';
        
        $alphamax = strlen($alphabet) - 1;

        for ($i = 0; $i < $length; ++$i) {
            $str .= $alphabet[random_int(0, $alphamax)];
        }
        
        return $str;
    }
}