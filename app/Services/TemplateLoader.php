<?php namespace App\Services;

use Carbon\Carbon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Storage;

class TemplateLoader
{
    /**
     * @var string
     */
    public $templatesPath;

    const DEFAULT_THUMBNAIL = 'client/assets/images/default_project_thumbnail.png';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->templatesPath = public_path('builder'.DIRECTORY_SEPARATOR.'templates');
        $this->filesystem = $filesystem;
    }

    /**
     * Load all available templates.
     *
     * @return Collection
     */
    public function loadAll()
    {
        $paths = $this->filesystem->directories($this->templatesPath);

        return collect($paths)->map(function($path) {
            $name = basename($path);

            $updatedAt = Carbon::createFromTimestamp($this->filesystem->lastModified($path))->toDateTimeString();

            return [
                'name' => $name,
                'updated_at' => $updatedAt,
                'config' => $this->getTemplateConfig(basename($path)),
                'thumbnail' => $this->getTemplateImagePath($name, self::DEFAULT_THUMBNAIL)
            ];
        });
    }

    /**
     * Load specified template from the disk.
     *
     * @param string $name
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function load($name)
    {
        $paths = $this->filesystem->files("$this->templatesPath/$name");

        $pages = collect($paths)->filter(function($path) {
            return str_contains($path, '.html');
        })->map(function($path) use($name) {
            return [
                'name' => basename($path, '.html'),
                'html' => $this->filesystem->get($path),
            ];
        })->values();

        return [
            'name' => $name,
            'config' => $this->getTemplateConfig($name),
            'thumbnail' => $this->getTemplateImagePath($name, self::DEFAULT_THUMBNAIL),
            'pages' => $pages,
            'css' => $this->getTemplateAsset("$this->templatesPath/$name/css/styles.css"),
            'js' => $this->getTemplateAsset("$this->templatesPath/$name/js/scripts.js"),
        ];
    }

    /**
     * Check if specified template exists.
     *
     * @param string $name
     * @return bool
     */
    public function exists($name)
    {
        return $this->filesystem->exists("$this->templatesPath/$name");
    }

    /**
     * Get template asset at specified path or default.
     *
     * @param string $path
     * @param string $default
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function getTemplateAsset($path, $default = '')
    {
        if ($this->filesystem->exists($path)) {
            return $this->filesystem->get($path);
        }

        return $default;
    }

    /**
     * Get template image path or default.
     *
     * @param string $name
     * @param string $default
     * @return string
     */
    private function getTemplateImagePath($name, $default = '')
    {
        $path = "$this->templatesPath/$name/thumbnail.png";

        if ($this->filesystem->exists($path)) {
            //only need relative path: 'builder/templates/name/thumbnail.png'
            return str_replace(public_path(DIRECTORY_SEPARATOR), '', $path);
        }

        return $default;
    }

    /**
     * Get template configuration.
     *
     * @param string $name
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function getTemplateConfig($name)
    {
        $path = "$this->templatesPath/$name/config.json";
        $config = [];

        if ($this->filesystem->exists($path)) {
            $config = json_decode($this->filesystem->get($path), true);
        }

        $config['framework'] = Arr::get($config, 'framework');

        return $config;
    }
}