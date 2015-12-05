<?php

use GuzzleHttp\Url;
use paslandau\ArrayUtility\ArrayUtil;
use paslandau\WebUtility\WebUtil;
use URL\Normalizer;

class WebUtilTest extends PHPUnit_Framework_TestCase {

    public function test_relativeToAbsoluteUrl(){

        $input = "http://www.example.com/path/to/file.html?foo=bar#top";

        // @see https://url.spec.whatwg.org/#url-parsing

        $tests = [
            "absoluteUrl" =>
            [
                "rel" => "http://www.example2.com/path2/to2",
                "expected" => "http://www.example2.com/path2/to2"
            ],
            "protocolRelative" =>
                [
                    "rel" => "//www.example2.com/path2/to2",
                    "expected" => "http://www.example2.com/path2/to2"
                ],
            "absolutePath" =>
                [
                    "rel" => "/path2/to2",
                    "expected" => "http://www.example.com/path2/to2"
                ],
            "relativePath" =>
                [
                    "rel" => "path2/to2",
                    "expected" => "http://www.example.com/path/to/path2/to2"
                ],
            "queryString" =>
                [
                    "rel" => "?foo2=bar2",
                    "expected" => "http://www.example.com/path/to/file.html?foo2=bar2"
                ],
            "fragment" =>
                [
                    "rel" => "#baz",
                    "expected" => "http://www.example.com/path/to/file.html?foo=bar#baz"
                ],
            "absoluteUrlPunycode" =>
                [
                    "rel" => "http://www.exämple2.com/path2/to2",
                    "expected" => "http://www.exämple2.com/path2/to2"
                ],
        ];

        foreach($tests as $name => $data){
            $res = WebUtil::relativeToAbsoluteUrl($data["rel"],$input);
            $this->assertEquals($data["expected"],$res,"Error in test $name");
        }
    }

    public function test_appendQuery(){

        $tests = [
            "base-path" =>
                [
                    "base" => "http://www.example.com/foo",
                    "query" => ["foo" => "bar"],
                    "expected" => "http://www.example.com/foo?foo=bar"
                ],
            "base-path-empty-query" =>
                [
                    "base" => "http://www.example.com/foo?",
                    "query" => ["foo" => "bar"],
                    "expected" => "http://www.example.com/foo?foo=bar"
                ],
            "base-path-query" =>
                [
                    "base" => "http://www.example.com/foo?bar=baz",
                    "query" => ["foo" => "bar"],
                    "expected" => "http://www.example.com/foo?bar=baz&foo=bar"
                ],
            // @todo the double "&&" is not that pretty but doesn't hurt either
            "base-path-query-trailing-ampersand" =>
                [
                    "base" => "http://www.example.com/foo?bar=baz&",
                    "query" => ["foo" => "bar"],
                    "expected" => "http://www.example.com/foo?bar=baz&&foo=bar"
                ],
            "base-path-multiple-query" =>
                [
                    "base" => "http://www.example.com/foo?bar=baz&bar2=baz2",
                    "query" => ["foo" => "bar"],
                    "expected" => "http://www.example.com/foo?bar=baz&bar2=baz2&foo=bar"
                ],
            "base-path-fragment" =>
                [
                    "base" => "http://www.example.com/foo#test",
                    "query" => ["foo" => "bar"],
                    "expected" => "http://www.example.com/foo?foo=bar#test"
                ],
            "base-path-query-fragment" =>
                [
                    "base" => "http://www.example.com/foo?bar=baz#test",
                    "query" => ["foo" => "bar"],
                    "expected" => "http://www.example.com/foo?bar=baz&foo=bar#test"
                ],
            "base-path-same-param-key" =>
                [
                    "base" => "http://www.example.com/foo?foo=baz",
                    "query" => ["foo" => "bar"],
                    "expected" => "http://www.example.com/foo?".http_build_query(["foo" => ["baz","bar"]])
                ],
            ## edge case?
//            "base-no-trailing-slash" =>
//                [
//                    "base" => "http://www.example.com",
//                    "query" => ["foo" => "bar"],
//                    "expected" => "http://www.example.com/?foo=bar"
//                ],
        ];

        foreach($tests as $name => $data){
            $res = WebUtil::appendQuery($data["base"],$data["query"]);
            $this->assertEquals($data["expected"],$res,"Error in test $name");
        }

    }

