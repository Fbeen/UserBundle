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

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('credentials', $this->getRouterIdParameter().'/credentials');
    }

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
        $choices = $this->getAvailableRoles();
        
        $formMapper
            ->add('username', NULL, array('label' => 'Gebruiksersnaam'))
            ->add('email',  NULL, array('label' => 'Email'))
            ->add('enabled',  'checkbox', array('label' => 'Ingeschakeld'))
            ->add('send_password',  'checkbox', array(
                'label' => 'Stuur de gebruiker direct zijn inloggegevens',
                'data' => true,
                'mapped' => false,
                'required' => false
            ))
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
                    'clone' => array(
                        'template' => 'FbeenUserBundle:Admin:list__action_credentials.html.twig'
                    )
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
        
        $this->randomPassword = $container->get('fbeen.user.user_manager')->generateRandomPassword();
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

        if($this->getForm()->get('send_password')->getData())
        {
            $container->get('fbeen.user.user_manager')->sendNewAccountDetailsEmail($user, $this->randomPassword);

            $flashBag = $container->get('session')->getFlashBag();
            $flashBag->add('warning', $container->get('translator')->trans('flash.login_details_sent', array(), 'fbeen_user') . ' ' . $user->getEmail());
        }
    }
    
    public function preUpdate($user)
    {
        /*
         * If this user has any roles that are not listed in the available roles configuration then we dont want to lose those roles from the user entity.
         * On this manner we can provide "hidden" or "not editable roles" while we still can add or delete the editable roles
         */
        $em = $this->getModelManager()->getEntityManager($this->getClass());
        $original = $em->getUnitOfWork()->getOriginalEntityData($user);
        
        foreach($original['roles'] as $role)
        {
            if(!in_array($role, $this->getAvailableRoles()))
            {
                $user->addRole($role);
            }
        }
    }
    
    /**
     * This function creates an associative array with the roles that are defined in the configuration
     * 
     * @return array An associative array with available roles
     */
    private function getAvailableRoles()
    {
        $availableRoles = array();
        
        foreach($this->getConfigurationPool()->getContainer()->getParameter('fbeen_user.available_roles') as $role)
        {
            $availableRoles[$role['label']] = $role['role'];
        }
        
        return $availableRoles;
    }
}