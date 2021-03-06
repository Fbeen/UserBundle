<?php

namespace Fbeen\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class ChangePasswordType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if($options['ask_old_password'])
        {
            $builder->add('oldPassword', PasswordType::class, array(
                'label' => 'password.form.old_password',
                'mapped' => FALSE
            ));
        }
        
        $builder
            ->add('plainPassword', RepeatedType::class, array(
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'options' => array('attr' => array('class' => 'password-field')),
                'required' => true,
                'first_options'  => array('label' => 'password.form.new_password'),
                'second_options' => array('label' => 'password.form.confirm_new_password'),
            ))
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'validation_groups' => array('password'),
            'translation_domain' => 'fbeen_user',
            'ask_old_password' => TRUE
        ));
    }
    
        
    public function getName()
    {
        return 'fbeen_user_change_password';
    }

}
