<?php

namespace Fbeen\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PasswordConstraintValidator extends ConstraintValidator
{
    private $container;
    
    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    public function validate($value, Constraint $constraint)
    {
        $translator = $this->container->get('translator');
        
        $constraints = $this->container->getParameter('fbeen_user.password_constraints');
        
        if(strlen($value) < $constraints['minlength']) {
            $this->context->buildViolation($translator->trans($constraint->password_too_short, array('%amount%' => $constraints['minlength']), 'fbeen_user'))
                ->addViolation();
        }
        
        if(preg_match_all('/[0-9]/', $value) < $constraints['nummeric']) {
            $this->context->buildViolation($translator->transChoice($constraint->digits_too_less, $constraints['nummeric'], array('%amount%' => $constraints['nummeric']), 'fbeen_user'))
                ->addViolation();
        }
        
        if(preg_match_all('/[a-zA-Z]/', $value) < $constraints['letters']) {
            $this->context->buildViolation($translator->transChoice($constraint->letters_too_less, $constraints['letters'], array('%amount%' => $constraints['letters']), 'fbeen_user'))
                ->addViolation();
        }
        
        if(preg_match_all('![^A-z0-9]!i', $value) < $constraints['special']) {
            $this->context->buildViolation($translator->transChoice($constraint->special_too_less, $constraints['special'], array('%amount%' => $constraints['special']), 'fbeen_user'))
                ->addViolation();
        }
    }
}