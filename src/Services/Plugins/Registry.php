<?php

namespace Chriha\ProjectCLI\Services\Plugins;

use Chriha\ProjectCLI\Helpers;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Collection;

class Registry
{

    /** @var string */
    public static $url = 'http://localhost:8090/api/v1/plugins';

    public static function get(string $name) : Plugin
    {
        $client = new Client();

        try {
            $result = $client->request('GET', self::$url . '/' . urlencode($name));
        } catch (ConnectException $e) {
            Helpers::abort('Unable to connect to registry. Please try again later.');
            exit;
        } catch (\Exception $e) {
            Helpers::abort('Plugin could not be found');
            exit;
        }

        $info = json_decode($result->getBody()->getContents(), true)['data'] ?? null;

        if ( !$info) {
            Helpers::abort('Unable to get plugin info. Please try again later.');
        }

        return new Plugin($info);
    }

    public static function search(string $query) : ?Collection
    {
        $query  = '?' . http_build_query(['q' => $query]);
        $client = new Client();

        try {
            $result = $client->request('GET', self::$url . $query);
        } catch (ConnectException $e) {
            Helpers::abort('Unable to connect to registry. Please try again later.');
            exit;
        }

        $list = new Collection();
        $json = json_decode($result->getBody()->getContents(), true);

        foreach ($json['data'] as $item) {
            $list->put($item['id'], new Plugin($item));
        }

        return $list;
    }

}