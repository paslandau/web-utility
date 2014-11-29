<?php

use paslandau\ArrayUtility\ArrayUtil;
use paslandau\WebUtility\EncodingConversion\EncodingConverter;
use paslandau\WebUtility\WebUtil;

class EncodingConverterTest extends \PHPUnit_Framework_TestCase {

    public function test_convert(){
        $tests = [
            "html4","html5","text-xml","application-xml",
        ];

        $converters = [
            "header" => new EncodingConverter("utf-8",true,false),
            "header-meta" => new EncodingConverter("utf-8",true,true),
            "meta" => new EncodingConverter("utf-8",false,true),
        ];

        foreach($tests as $name) {
            $input = __DIR__ . "/resources/iso-8859-1-{$name}";
            $c = file_get_contents($input);
            $arr =  $this->splitHeadersAndContentFromHttpResponseString($c);
            $headers = $arr["headers"];
            $body = $arr["body"];
            /**
             * @var EncodingConverter $converter
             */
            foreach ($converters as $file => $converter) {
                $expected = __DIR__ . "/resources/utf8-{$name}-{$file}";
                $c = file_get_contents($expected);

                $arr =  $this->splitHeadersAndContentFromHttpResponseString($c);
                $expectedHeaders = $arr["headers"];
                $expectedBody = $arr["body"];

                $res = $converter->convert($headers, $body);
                $msg = [
                    "Error in test $name - $file:",
                    "Input headers    : " . json_encode($headers),
                    "Excpected headers: " . json_encode($expectedHeaders),
                    "Actual headers   : " . json_encode($res->getTargetHeaders()),
                ];
                $msg = implode("\n", $msg);
                $this->assertTrue(ArrayUtil::equals($res->getTargetHeaders(),$expectedHeaders,true,false),$msg);
                $msg = [
                    "Error in test $name - $file:",
                    "Input body       :\n" . $body . "\n",
                    "Excpected body   :\n" . $expectedBody . "\n",
                    "Actual body      :\n" . $res->getTargetContent() . "\n",
                ];
                $msg = implode("\n", $msg);
//                echo $msg;
                $this->assertEquals($expectedBody,$res->getTargetContent(),$msg);

            }
        }
    }

    /**
     * Gets the headers from a HTTP response as one dimensional associative array
     * with header names as keys. The header values will not be parsed but saved as-is!
     * @param $responseString
     * @return array
     */
    private function splitHeadersAndContentFromHttpResponseString($responseString){
        $lines = explode("\n",$responseString);
        $headers = [];
        $i = 0;
        foreach($lines as $i => $line){
            $line = trim($line);
            if($line === ""){
                break;
            }
            $parts = explode(":",$line);
            $key = array_shift($parts);
            if(count($parts) > 0){
                $headers[$key] = trim(implode(":",$parts));
            }else{
                $headers[$key] = "";
            }
        }
        $body = implode("\n", array_slice($lines,$i+1));
        return ["headers" => $headers, "body" => $body];
    }
}