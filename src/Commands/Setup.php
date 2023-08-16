<?php

/**
 * This file is part of Blitz PHP framework - Inertia Adapter.
 *
 * (c) 2023 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Inertia\Commands;

use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Cli\Console\Console;
use BlitzPHP\Cli\Traits\ContentReplacer;
use BlitzPHP\Cli\Traits\GeneratorTrait;
use Psr\Log\LoggerInterface;

class Setup extends Command
{
    use ContentReplacer;
    use GeneratorTrait;

    /**
     * @var string
     */
    protected $group = 'Inertia';

    /**
     * @var string
     */
    protected $name = 'inertia:init';

    /**
     * @var string
     */
    protected $description = 'Créé un nouveau middleware Inertia et publie les configurations du ssr.';

    /**
     * @var array<string, string>
     */
    protected $options = [
        '--force' => 'Créer la classe même si le Middleware existe déjà.',
    ];

    public function __construct(Console $app, LoggerInterface $logger)
    {
        parent::__construct($app, $logger);
        $this->sourcePath = __DIR__ . '/../';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $this->publishConfig();

        $this->generateMiddleware();

        return 0;
    }

    private function publishConfig(): void
    {
        $file     = 'Config/inertia.php';
        $replaces = [];

        $this->copyAndReplace($file, $replaces);
    }

    private function generateMiddleware(): void
    {
        $this->component    = 'Middleware';
        $this->directory    = 'Middlewares';
        $this->template     = 'middleware.tpl.php';
        $this->templatePath = __DIR__ . '/Views';

        $this->classNameLang = 'CLI.generator.className.middleware';
        $this->setHasClassName(false);

        $params[0] = 'Inertia';

        $this->runGeneration($params);
    }
}