    public function test_appendQueryString(){

        $tests = [
            "no-query" =>
                [
                    "base" => "http://www.example.com/foo",
                    "query" => "",
                    "expected" => "http://www.example.com/foo"
                ],
            "no-query-question-mark" =>
                [
                    "base" => "http://www.example.com/foo",
                    "query" => "?",
                    "expected" => "http://www.example.com/foo"
                ],
            "no-query-ampersand" =>
                [
                    "base" => "http://www.example.com/foo",
                    "query" => "&",
                    "expected" => "http://www.example.com/foo"
                ],
            "query-key" =>
                [
                    "base" => "http://www.example.com/foo",
                    "query" => "foo",
                    "expected" => "http://www.example.com/foo?foo"
                ],
            "query-key-value" =>
                [
                    "base" => "http://www.example.com/foo",
                    "query" => "foo=bar",
                    "expected" => "http://www.example.com/foo?foo=bar"
                ],
            "query-question-mark-key-value" =>
                [
                    "base" => "http://www.example.com/foo",
                    "query" => "?foo=bar",
                    "expected" => "http://www.example.com/foo?foo=bar"
                ],
            "query-ampersand-key-value" =>
                [
                    "base" => "http://www.example.com/foo",
                    "query" => "&foo=bar",
                    "expected" => "http://www.example.com/foo?foo=bar"
                ],
        ];

        foreach($tests as $name => $data){
            $res = WebUtil::appendQueryString($data["base"],$data["query"]);
            $this->assertEquals($data["expected"],$res,"Error in test $name");
        }
    }

    public function test_removeQuery(){

        $tests = [
            "no-query" =>
                [
                    "base" => "http://www.example.com/foo",
                    "expected" => "http://www.example.com/foo"
                ],
            "no-query-question-mark" =>
                [
                    "base" => "http://www.example.com/foo?",
                    "expected" => "http://www.example.com/foo"
                ],
            "no-query-ampersand" =>
                [
                    "base" => "http://www.example.com/foo?bar&",
                    "expected" => "http://www.example.com/foo"
                ],
            "query-key" =>
                [
                    "base" => "http://www.example.com/foo?foo",
                    "expected" => "http://www.example.com/foo"
                ],
            "query-key-value" =>
                [
                    "base" => "http://www.example.com/foo?foo=bar",
                    "expected" => "http://www.example.com/foo"
                ],
            "query-ampersand-key-value" =>
                [
                    "base" => "http://www.example.com/foo?bar&foo=bar",
                    "expected" => "http://www.example.com/foo"
                ],
            "query-ampersand-key-value-keep-hash" =>
                [
                    "base" => "http://www.example.com/foo?bar&foo=bar#baz",
                    "hash" => true,
                    "expected" => "http://www.example.com/foo#baz"
                ],
            "query-ampersand-key-value-remove-hash" =>
                [
                    "base" => "http://www.example.com/foo?bar&foo=bar#baz",
                    "hash" => false,
                    "expected" => "http://www.example.com/foo"
                ],
        ];

        foreach($tests as $name => $data){
            if(array_key_exists("hash",$data)){
                $res = WebUtil::removeQuery($data["base"],$data["hash"]);
            }else {
                $res = WebUtil::removeQuery($data["base"]);
            }
            $this->assertEquals($data["expected"],$res,"Error in test $name");
        }
    }

