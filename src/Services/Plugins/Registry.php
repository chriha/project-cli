<?php

namespace Chriha\ProjectCLI\Services\Plugins;

use Chriha\ProjectCLI\Exceptions\Plugins\NotFoundException;
use Chriha\ProjectCLI\Helpers;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Collection;

class Registry
{

    /** @var string */
    public static $url = 'https://raw.githubusercontent.com/ProjectCLI/registry/main/plugins.json';

    public static function get(string $name) : Plugin
    {
        $client = new Client();

        try {
            $result = $client->request('GET', self::$url);
        } catch (ClientException $e) {
            if ($e->getCode() === 404) {
                throw new NotFoundException();
            }
        } catch (ConnectException | RequestException $e) {
            Helpers::abort('Unable to connect to registry. Please try again later.', $e);
            exit;
        } catch (Exception $e) {
            Helpers::abort('Plugin could not be found');
            exit;
        }

        $info = json_decode($result->getBody()->getContents(), true)['data'] ?? null;

        if (! $info) {
            Helpers::abort(
                'Unable to get plugin info. Please try again later.',
                'Invalid response format.'
            );
        }

        foreach ($info as $item) {
            if (strtolower($item['name']) !== strtolower($name)) {
                continue;
            }

            $info = $item;
        }

        if (!$info) {
            Helpers::abort('Unable to find requested plugin.');
        }

        return new Plugin($info);
    }

    public static function search(string $query) : ?Collection
    {
        $client = new Client();

        try {
            $result = $client->request('GET', self::$url);
        } catch (ConnectException | RequestException $e) {
            Helpers::abort('Unable to connect to registry. Please try again later.', $e);
            exit;
        }

        $list = new Collection();
        $json = json_decode($result->getBody()->getContents(), true);

        foreach ($json['data'] as $item) {
            if (! str_contains($item['title'], $query) && ! str_contains($item['name'], $query)
                && ! str_contains($item['short_description'] ?? '', $query)) {
                continue;
            }

            $list->put($item['id'], new Plugin($item));
        }

        return $list;
    }

}
