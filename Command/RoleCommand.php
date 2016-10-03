<?php

namespace Fbeen\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
abstract class RoleCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputArgument('username', InputArgument::REQUIRED, 'The username'),
                new InputArgument('role', InputArgument::OPTIONAL, 'The role'),
                new InputOption('admin', null, InputOption::VALUE_NONE, 'Instead specifying role, use this to quickly add the administrator role'),
            ));
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        $role = $input->getArgument('role');
        $admin = (true === $input->getOption('admin'));

        if (null !== $role && $admin) {
            throw new \InvalidArgumentException('You can pass either the role or the --admin option (but not both simultaneously).');
        }

        if (null === $role && !$admin) {
            throw new \RuntimeException('Not enough arguments.');
        }
        
        if($role === NULL) {
            $role = 'ROLE_ADMIN';
        }

        $this->executeRoleCommand($output, $username, $admin, $role);
    }

    /**
     * @see Command
     *
     * @param OutputInterface $output
     * @param string          $username
     * @param boolean         $admin
     * @param string          $role
     *
     * @return void
     */
    abstract protected function executeRoleCommand(OutputInterface $output, $username, $admin, $role);

    /**
     * @see Command
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$this->getHelperSet()->has('question')) {
            $this->legacyInteract($input, $output);

            return;
        }

        $questions = array();

        if (!$input->getArgument('username')) {
            $question = new Question('Please choose a username:');
            $question->setValidator(function($username) {
                if (empty($username)) {
                    throw new \Exception('Username can not be empty');
                }

                return $username;
            });
            $questions['username'] = $question;
        }

        if ((true !== $input->getOption('admin')) && !$input->getArgument('role')) {
            $question = new Question('Please choose a role:');
            $question->setValidator(function($role) {
                if (empty($role)) {
                    throw new \Exception('Role can not be empty');
                }

                return $role;
            });
            $questions['role'] = $question;
        }

        foreach ($questions as $name => $question) {
            $answer = $this->getHelper('question')->ask($input, $output, $question);
            $input->setArgument($name, $answer);
        }
    }

    // BC for SF <2.5
    private function legacyInteract(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('username')) {
            $username = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose a username:',
                function($username) {
                    if (empty($username)) {
                        throw new \Exception('Username can not be empty');
                    }

                    return $username;
                }
            );
            $input->setArgument('username', $username);
        }
        if ((true !== $input->getOption('admin')) && !$input->getArgument('role')) {
            $role = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose a role:',
                function($role) {
                    if (empty($role)) {
                        throw new \Exception('Role can not be empty');
                    }

                    return $role;
                }
            );
            $input->setArgument('role', $role);
        }
    }
    
    protected function demote($username, $role)
    {
        if($role == 'ROLE_USER')
        {
            throw new \Exception('Role ROLE_USER cannot be removed!');
        }
        
        $manager = $this->getContainer()->get('fbeen.user.user_manager');
        $user = $manager->findUserByUsername($username);
        
        if(NULL === $user)
        {
            throw new \Exception('Username does not exist');
        }

        $roles = $user->getRoles();
        
        if(($key = array_search($role, $roles)) !== false)
        {
            unset($roles[$key]);
            $user->setRoles($roles);
            $manager->updateUser($user);    
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    protected function promote($username, $role)
    {
        $manager = $this->getContainer()->get('fbeen.user.user_manager');
        $user = $manager->findUserByUsername($username);
        
        if(NULL === $user)
        {
            throw new \Exception('Username does not exist');
        }

        if(($key = array_search($role, $user->getRoles())) === false)
        {
            $user->addRole($role);
            $manager->updateUser($user);    
            
            return TRUE;
        }
        
        return FALSE;
    }
}
