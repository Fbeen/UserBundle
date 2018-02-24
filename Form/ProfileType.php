<?php

namespace Fbeen\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;


class ProfileType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', null, array('label' => 'edit.form.username'))
            //->add('email', null, array('label' => 'edit.form.email'))
        ;
        
        if($options['password_on_profile_edit'])
        {
            $builder->add('password', PasswordType::class, array('label' => 'edit.form.password', 'mapped' => false));
        }
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'validation_groups' => array('profile'),
            'translation_domain' => 'fbeen_user',
            'password_on_profile_edit' => true
        ));
    }
    
    public function getName()
    {
        return 'fbeen_user_profile';
    }

}