    public function test_getQuery(){

        // only test unnamed key array parameters in query due to https://github.com/glenscott/url-normalizer/issues/17
        $tests = [
            "no-value" =>
                [
                    "base" => "http://example.com/?foo[]",
                    "expected" => ["foo" => [null]]

                ],
            "value" =>
                [
                    "base" => "http://example.com/?foo[]=bar",
                    "expected" => ["foo" => ["bar"]]

                ],
            "2-no-value" =>
                [
                    "base" => "http://example.com/?foo[]&foo[]",
                    "expected" => ["foo" => [null,null]]
                ],
            "2-value" =>
                [
                    "base" => "http://example.com/?foo[]=bar&foo[]=baz",
                    "expected" => ["foo" => ["bar","baz"]]
                ],
            "value-no-value" =>
                [
                    "base" => "http://example.com/?foo[]=bar&foo[]",
                    "expected" => ["foo" => ["bar",null]]
                ],
            "whitespace-key" =>
                [
                    "base" => "http://example.com/?f oo[]=bar&f oo[]",
                    "expected" => ["f oo" => ["bar",null]]
                ],
            "dot-key" =>
                [
                    "base" => "http://example.com/?f.oo[]=bar&f.oo[]",
                    "expected" => ["f.oo" => ["bar",null]]
                ],
            "dot-key-whitespace-key" =>
                [
                    "base" => "http://example.com/?f.oo[]=bar&f oo[]",
                    "expected" => ["f.oo" => ["bar"],"f oo" => [null]]
                ],
            "dot-name-key-whitespace-name-key" =>
                [
                    "base" => "http://example.com/?f.oo[foo]=bar&f oo[2]",
                    "expected" => ["f.oo" => ["foo" => "bar"],"f oo" => ["2" => null]]
                ],
            "nested-arrays" =>
                [
                    "base" => "http://example.com/?f.oo[foo.1][foo.2][foo.3]=foo-val&f.oo[foo.1][foo.2][foo.4]=foo-val2",
                    "expected" => ["f.oo" => ["foo.1" => ["foo.2" => ["foo.3" => "foo-val", "foo.4" => "foo-val2"]]]]
                ],
            //todo is this expected behavior? Treating foo as array although no [] syntax is used?
            "overriding-values" =>
                [
                    "base" => "http://example.com/?foo=bar&foo=baz",
                    "expected" => ["foo" => ["bar","baz"]]
                ],
            // Having and empty key with a null value on trailing ampersand is expected behaviour:
            // [...] the URI "http://example.com/?" cannot be assumed to be equivalent to "http://example.com/" [...]
            // @see https://tools.ietf.org/html/rfc3986#section-6.2.3
            "trailing-ampersand" =>
                [
                    "base" => "http://example.com/?foo=baz&",
                    "expected" => ["foo" => "baz","" => null]
                ],
            "trailing-question-mark" =>
                [
                    "base" => "http://example.com/?",
                    "expected" => []
                ],
            //todo is this expected behavior? Treating foo as array although no [] syntax is used?
            "nested-arrays-override" =>
                [
                    "base" => "http://example.com/?f.oo[foo.1][foo.2][foo.3]=foo-val&f.oo[foo.1][foo.2][foo.3]=foo-val2",
                    "expected" => ["f.oo" => ["foo.1" => ["foo.2" => ["foo.3" => ["foo-val","foo-val2"]]]]]
                ],
            "crazy-stuff" =>
                [
                    "base" => "http://example.com/?f.o  o[foo]=bar&f oo[2]&",
                    "expected" => ["f.o  o" => ["foo" => "bar"], "f oo" => [2=>null],"" => null]
                ],
        ];

        foreach($tests as $name => $data){
            $res = WebUtil::getQuery($data["base"]);
//            echo "\"expected\" => \"$res\"\n";
            $msg = [
                "Error in test $name:",
                "Input    : ".$data["base"],
                "Excpected: ".json_encode($data["expected"]),
                "Actual   : ".json_encode($res),
            ];
            $msg = implode("\n",$msg);
//            echo $msg."\n";
            $this->assertEquals($data["expected"],$res,$msg);
        }
    }

