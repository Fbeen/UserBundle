<?php

namespace Fbeen\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
class ActivateUserCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('fbeen:user:activate')
            ->setDescription('Activate a user')
            ->setDefinition(array(
                new InputArgument('username', InputArgument::REQUIRED, 'The username'),
            ))
            ->setHelp(<<<EOT
The <info>fbeen:user:activate</info> command activates a user (so they will be able to log in):

  <info>php %command.full_name% frank</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');

        $manager = $this->getContainer()->get('fbeen.user.user_manager');
        $user = $manager->findUserByUsername($username);
        
        if(NULL === $user)
        {
            throw new \Exception('Username does not exist');
        }

        $user->setEnabled(TRUE);
        $manager->updateUser($user);
        
        $output->writeln(sprintf('User "%s" has been activated.', $username));
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$this->getHelperSet()->has('question')) {
            $this->legacyInteract($input, $output);

            return;
        }

        if (!$input->getArgument('username')) {
            $question = new Question('Please choose a username:');
            $question->setValidator(function($username) {
                if (empty($username)) {
                    throw new \Exception('Username can not be empty');
                }

                return $username;
            });
            $answer = $this->getHelper('question')->ask($input, $output, $question);

            $input->setArgument('username', $answer);
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
    }
}
