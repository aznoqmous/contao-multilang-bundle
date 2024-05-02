<?php

namespace Aznoqmous\ContaoMultilangBundle\Multilang;

use Aznoqmous\ContaoMultilangBundle\Config\MultilangConfig;
use Symfony\Component\HttpClient\HttpClient;

class Translator {

    public static function translate($string, $targetLangKey){
        $apiKey = MultilangConfig::get('deepl_api_key');
        $apiUrl = MultilangConfig::get('deepl_api_url');
        $client = HttpClient::create();
        $response = $client->request('POST', $apiUrl, [
            'headers' => [
                'Authorization' => "DeepL-Auth-Key $apiKey"
            ],
            'query' => [
                'text' => $string,
                'target_lang' => strtoupper($targetLangKey)
            ]
        ]);
        if ($response->getStatusCode() != 200) {
            new \ErrorException($response->getInfo('debug'));
            return null;
        }
        return json_decode($response->getContent())->translations[0]->text;
    }

}