    public function test_getPathSegments(){

        $tests = [
            "no-path" =>
                [
                    "base" => "http://www.example.com",
                    "expected" => []
                ],
            "only-root" =>
                [
                    "base" => "http://www.example.com/",
                    "expected" => []
                ],
            "folder" =>
                [
                    "base" => "http://www.example.com/foo",
                    "expected" => ["foo"]
                ],
            "file" =>
                [
                    "base" => "http://www.example.com/foo.html",
                    "expected" => ["foo.html"]
                ],
            "file-param" =>
                [
                    "base" => "http://www.example.com/foo.html?foo",
                    "expected" => ["foo.html"]
                ],
            "file-param-fragment" =>
                [
                    "base" => "http://www.example.com/foo.html?foo#bar",
                    "expected" => ["foo.html"]
                ],
            "folder-file" =>
                [
                    "base" => "http://www.example.com/foo/bar.html",
                    "expected" => ["foo","bar.html"]
                ],
            "folder-dot-fragment-file" =>
                [
                    "base" => "http://www.example.com/foo/./bar.html",
                    "expected" => ["foo","bar.html"]
                ],
            "folders-dot-fragments-file" =>
                [
                    "base" => "http://www.example.com/foo/foo2/../foo3/../bar.html",
                    "expected" => ["foo","bar.html"]
                ],
            "folders-files-params-anchors" =>
                [
                    "base" => "http://www.example.com/foo/bar.html?foo#bar",
                    "expected" => ["foo","bar.html"]
                ],
            "multi-folders-files-params-anchors" =>
                [
                    "base" => "http://www.example.com/foo/foo2/foo3/bar.html?foo#bar",
                    "expected" => ["foo","foo2","foo3","bar.html"]
                ],
            "multi-folders-dot-segment-files-params-anchors" =>
                [
                    "base" => "http://www.example.com/foo/./foo2/foo3/../../bar.html?foo#bar",
                    "expected" => ["foo","bar.html"]
                ],
            "relative-files-params-anchors" =>
                [
                    "base" => "/foo/bar.html?foo#bar",
                    "expected" => ["foo","bar.html"]
                ],
        ];

        foreach($tests as $name => $data){
            $res = WebUtil::getPathSegments($data["base"]);
            $msg = [
                "Error in test $name:",
                "Input    : ".$data["base"],
                "Excpected: ".json_encode($data["expected"]),
                "Actual   : ".json_encode($res),
            ];
            $msg = implode("\n",$msg);
            $this->assertTrue(ArrayUtil::equals($res,$data["expected"],false,false,false),$msg);
        }
    }

    public function test_getSubdomains_getRegisterableDomain_getPublicSuffix(){

        $tests = [
            "no-subdomain" =>
                [
                    "base" => "http://example.com/",
                    "subdomain" => [],
                ],
            "www-subdomain" =>
                [
                    "base" => "http://www.example.com/",
                    "subdomain" => ["www"]
                ],
            "multi-subdomain" =>
                [
                    "base" => "http://www.foo.bar.example.com/",
                    "subdomain" => ["www","foo","bar"]
                ],
            "multi-subdomain-full-url" =>
                [
                    "base" => "http://www.foo.bar.example.com/foo/./foo2/foo3/../../bar.html?foo#bar",
                    "subdomain" => ["www","foo","bar"]
                ],
            "subdomain-co.uk" =>
                [
                    "base" => "http://www.example.co.uk/",
                    "subdomain" => ["www"]
                ]
        ];

        foreach($tests as $name => $data){
            $res = WebUtil::getSubdomains($data["base"]);
            $msg = [
                "Error in test $name:",
                "Input    : ".$data["base"],
                "Excpected: ".json_encode($data["subdomain"]),
                "Actual   : ".json_encode($res),
            ];
            $msg = implode("\n",$msg);
            $this->assertTrue(ArrayUtil::equals($res,$data["subdomain"],false,false,false),$msg);
        }
    }

