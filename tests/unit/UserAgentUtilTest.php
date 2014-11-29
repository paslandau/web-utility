<?php

use paslandau\WebUtility\UserAgentUtil;

class UserAgentUtilTest extends PHPUnit_Framework_TestCase {

    public function test_ShouldNotDeadlockOnNull(){
        $this->setExpectedException(InvalidArgumentException::class);
        UserAgentUtil::getRandom(0);
    }

    public function test_ShouldNotDeadlockOnNoOptions(){
        $this->setExpectedException(InvalidArgumentException::class);
        UserAgentUtil::getRandom(1,false,false,false);
    }

    public function test_ShouldReturnStringArray(){
        $tests = [
            "desktop" =>
                [
                    "dektop" => true,
                    "mobile" => false,
                    "feature" => false,
                ],
            "mobile" =>
                [
                    "dektop" => false,
                    "mobile" => true,
                    "feature" => false,
                ],
            "feature" =>
                [
                    "dektop" => false,
                    "mobile" => false,
                    "feature" => true,
                ],
        ];

        foreach($tests as $name => $data){
            $msg = [
                "Error in test $name"
            ];
            $msg = implode("\n",$msg);
            $num = 5000;
            $uas = UserAgentUtil::getRandom($num,true,true,true);
            $this->assertCount($num,$uas,$msg);
        }
    }
}
 