<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Starme\HyperfEs;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                ConnectionResolverInterface::class => ConnectionResolver::class
            ],
            'commands' => [
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'elastice connection settings of this config file.',
                    'source' => __DIR__ . '/../config/es.php',
                    'destination' => BASE_PATH . '/config/autoload/es.php',
                ],
            ],
        ];
    }
}
