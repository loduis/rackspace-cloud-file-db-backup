<?php

namespace Rackspace\CloudFiles\Backup;

use Throwable;
use OpenStack\Common\Error\BadResponseError;
use OpenStack\ObjectStore\v1\Models\Container as ModelsContainer;
use OpenStack\ObjectStore\v1\Models\StorageObject;

class Container
{
    private ModelsContainer $store;

    private $maxFiles;

    public function __construct(Rackspace $client, $region, $name, $maxFiles = 30)
    {
        $this->store    = $client->objectStoreV1()->getContainer($name);
        $this->maxFiles = $maxFiles;
    }

    /**
     * Sube un objeto al contenedor
     *
     * @param  File   $file
     * @return bool
     */
    public function upload(File $file)
    {
        try {
            $this->store->createObject([
                'name' => $file->path(),
                'stream' => $file->stream()
            ]);
        } catch (Throwable $e) {
            throw $e;
            return false;
        }

        return true;
    }

    /**
     * Obtiene el contenido de un objeto en el contenedor
     *
     * @param  string $filename
     * @return string
     */
    public function download($filename)
    {
        return (string) $this->get($filename)->download();
    }

    /**
     * Obtiene un objeto en el contenedor
     *
     * @param  string $filename
     * @return \OpenCloud\ObjectStore\Resource\DataObject
     */
    public function get($filename = null): StorageObject
    {
        return $this->store->getObject($filename);
    }

    /**
     * @param  array $params
     * @return \OpenCloud\ObjectStore\Resource\DataObject[]
     */
    public function all(array $params = []): \Generator
    {
        return $this->store->listObjects($params);
    }

    public function maxFiles($max = null)
    {
        if ($max === null) {
            return $this->maxFiles;
        }

        return $this->maxFiles = (int) $max;
    }

    public function copy($from, $to)
    {
        try {
            $this->store->getObject($from)
                ->copy([
                    'destination' => $this->store->name . '/' . $to
                ]);
        } catch (Throwable $e) {
            throw $e;
            if ($this->isNotFound($e)) {
                return true;
            }
            throw $e;
        }

        return true;
    }

    public function delete($filename)
    {
        try {
            $this->store->getObject($filename)->delete();
        } catch (Throwable $e) {
            if ($this->isNotFound($e)) {
                return true;
            }
            throw $e;
        }

        return true;
    }

    protected function isNotFound($e)
    {
         return $e instanceof BadResponseError && $e->getResponse()->getStatusCode() == 404;
    }
}
