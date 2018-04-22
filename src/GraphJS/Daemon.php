<?php

/*
 * This file is part of the Pho package.
 *
 * (c) Emre Sokullu <emre@phonetworks.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphJS;

use Pho\Kernel\Kernel;
use PhoNetworksAutogenerated\{User, Site};
use CapMousse\ReactRestify\Http\Session;
use Pho\Plugins\Feeds\FeedPlugin;

/**
 * The async/event-driven REST server daemon
 * 
 * @author Emre Sokullu <emre@phonetworks.org>
 */
class Daemon extends \Pho\Server\Rest\Daemon
{
    public function __construct(string $configs = "")
    {
        $this->server = new Server();
        $this->server->setAccessControlAllowOrigin("*");
        $this->initKernel();
        $this->initControllers(__DIR__, false);
        Router::init2($this->server, $this->controllers, $this->kernel);
    }

    protected function initKernel(string $configs_file = ""): void
    {
        if(empty($configs_file)) {
            $configs_file = __DIR__ . '/../../';
        }
        $dotenv = new \Dotenv\Dotenv($configs_file);
        $dotenv->load();
        $configs = array(
            "services"=>array(
                "database" => ["type" => getenv('DATABASE_TYPE'), "uri" => getenv('DATABASE_URI')],
                "storage" => ["type" => getenv('STORAGE_TYPE'), "uri" =>  getenv("STORAGE_URI")],
                "index" => ["type" => getenv('INDEX_TYPE'), "uri" => getenv('INDEX_URI')]
            ),
            "default_objects" => array(
                    "graph" => \PhoNetworksAutogenerated\Site::class,
                    "founder" => \PhoNetworksAutogenerated\User::class,
                    "actor" => \PhoNetworksAutogenerated\User::class
            )
        );
        $this->kernel = new \Pho\Kernel\Kernel($configs);
        if(!empty(getenv("STREAM_KEY"))&&!empty(getenv("STRAM_SECRET"))) {
            $feedplugin = new FeedPlugin($this->kernel,  getenv('STREAM_KEY'),  getenv('STRAM_SECRET'));
            $this->kernel->registerPlugin($feedplugin);
        }
        $founder = new \PhoNetworksAutogenerated\User($this->kernel, $this->kernel->space(), "EmreSokullu", "esokullu@gmail.com", "123456");
        $this->kernel->boot($founder);
    }

}

