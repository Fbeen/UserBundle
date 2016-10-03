<?php

namespace Fbeen\UserBundle\Command;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
class PromoteUserCommand extends RoleCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('fbeen:user:promote')
            ->setDescription('Promotes a user by adding a role')
            ->setHelp(<<<EOT
The <info>fbeen:user:promote</info> command promotes a user by adding a role

  <info>php %command.full_name% frank ROLE_CUSTOM</info>
  <info>php %command.full_name% --admin frank</info>
EOT
            );
    }

    protected function executeRoleCommand(OutputInterface $output, $username, $admin, $role)
    {
        if($this->promote($username, $role)) {
            $output->writeln(sprintf('Role "%s" has been added to user "%s".', $role, $username));
        } else {
            $output->writeln(sprintf('User "%s" did already have "%s" role.', $username, $role));
        }
    }
}