    public function test_getRegisterableDomain_getPublicSuffix(){

        $tests = [
            "no-subdomain" =>
                [
                    "base" => "http://example.com/",
                    "domain" => "example.com",
                    "suffix" => "com"
                ],
            "www-subdomain" =>
                [
                    "base" => "http://www.example.com/",
                    "domain" => "example.com",
                    "suffix" => "com"
                ],
            "multi-subdomain" =>
                [
                    "base" => "http://www.foo.bar.example.com/",
                    "domain" => "example.com",
                    "suffix" => "com"
                ],
            "multi-subdomain-full-url" =>
                [
                    "base" => "http://www.foo.bar.example.com/foo/./foo2/foo3/../../bar.html?foo#bar",
                    "domain" => "example.com",
                    "suffix" => "com"
                ],
            "subdomain-co.uk" =>
                [
                    "base" => "http://www.example.co.uk/",
                    "domain" => "example.co.uk",
                    "suffix" => "co.uk"
                ]
        ];

        foreach($tests as $name => $data){
            $methods = [
                "domain" => [WebUtil::class,"getRegisterableDomain"],
                "suffix" => [WebUtil::class,"getPublicSuffix"],
            ];

            foreach($methods as $id => $method){
                $res = call_user_func($method,$data["base"]);
                $msg = [
                    "Error in method ".json_encode($method)." test $name:",
                    "Input    : ".$data["base"],
                    "Excpected: ".json_encode($data[$id]),
                    "Actual   : ".json_encode($res),
                ];
                $msg = implode("\n",$msg);
                $this->assertEquals($data[$id],$res,$msg);
            }
        }
    }

    public function test_splitHeaderWords_joinHeaderWords(){
        $tests = [
            "key" =>
                [
                    "base" => "foo",
                    "expected" => [["foo" => null]]
                ],
            "key-value" =>
                [
                    "base" => "foo=bar",
                    "expected" => [["foo" => "bar"]]
                ],
            "key-escaped-value" =>
                [
                    "base" => "foo=\"bar, baz\"",
                    "expected" => [["foo" => "bar, baz"]]
                ],
            "key-escaped-value-double-quotes" =>
                [
                    "base" => 'foo="\"bar\""',
                    "expected" => [["foo" => "\"bar\""]]
                ],
            "key-encoded-value" =>
                [
                    "base" => "foo='bar%20baz'",
                    "expected" => [["foo" => "'bar%20baz'"]],
                ],
            "escaped-key-value" =>
                [
                    "base" => "<foo,bar>; baz",
                    "expected" => [["<foo,bar>" => null, "baz" => null]]
                ],
            "escaped-key-escaped-value-key-encoded-value" =>
                [
                    "base" => "<foo,bar>,        bar=baz; foo=baz%20baz",
                    "expected" => [["<foo,bar>" => null],["bar" => "baz", "foo" => "baz%20baz"]],
                    "join" => "<foo,bar>, bar=baz; foo=baz%20baz" // removed whitespaces
                ],
            "rel-canonical" =>
                [
                    "base" => "<http://www.example.com/white-paper.html>; rel=\"canonical\"", // @see http://googlewebmastercentral.blogspot.de/2011/06/supporting-relcanonical-http-headers.html
                    "expected" => [["<http://www.example.com/white-paper.html>" => null, "rel" => "canonical"]],
                    "join" => "<http://www.example.com/white-paper.html>; rel=canonical", // removed unnecessary quotes
                ],
        ];

        foreach($tests as $name => $data){
            $methods = [
                "split" => [WebUtil::class,"splitHttpHeaderWords"],
                "join" => [WebUtil::class,"joinHttpHeaderWords"],
            ];

            $expected = null;
            $base = null;
            foreach($methods as $id => $method){
                if($id == "split"){
                    $expected = $data["expected"];
                    $base = $data["base"];
                }else{ // swap data for join
                    $expected = $data["base"];
                    if(array_key_exists("join",$data)){
                        $expected = $data["join"];
                    }
                    $base = $data["expected"];
                }
                $res = call_user_func($method,$base);
                $msg = [
                    "Error in method ".json_encode($method)." test $name:",
                    "Input    : ".json_encode($base),
                    "Excpected: ".json_encode($expected),
                    "Actual   : ".json_encode($res),
                ];
                $msg = implode("\n",$msg);
                if(is_array($expected)) {
                    $this->assertTrue(ArrayUtil::equals($res, $expected, false, false, false), $msg);
                }else{
                    $this->assertEquals($expected,$res, $msg);
                }
            }
        }
    }

