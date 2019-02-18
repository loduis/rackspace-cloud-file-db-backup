<?php

namespace Rackspace\CloudFiles\Backup;

use ReflectionClass;
use RuntimeException;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware as GuzzleMiddleware;
use OpenStack\Common\Service\Builder;
use OpenStack\Common\Transport\Utils;
use Rackspace\CloudFiles\Backup\Identity\v2\Service;

class Rackspace
{
    const US_IDENTITY_ENDPOINT = 'https://identity.api.rackspacecloud.com/v2.0/';
    const UK_IDENTITY_ENDPOINT = 'https://lon.identity.api.rackspacecloud.com/v2.0/';

    private const END_POINT = [
        'US' => self::US_IDENTITY_ENDPOINT,
        'UK' => self::UK_IDENTITY_ENDPOINT
    ];

    private $builder;

    public function __construct(array $options = [], Builder $builder = null)
    {
        $url = $this->resolveEndPoint($options['identity_endpoint']);
        $params = [
            'authUrl' => $url,
            'region' => $options['region'],
            'username' => $options['user_name'],
            'apiKey' => $options['api_key']
        ];
        if (!isset($params['identityService'])) {
            $params['identityService'] = $this->getDefaultIdentityService($params);
        }
        $this->builder = $builder ?: new Builder($params, 'OpenStack');
    }

    /**
     * Creates a new Object Store v1 service.
     *
     * @param array $options options that will be used in configuring the service
     *
     * @return \OpenStack\ObjectStore\v1\Service
     */
    public function objectStoreV1(array $options = []): \OpenStack\ObjectStore\v1\Service
    {
        $defaults = ['catalogName' => 'cloudFiles', 'catalogType' => 'object-store'];
        return $this->builder->createService('ObjectStore\\v1', array_merge($defaults, $options));
    }

    /**
     * @param array $options
     *
     * @return Service
     */
    private function getDefaultIdentityService(array $options): Service
    {
        if (!isset($options['authUrl'])) {
            throw new \InvalidArgumentException("'authUrl' is a required option");
        }
        $stack = HandlerStack::create();
        if (!empty($options['debugLog'])
            && !empty($options['logger'])
            && !empty($options['messageFormatter'])
        ) {
            $stack->push(GuzzleMiddleware::log($options['logger'], $options['messageFormatter']));
        }
        $clientOptions = [
            'base_uri' => Utils::normalizeUrl($options['authUrl']),
            'handler'  => $stack,
        ];
        if (isset($options['requestOptions'])) {
            $clientOptions = array_merge($options['requestOptions'], $clientOptions);
        }
        return Service::factory(new Client($clientOptions));
    }

    private function resolveEndPoint($endPoint)
    {
        if ($value = (static::END_POINT[$endPoint] ?? false)) {
            return $value;
        }

        throw new RuntimeException('Not can\'t be resolve endpoint: ' . $endPoint);
    }
}
