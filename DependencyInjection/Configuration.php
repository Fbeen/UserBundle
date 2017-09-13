<?php

namespace Fbeen\UserBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('fbeen_user');

        $rootNode
            ->children()
                ->scalarNode('user_entity')
                    ->defaultValue('AppBundle\\Entity\\User')
                ->end()
                ->arrayNode('form_types')->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('change_password')->defaultValue('Fbeen\\UserBundle\\Form\\ChangePasswordType')->end()
                            ->scalarNode('profile')->defaultValue('Fbeen\\UserBundle\\Form\\ProfileType')->end()
                            ->scalarNode('register')->defaultValue('Fbeen\\UserBundle\\Form\\RegisterType')->end()
                        ->end()
                ->end()
                ->arrayNode('register')->addDefaultsIfNotSet()
                        ->children()
                            ->booleanNode('confirm_email')->defaultTrue()->end()
                            ->booleanNode('admin_approval')->defaultFalse()->end()
                        ->end()
                ->end()
                ->arrayNode('admin')->addDefaultsIfNotSet()
                        ->children()
                            ->booleanNode('create_password')->defaultFalse()->end()
                        ->end()
                ->end()
                ->arrayNode('password_constraints')->addDefaultsIfNotSet()
                        ->children()
                            ->integerNode('minlength')->min(4)->defaultValue(6)->end()
                            ->integerNode('nummeric')->min(0)->defaultValue(0)->end()
                            ->integerNode('letters')->min(0)->defaultValue(0)->end()
                            ->integerNode('special')->min(0)->defaultValue(0)->end()
                        ->end()
                ->end()
                ->arrayNode('available_roles')->defaultValue(array(array('role' => 'ROLE_USER', 'label' => 'Normal User')))
                    ->beforeNormalization()
                        ->ifNull()
                        ->then(function() { return array(array('role' => 'ROLE_USER', 'label' => 'Normal User')); })
                    ->end()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('role')->isRequired()->end()
                            ->scalarNode('label')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('providers')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('key')->isRequired()->end()
                            ->scalarNode('secret')->isRequired()->end()
                            ->scalarNode('scope')->end()
                            ->scalarNode('image')->defaultValue(null)->end()
                            ->scalarNode('title')->defaultValue(null)->end()
                        ->end()
                     ->end()
                ->end()
                ->arrayNode('emails_to_admins')->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('approve_new_account')->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('template')->defaultValue('FbeenUserBundle:Email:approve_new_account.html.twig')->end()
                            ->end()
                        ->end()
                        ->arrayNode('register_confirmation')->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')->defaultFalse()->end()
                                ->scalarNode('template')->defaultValue('FbeenUserBundle:Email:register_confirmation_admin.html.twig')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('emails_to_users')->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('confirm_your_mailaddress')->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('template')->defaultValue('FbeenUserBundle:Email:confirm_your_mailaddress.html.twig')->end()
                            ->end()
                        ->end()
                        ->arrayNode('reset_your_password')->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('template')->defaultValue('FbeenUserBundle:Email:reset_your_password.html.twig')->end()
                            ->end()
                        ->end()
                        ->arrayNode('register_confirmation')->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')->defaultTrue()->end()
                                ->scalarNode('template')->defaultValue('FbeenUserBundle:Email:register_confirmation_user.html.twig')->end()
                            ->end()
                        ->end()
                        ->arrayNode('new_account_details')->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')->defaultTrue()->end()
                                ->scalarNode('template')->defaultValue('FbeenUserBundle:Email:new_account_details_user.html.twig')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->booleanNode('password_on_profile_edit')->defaultFalse()->end()
                ->scalarNode('firewall')->defaultValue('main')->end()
            ->end()
        ;
        
        return $treeBuilder;
    }
        
}

