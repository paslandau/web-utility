<?php

namespace paslandau\WebUtility\EncodingConversion;

interface EncodingConverterInterface {
    /**
     * @param array $headers
     * @param string $content
     * @return EncodingResult|null
     */
    public function convert(array $headers, $content);
    /**
     * @return string
     */
    public function getTargetEncoding();

} 