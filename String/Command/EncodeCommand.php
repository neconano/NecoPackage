<?php

namespace neco\String\Command;

use neco\Tools\ConfigBag;
use neco\String\Secret;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EncodeCommand extends Command
{
    public function configure()
    {
        $this->setName('string:encode');
        $this->setDescription('Encode');
        $this->addArgument('password');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $password = $input->getArgument('password');

        $secret = new Secret();

        $secret->setPrivateKeyPath(ConfigBag::getConfigByKey('secret.privateKeyPath'))
            ->setPrivateKeyPassword(ConfigBag::getConfigByKey('secret.privateKeyPasswork'))
            ->setPublicKeyPath(ConfigBag::getConfigByKey('secret.publicKeyPath'));

        $cryptTxt = $secret->publicKeyEncode($password);
        $output->writeln(base64_encode($cryptTxt));
        $passwd = $secret->privateKeyDecode($cryptTxt);
        $output->writeln(sprintf(
            'Private key decode <info>%s</info>',
            $passwd
        ));

        $output->writeln(sprintf(
            'Public key decode <info>%s</info>',
            $secret->publicKeyDecode($secret->privateKeyEncode($password))
        ));

        $secret->setPassword($passwd);

        $encodeStr = $secret->RC4Encode('text will be crypt');

        $output->writeln(base64_encode($encodeStr));

        $decodeStr = $secret->RC4Decode($encodeStr);

        $output->writeln($decodeStr);
    }
}
