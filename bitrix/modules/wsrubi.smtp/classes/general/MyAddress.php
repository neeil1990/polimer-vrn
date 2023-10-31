<?php
namespace Zend\Mail;

use Zend\Mail\Address;
use Zend\Validator\EmailAddress as EmailAddressValidator;
use Zend\Validator\Hostname;

/**
 * Created by PhpStorm.
 * User: Sergey
 * Date: 08.11.2016
 * Time: 0:02
 */
class MyAddress extends Address
{
    /**
     * Constructor
     *
     * @param  string $email
     * @param  null|string $name
     * @param  bool $validation
     * @throws Exception\InvalidArgumentException
     * @return Address
     */
    public function __construct($email, $name = null, $validation = true)
    {
        if($validation) {
            $emailAddressValidator = new EmailAddressValidator(Hostname::ALLOW_LOCAL);
            if (!is_string($email) || empty($email)) {
                throw new Exception\InvalidArgumentException('Email must be a valid email address');
            }

            if (preg_match("/[\r\n]/", $email)) {
                throw new Exception\InvalidArgumentException('CRLF injection detected');
            }
            if (!$emailAddressValidator->isValid($email)) {
                $invalidMessages = $emailAddressValidator->getMessages();
                throw new Exception\InvalidArgumentException(array_shift($invalidMessages));
            }

            if (null !== $name) {
                if (!is_string($name)) {
                    throw new Exception\InvalidArgumentException('Name must be a string');
                }

                if (preg_match("/[\r\n]/", $name)) {
                    throw new Exception\InvalidArgumentException('CRLF injection detected');
                }

                $this->name = $name;
            }
        }
        $this->email = $email;
    }
    public function __toString()
    {
        return $this->email;
    }
}