    public function test_normalizeUrl(){

        $tests = [
            "no-value" =>
                [
                    "base" => "http://example.com/?foo[]",
                    "expected" => "http://example.com/?foo%5B%5D"

                ],
            "value" =>
                [
                    "base" => "http://example.com/?foo[]=bar",
                    "expected" => "http://example.com/?foo%5B%5D=bar"

                ],
            "2-no-value" =>
                [
                    "base" => "http://example.com/?foo[]&foo[]",
                    "expected" => "http://example.com/?foo%5B%5D&foo%5B%5D"
                ],
            "2-value" =>
                [
                    "base" => "http://example.com/?foo[]=bar&foo[]=baz",
                    "expected" => "http://example.com/?foo%5B%5D=bar&foo%5B%5D=baz"
                ],
            "value-no-value" =>
                [
                    "base" => "http://example.com/?foo[]=bar&foo[]",
                    "expected" => "http://example.com/?foo%5B%5D=bar&foo%5B%5D"
                ],
            "whitespace-key" =>
                [
                    "base" => "http://example.com/?f oo[]=bar&f oo[]",
                    "expected" => "http://example.com/?f%20oo%5B%5D=bar&f%20oo%5B%5D"
                ],
            "dot-key" =>
                [
                    "base" => "http://example.com/?f.oo[]=bar&f.oo[]",
                    "expected" => "http://example.com/?f.oo%5B%5D=bar&f.oo%5B%5D"
                ],
            "dot-key-whitespace-key" =>
                [
                    "base" => "http://example.com/?f.oo[]=bar&f oo[]",
                    "expected" => "http://example.com/?f.oo%5B%5D=bar&f%20oo%5B%5D"
                ],
            "dot-name-key-whitespace-name-key" =>
                [
                    "base" => "http://example.com/?f.oo[foo]=bar&f oo[2]",
                    "expected" => "http://example.com/?f.oo%5Bfoo%5D=bar&f%20oo%5B2%5D"
                ],
            "nested-arrays" =>
                [
                    "base" => "http://example.com/?f.oo[foo.1][foo.2][foo.3]=foo-val&f.oo[foo.1][foo.2][foo.4]=foo-val2",
                    "expected" => "http://example.com/?f.oo%5Bfoo.1%5D%5Bfoo.2%5D%5Bfoo.3%5D=foo-val&f.oo%5Bfoo.1%5D%5Bfoo.2%5D%5Bfoo.4%5D=foo-val2"
                ],
            "ordering-key-true" =>
                [
                    "base" => "http://example.com/?b_foo=a&a_foo=a",
                    "orderParams" => true,
                    "expected" => "http://example.com/?a_foo=a&b_foo=a"
                ],
            "ordering-values-true" =>
                [
                    "base" => "http://example.com/?foo=b&foo=a",
                    "orderParams" => true,
                    "expected" => "http://example.com/?foo=b&foo=a"
                ],
            "ordering-key-false" =>
                [
                    "base" => "http://example.com/?b_foo=a&a_foo=a",
                    "orderParams" => false,
                    "expected" => "http://example.com/?b_foo=a&a_foo=a"
                ],
            "ordering-values-false" =>
                [
                    "base" => "http://example.com/?foo=b&foo=a",
                    "orderParams" => false,
                    "expected" => "http://example.com/?foo=b&foo=a"
                ],
            "multiple-ampersand-remove" =>
                [
                    "base" => "http://example.com/?foo=baz&&&bar=baz",
                    "removeTrailing" => true,
                    "expected" => "http://example.com/?foo=baz&bar=baz"
                ],
            "trailing-ampersand-remove" =>
                [
                    "base" => "http://example.com/?foo=baz&",
                    "removeTrailing" => true,
                    "expected" => "http://example.com/?foo=baz"
                ],
            "trailing-question-mark-remove" =>
                [
                    "base" => "http://example.com/?",
                    "removeTrailing" => true,
                    "expected" => "http://example.com/"
                ],
            "trailing-question-mark" =>
                [
                    "base" => "http://example.com/?",
                    "removeTrailing" => false,
                    "expected" => "http://example.com/?"
                ],
            "nested-arrays-order" =>
                [
                    "base" => "http://example.com/?f.oo[foo.1][foo.2][foo.4]=foo-val2&f.oo[foo.1][foo.2][foo.3]=foo-val",
                    "orderParams" => true,
                    "expected" => "http://example.com/?f.oo%5Bfoo.1%5D%5Bfoo.2%5D%5Bfoo.3%5D=foo-val&f.oo%5Bfoo.1%5D%5Bfoo.2%5D%5Bfoo.4%5D=foo-val2"
                ],
            //This test fails --- the trailing ampersand should not be removed
            // @see https://github.com/glenscott/url-normalizer/issues/17#issuecomment-103825087
//            "trailing-ampersand" =>
//                [
//                    "base" => "http://example.com/?foo=baz&",
//                    "removeTrailing" => false,
//                    "expected" => "http://example.com/?foo=baz&"
//                ],
            //This test fails --- the trailing ampersand should not be removed
//            "multiple-ampersand" =>
//                [
//                    "base" => "http://example.com/?foo=baz&&&bar=baz",
//                    "removeTrailing" => false,
//                    "expected" => "http://example.com/?foo=baz&&&bar=baz"
//                ],
            // fails due to trailing ampersand being removed
//            "crazy-stuff" =>
//                [
//                    "base" => "http://example.com/?f.o  o[foo]=bar&f oo[2]&",
//                    "expected" => "http://example.com/?f.o%20%20o%5Bfoo%5D=bar&f%20oo%5B2%5D&"
//                ],
            //we cannot assume that values are overriden!
//            "overriding-values" =>
//                [
//                    "base" => "http://example.com/?foo=bar&foo=baz",
//                    "expected" => "http://example.com/?foo=baz"
//                ],
        ];

        foreach($tests as $name => $data){
            $removeTrailing = array_key_exists("removeTrailing",$data) ? $data["removeTrailing"] : null;
            $orderParams = array_key_exists("orderParams",$data) ? $data["orderParams"] : null;
            $res = WebUtil::normalizeUrl($data["base"],$removeTrailing,$orderParams);
//            echo "\"expected\" => \"$res\"\n";
            $msg = [
                "Error in test $name:",
                "Input    : ".$data["base"],
                "Excpected: ".json_encode($data["expected"]),
                "Actual   : ".json_encode($res),
            ];
            $msg = implode("\n",$msg);
//            echo $msg."\n";
            $this->assertEquals($data["expected"],$res,$msg);
        }
    }

