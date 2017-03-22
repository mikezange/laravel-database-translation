<?php

namespace MikeZange\LaravelDatabaseTranslation\Commands;

use Illuminate\Console\Command;
use MikeZange\LaravelDatabaseTranslation\Repositories\TranslationRepository;

/**
 * Class RemoveTranslationsForLocale.
 */
class RemoveTranslationsForLocale extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trans-db:remove {locale}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes the translations from the DB for a given locale';

    /**
     * Translation repository.
     *
     * @var TranslationRepository
     */
    protected $translationRepository;

    /**
     * Locale.
     *
     * @var string
     */
    protected $locale;

    /**
     * Create a new command instance.
     *
     * @param TranslationRepository $translationRepository
     */
    public function __construct(TranslationRepository $translationRepository)
    {
        parent::__construct();
        $this->translationRepository = $translationRepository;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->locale = $this->argument('locale');

        $lines = $this->translationRepository->all();

        $this->processLines($lines);

        return $this->info('Complete');
    }

    /**
     * Process the language lines to remove the specified locale.
     *
     * @param $lines
     */
    protected function processLines($lines)
    {
        foreach ($lines as $line) {
            $line->forgetTranslation('values', $this->locale);
            $this->translationRepository->save($line);
        }
    }
}
