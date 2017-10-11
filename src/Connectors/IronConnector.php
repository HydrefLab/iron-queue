<?php

namespace Collective\IronQueue\Connectors;

use Collective\IronQueue\IronQueue;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Http\Request;
use Illuminate\Queue\Connectors\ConnectorInterface;
use IronMQ\IronMQ;

class IronConnector implements ConnectorInterface
{
    /**
     * The encrypter instance.
     *
     * @var \Illuminate\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * The current request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Create a new Iron connector instance.
     *
     * @param \Illuminate\Contracts\Encryption\Encrypter $encrypter
     * @param \Illuminate\Http\Request                   $request
     */
    public function __construct(EncrypterContract $encrypter, Request $request)
    {
        $this->encrypter = $encrypter;
        $this->request = $request;
    }

    /**
     * Establish a queue connection.
     *
     * @param array $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config): Queue
    {
        $ironConfig = ['token' => $config['token'], 'project_id' => $config['project']];

        if (isset($config['host'])) {
            $ironConfig['host'] = $config['host'];
        }

        $iron = new IronMQ($ironConfig);

        if (isset($config['ssl_verifypeer'])) {
            $iron->ssl_verifypeer = $config['ssl_verifypeer'];
        }

        $queue = new IronQueue($iron, $this->request, $config['queue'], $config['encrypt'], $config['timeout']);
        $queue->setEncrypter($this->encrypter);

        return $queue;
    }
}