    public function test_validateReverseIpLookup(){
        $tests = [
            "myseosolution-kasserver" =>
                [
                    "base" => "85.13.146.112",
                    "host" => "dd27808.kasserver.com",
                    "strict" => true,
                    "expected" => true
                ],
            "myseosolution" =>
                [
                    "base" => "85.13.133.212",
                    "host" => "myseosolution.de",
                    "strict" => false,
                    "expected" => false
                ],
            "googlebot" =>
                [
                    "base" => "66.249.64.5",
                    "host" => "googlebot.com",
                    "strict" => false,
                    "expected" => true
                ],
            "googlebot-strict" =>
                [
                    "base" => "66.249.64.5",
                    "host" => "googlebot.com",
                    "strict" => true,
                    "expected" => false
                ],
        ];

        foreach($tests as $name => $data){
            $res = WebUtil::validateReverseIpLookup($data["base"],$data["host"],$data["strict"]);
//            echo "\"expected\" => \"$res\"\n";
            $msg = [
                "Error in test $name:",
                "Input    : ".$data["base"],
                "Excpected: ".json_encode($data["expected"]),
                "Actual   : ".json_encode($res),
            ];
            $msg = implode("\n",$msg);
            $this->assertEquals($data["expected"],$res,$msg);
        }
    }
}
 