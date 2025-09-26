<?php
namespace Larapress\Console;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class Please
{
    private string $basePath;
    private string $wpPath;

    public function __construct()
    {
        $this->basePath = '/var/www/html/framework';
        $this->wpPath   = $this->basePath . '/wp';

        require_once __DIR__.'/../../autoload.php';

        configLoader();
    }

    public function run($command){
        $commandMethod = 'run' . ucfirst($command) . 'Command';

        if (method_exists($this, $commandMethod)){
            $this->$commandMethod();
        }else{
            $this->error("Command not found: {$command}");
            exit(-100);
        }
    }

    public function runInstallCommand(): void
    {
        $config = $this->loadConfig();
        $slug = $config['slug'];

        $source = $this->basePath;
        $target = $this->wpPath . '/wp-content/plugins/' . $slug;


        if (!is_dir(BASE_PATH . '/wp/wp-content')) {
            $this->error('Please start docker using: docker-compose up -d');
            exit(-100);
        }

        if (is_link($target) || is_dir($target)) {
            $this->error("Plugin '{$slug}' already installed at {$target}");
            exit(-100);
        }

        $this->info("Installing plugin '{$slug}'...");

        if (!$this->createSymlink($source, $target)) {
            $this->error("Failed to create symlink {$target} → {$source}");
            exit(-100);
        }

        $this->publishStub(BASE_PATH, 'plugin_name.stub', $config['slug']);

        $this->info("Plugin '{$slug}' installed successfully!");
    }

    private function runCreateResourceCommand(): void
    {
        global $argv;

        $target = BASE_PATH . '/App/Resources';

        if (!is_dir($target)) {
            $this->error("Error 'Resources' directory not found {$target}");
            exit(-100);
        }

        if (!isset($argv[2])) {
            $this->error("Error: missing resource name: 'php please createResource <resource_name>");
        }
        $resourceName = $argv[2];

        $resourceName = ucfirst($resourceName);

        $variables = ['{{ResourceName}}' => $resourceName];
        $this->publishStub($target, 'resource.stub', $resourceName, $variables);

    }

    private function loadConfig(): array
    {
        if (!function_exists('plugin_dir_url')) {
            function plugin_dir_url(...$var) { return ''; }
        }

        return config('app');
    }

    private function info(string $msg): void {
        echo "\033[32m$msg\033[0m\n";
    }

    private function error(string $msg): void {
        echo "\033[31m$msg\033[0m\n";
    }

    private function createSymlink(string $source, string $target): bool
    {
        $this->info("Creating symlink inside Docker: {$target} -> {$source}");

        shell_exec("mkdir -p " . escapeshellarg(dirname($target)));

        $cmd = "ln -s " . escapeshellarg($source) . " " . escapeshellarg($target);
        shell_exec($cmd);

        $check = shell_exec("[ -L " . escapeshellarg($target) . " ] && echo '1' || echo '0'");
        return trim($check) === '1';
    }

    private function publishStub(string $target, string $filename, string $destFileName, array $variables = []): void
    {
        $config = $this->loadConfig();

        $stubFile = __DIR__ . '/../../stubs/'.$filename;
        $destFile = $target . '/' . $destFileName . '.php';

        $defaults = [
            '{{PluginName}}' => $config['name'] ?? $config['slug'],
            '{{PluginDescription}}' => $config['description'] ?? '',
            '{{PluginVersion}}' => $config['version'] ?? '1.0.0',
            '{{PluginAuthor}}' => $config['author'] ?? '',
            '{{PluginAuthorURI}}' => $config['author_uri'] ?? ''
        ];

        $replacements = array_merge($defaults, $variables);

        $content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            file_get_contents($stubFile)
        );

        file_put_contents($destFile, $content);
    }

    public function runInDocker(string $service, string $cmd): string
    {
        $container = trim(shell_exec("docker compose ps -q {$service}"));
        if (!$container) {
            $this->error("Service {$service} not running");
            exit(-100);
        }

        $output = shell_exec("docker exec -u www-data {$container} sh -c " . escapeshellarg($cmd));

        return $output ?? '';
    }
    public function runDeployCommand(): void
    {
        $config = $this->loadConfig();
        $slug = $config['slug'];
        $version = $config['version'];
        $zipFile = BASE_PATH . "/{$slug}_{$version}.zip";

        $this->info("Creating deployment zip for plugin '{$slug}'...");

        $tmpDir = BASE_PATH . "/{$slug}_deploy";
        if (is_dir($tmpDir)) {
            exec("rm -rf " . escapeshellarg($tmpDir));
        }
        mkdir($tmpDir, 0755, true);

        $paths = ['App', 'bootstrap', 'config', 'helpers', 'resources', 'vendor', "{$slug}.php"];
        foreach ($paths as $p) {
            $source = BASE_PATH . '/' . $p;
            $dest = $tmpDir . '/' . $p;

            if (is_dir($source)) {
                exec("cp -r " . escapeshellarg($source) . " " . escapeshellarg($dest));
            } elseif (is_file($source)) {
                $destDir = dirname($dest);
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                copy($source, $dest);
            }
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("Cannot create zip file {$zipFile}");
            exit(-100);
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($tmpDir) + 1);
            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
        exec("rm -rf " . escapeshellarg($tmpDir));

        $this->info("Deployment zip created: {$zipFile}");
    }
}