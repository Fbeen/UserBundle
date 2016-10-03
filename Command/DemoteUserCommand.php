<?php

namespace Fbeen\UserBundle\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Fbeen\UserBundle\Command\RoleCommand;

/**
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
class DemoteUserCommand extends RoleCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('fbeen:user:demote')
            ->setDescription('Demote a user by removing a role')
            ->setHelp(<<<EOT
The <info>fbeen:user:demote</info> command demotes a user by removing a role

  <info>php %command.full_name% frank ROLE_CUSTOM</info>
  <info>php %command.full_name% --admin frank</info>
EOT
            );
    }

    protected function executeRoleCommand(OutputInterface $output, $username, $admin, $role)
    {
        if($this->demote($username, $role)) {
            $output->writeln(sprintf('Role "%s" has been removed from user "%s".', $role, $username));
        } else {
            $output->writeln(sprintf('User "%s" didn\'t have "%s" role.', $username, $role));
        }
    }
}
