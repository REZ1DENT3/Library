<?php

include_once "../autoload.php";

use Deimos\Form;
use Deimos\Library;

class FormTest extends PHPUnit_Framework_TestCase
{

    public function testEmailStrNotEmpty()
    {
        mb_internal_encoding('utf-8');
        $emails = array(
            'почта@привет.мир' => true,
            'maksim.babichev95@gmail.com' => true,
            'ad@spa.com' => true,
            '(comment)localpart@example.com' => false,
            'localpart.ending.with.dot.@example.com' => false,
            '"this is v@lid!"@example.com' => false,
            '"much.more unusual"@example.com' => false,
            'postbox@com' => false,
            'admin@mailserver1' => false,
            '"()<>[]:,;@\\"\\\\!#$%&\'*+-/=?^_`{}| ~.a"@example.org' => false,
            '" "@example.org' => false,
            '0hot\'mail_check@hotmail.com' => true
        );

        foreach($emails as $email => $result) {

            $f = new Form(['email' => $email]);
            if ($result) {
                $this->assertTrue($f->is_valid());
            }
            else {
                $this->assertNotTrue($f->is_valid());
            }
        }

    }

    public function testPhoneStrNotEmpty()
    {
        $phones = array(
            "+ 7 (918) 382 - 2970",
            "9183822970",
            "8 (918)! 382 - 2970",
            "+ 7 (918) 382 - 2970",
            "+ 7 (918)@# 382 - 2970"
        );

        foreach ($phones as $phone) {
            $form = new Form(array('phone' => $phone));
            $this->assertTrue($form->is_valid());
        }
    }

    public function testPhoneStrEmpty()
    {

        $this->assertNotTrue(Library::is_phone(""));

    }

}