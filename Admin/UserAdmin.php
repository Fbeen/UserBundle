<?php

namespace Fbeen\UserBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Route\RouteCollection;

class UserAdmin extends Admin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('username', NULL, array('label' => 'Username'))
            ->add('email',  NULL, array('label' => 'Email'))
            ->add('enabled',  'checkbox', array('label' => 'Enabled'))
            ->add('locked',  NULL, array('label' => 'Locked'))
            ->add('created',  NULL, array('label' => 'Created'))
            ->add('roles', NULL, array('label' => 'Roles'))
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('username', NULL, array('label' => 'Username'))
            ->add('email', NULL, array('label' => 'Email'))
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('username', NULL, array('label' => 'Username'))
            ->addIdentifier('email',  NULL, array('label' => 'Email'))
            ->add('enabled',  NULL, array('label' => 'Enabled', 'editable' => true))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'show' => array(),
                )
            ))
        ;
    }
    
    // Fields to be shown on show action
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('username', NULL, array('label' => 'Username'))
            ->add('email',  NULL, array('label' => 'Email'))
            ->add('enabled',  NULL, array('label' => 'Enabled'))
            ->add('locked',  NULL, array('label' => 'Locked'))
            ->add('created',  NULL, array('label' => 'Created'))
            ->add('roles',  NULL, array('label' => 'Roles'))
        ;
    }
    
    protected function configureRoutes(RouteCollection $collection)
    {
        // to remove a single route
        //$collection->remove('create');
    }
}