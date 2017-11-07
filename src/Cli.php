<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250;

use GetOpt\ArgumentException;
use GetOpt\Command;
use GetOpt\GetOpt;
use GetOpt\Operand;
use ScriptFUSION\Porter\Provider\Steam\Resource\GetAppList;
use ScriptFUSION\Steam250\Database\DatabaseStitcherFactory;
use ScriptFUSION\Steam250\Decorate\DecoratorFactory;
use ScriptFUSION\Steam250\Import\Importer;
use ScriptFUSION\Steam250\Import\ImporterFactory;
use ScriptFUSION\Steam250\SiteGenerator\SiteGeneratorFactory;

final class Cli
{
    private $cli;

    public function __construct()
    {
        $this->cli = (new GetOpt([
            ['?', 'help', GetOpt::NO_ARGUMENT, 'Show this help.'],
        ]))->addCommands([
            (new Command('applist', [$this, 'importApps']))
                ->setShortDescription('Import full list of Steam apps in JSON format.')
            ,

            (new Command('reviews', [$this, 'importReviews']))
                ->setShortDescription('Import reviews for each Steam app into database.')
                ->addOptions([
                    [
                        'c',
                        'chunks',
                        GetOpt::REQUIRED_ARGUMENT,
                        'Number of chunks to split import into.',
                        Importer::DEFAULT_CHUNKS,
                    ],
                    [
                        'i',
                        'chunk-index',
                        GetOpt::REQUIRED_ARGUMENT,
                        'Chunk index of this job (1 to total chunks).',
                        Importer::DEFAULT_CHUNK_INDEX,
                    ],
                ])
                ->addOperand(new Operand('app-list-path', Operand::REQUIRED))
                    ->setShortDescription('Path to Steam app list in JSON format.')
            ,

            (new Command('stitch', [$this, 'stitch']))
                ->setShortDescription('Stitch database chunks back together.')
                ->addOperand(new Operand('path', Operand::REQUIRED))
                    ->setShortDescription('Path to database chunks.')
            ,

            (new Command('decorate', [$this, 'decorate']))
                ->setShortDescription('Decorate each Steam game with additional information in database.')
                ->addOperand(new Operand('path', Operand::REQUIRED))
                    ->setShortDescription('Path to database.')
            ,

            (new Command('generate', [$this, 'generate']))
                ->setShortDescription('Generate Steam Top 250 site content from database.')
                ->addOperand(new Operand('db-path', Operand::REQUIRED))
                    ->setShortDescription('Path to database.')
                ->addOperand(new Operand('output-path', Operand::REQUIRED))
                    ->setShortDescription('Path to database.')
            ,
        ]);
    }

    public function run(): void
    {
        try {
            $this->cli->process();
        } catch (ArgumentException $exception) {
            fwrite(STDERR, $exception->getMessage() . PHP_EOL);
            echo PHP_EOL . $this->cli->getHelpText();

            return;
        }

        if ($this->cli->getOption('help') || !$command = $this->cli->getCommand()) {
            echo $this->cli->getHelpText();

            return;
        }

        $command->handler()($command);
    }

    public function importApps(): void
    {
        echo file_get_contents(GetAppList::getUrl());
    }

    public function importReviews(Command $command): void
    {
        (new ImporterFactory)->create(
            $command->getOperand('app-list-path')->value(),
            +$command->getOption('chunks')->value(),
            +$command->getOption('chunk-index')->value()
        )->import();
    }

    public function stitch(Command $command): void
    {
        (new DatabaseStitcherFactory)->create($command->getOperand('path')->value())->stitch();
    }

    public function decorate(Command $command): void
    {
        (new DecoratorFactory)->create($command->getOperand('path')->value())->decorate();
    }

    public function generate(Command $command): void
    {
        (new SiteGeneratorFactory)->create(
            $command->getOperand('db-path')->value(),
            $command->getOperand('output-path')->value()
        )->generate();
    }
}
