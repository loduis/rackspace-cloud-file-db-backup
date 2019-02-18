<?php

declare(strict_types=1);

namespace Rackspace\CloudFiles\Backup\Identity\v2;

use OpenStack\Common\Api\ApiInterface;

/**
 * Represents the OpenStack Identity v2 API.
 */
class Api implements ApiInterface
{
    public function postToken(): array
    {
        return [
            'method' => 'POST',
            'path'   => 'tokens',
            'params' => [
                'username' => [
                    'type'     => 'string',
                    'required' => true,
                    'path'     => 'auth.apiKeyCredentials',
                ],
                'apiKey' => [
                    'type'     => 'string',
                    'required' => true,
                    'path'     => 'auth.apiKeyCredentials',
                ],
                'tenantId' => [
                    'type' => 'string',
                    'path' => 'auth',
                ],
                'tenantName' => [
                    'type' => 'string',
                    'path' => 'auth',
                ],
            ],
        ];
    }
}
