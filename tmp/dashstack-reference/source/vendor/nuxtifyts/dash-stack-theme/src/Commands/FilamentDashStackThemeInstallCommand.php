<?php

namespace Nuxtifyts\DashStackTheme\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Nuxtifyts\DashStackTheme\DashStackThemeServiceProvider;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('filament-dash-stack-theme:install')]
class FilamentDashStackThemeInstallCommand extends Command
{
    /**
     * @var 0
     */
    public const SUCCESS = 0;

    /**
     * @var 1
     */
    public const FAILURE = 1;

    /**
     * @var string
     */
    protected $signature = 'filament-dash-stack-theme:install';

    /**
     * @var string
     */
    protected $description = 'Install the Dash Stack theme for Filament';

    /**
     * @return 1|0
     */
    public function handle(): int
    {
        $this->info('Installing the Dash Stack theme...');

        if (($npmVersionResult = Process::run('npm -v'))->failed()) {
            $this->error('NPM is required to install the Dash Stack theme.');

            return static::FAILURE;
        }

        $this->info("Using NPM version {$npmVersionResult->output()} to installed dependencies.");

        $npmInstallResult = Process::run('npm install tailwindcss @tailwindcss/forms @tailwindcss/typography postcss autoprefixer --save-dev');

        $this->info($npmInstallResult->output());

        $postcssConfigPath = base_path('postcss.config.js');

        if (! File::exists($postcssConfigPath)) {
            $this->info('No postcss.config.js file found. Creating one for you...');

            File::copy(__DIR__.'/../../stubs/postcss.config.js', $postcssConfigPath);

            $this->info('postcss.config.js file created.');
        }

        $this->info('Running NPM build...');

        $npmBuildResult = Process::run('npm install && npm run build');

        $this->info($npmBuildResult->output());

        $this->info('Publishing assets...');

        $this->call('vendor:publish', [
            '--tag' => DashStackThemeServiceProvider::PACKAGE_NAME.'-assets',
            '--force' => true,
        ]);

        return static::SUCCESS;
    }
}
