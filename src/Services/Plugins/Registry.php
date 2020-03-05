<?php

namespace Chriha\ProjectCLI\Services\Plugins;

use Chriha\ProjectCLI\Helpers;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Collection;

class Registry
{

    /** @var string */
    public static $url = 'https://cli.lazu.io/api/v1/plugins';

    public static function get(string $name) : Plugin
    {
        $client = new Client();

        try {
            $result = $client->request('GET', self::$url . '/' . urlencode($name));
        } catch (ConnectException | RequestException $e) {
            Helpers::abort('Unable to connect to registry. Please try again later.', $e);
            exit;
        } catch (Exception $e) {
            Helpers::abort('Plugin could not be found');
            exit;
        }

        $info = json_decode($result->getBody()->getContents(), true)['data'] ?? null;

        if ( !$info) {
            Helpers::abort('Unable to get plugin info. Please try again later.', 'Invalid response format.');
        }

        return new Plugin($info);
    }

    public static function search(string $query) : ?Collection
    {
        $query  = '?' . http_build_query(['q' => $query]);
        $client = new Client();

        try {
            $result = $client->request('GET', self::$url . $query);
        } catch (ConnectException | RequestException $e) {
            Helpers::abort('Unable to connect to registry. Please try again later.', $e);
            exit;
        }

        $list = new Collection();
        $json = json_decode($result->getBody()->getContents(), true);

        foreach ($json['data'] as $item) {
            $list->put($item['id'], new Plugin($item));
        }

        return $list;
    }

    public static function incrementInstallations(Plugin $plugin) : bool
    {
        $client = new Client();

        try {
            $client->request('POST', $plugin->url_api . '/installation');
        } catch (ConnectException | RequestException $e) {
            Helpers::logger()->debug($e);

            return false;
        }

        return true;
    }

}
