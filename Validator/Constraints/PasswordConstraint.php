<?php

namespace Fbeen\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PasswordConstraint extends Constraint
{
    public $password_too_short = 'validator.password_too_short';
    public $digits_too_less = 'validator.digits_too_less';
    public $letters_too_less = 'validator.letters_too_less';
    public $special_too_less = 'validator.special_too_less';
}