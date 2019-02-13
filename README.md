VikingPayBundle
===============

Viking pay plugin adapter for JMSPaymentCoreBundle


1- Install
----------

Add plugin to your composer.json require:

    {
        "require": {
            "jihel/viking-pay-plugin": "dev-master",
        }
    }

or

    php composer.phar require jihel/viking-pay-plugin:dev-master

Add bundle to your AppKernel.php

    public function registerBundles()
    {
        $bundles = array(
            ...
            new Jihel\VikingPayBundle\JihelVikingPayBundle(),
        );
    }


2- Configure your config.yml
----------------------------

The default configuration file and explanations can be found [here](doc/config.md)


3- Usage
--------

You can change the mid by setting it on extendedData after form validation

    /** @var PaymentInstructionInterface $instruction */
    $instruction = $form->getData();
    $instruction->getExtendedData()->set('mid', 'default');

All transaction are build on server 2 server process, with first paiement as a registration with a token creation that allows for further transactions.
Refunds are managed with reverseDeposit.


4- Thanks
---------

Thanks to my cat to keep meowing me.
Thanks to me for giving my free time doing class for lazy developers.
You can access read CV [here](http://www.joseph-lemoine.fr)
