<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\Application\Command;

use Slcorp\FileBundle\Application\Service\FileService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * @copyright  2025 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[AsCommand(
    name: 'slcorp:file:cleanup-draft',
    description: 'Удаляет старые файлы из draft area (по умолчанию старше 7 дней)'
)]
class CleanupOldDraftFilesCommand extends Command
{
    public function __construct(
        private readonly FileService $fileService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Удалять файлы старше указанного количества дней',
                7
            )
            ->addOption(
                'component',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Компонент для очистки (по умолчанию "user")',
                'user'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $days = (int)$input->getOption('days');
        $component = (string)$input->getOption('component');

        if ($days < 1) {
            $io->error('Количество дней должно быть больше 0');

            return Command::FAILURE;
        }

        $io->info(sprintf('Начинаем очистку draft файлов старше %d дней для компонента "%s"...', $days, $component));

        try {
            $deletedCount = $this->fileService->deleteOldDraftFiles($days, $component);

            if ($deletedCount > 0) {
                $io->success(sprintf('Удалено файлов: %d', $deletedCount));
            } else {
                $io->info('Файлы для удаления не найдены');
            }

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $io->error(sprintf('Ошибка при очистке файлов: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}
