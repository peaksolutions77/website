<?php namespace App\Services;

use Chumper\Zipper\Zipper;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use SplFileInfo;
use Storage;

class TemplateRepository
{
    /**
     * @var string
     */
    private $templatesPath;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * TemplateRepository constructor.
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->templatesPath = public_path('builder/templates');
    }

    /**
     * Create a new template.
     *
     * @param array $params
     * @throws FileNotFoundException
     */
    public function create($params)
    {
        $name = isset($params['name']) ? $params['name'] : $params['display_name'];
        $this->update($name, $params);
    }

    /**
     * Update template matching specified name.
     *
     * @param string $name
     * @param array $params
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function update($name, $params)
    {
        $name = str_slug($name);
        $templatePath = "$this->templatesPath/$name";

        // extract template files
        if (isset($params['template'])) {
            $zipper = new Zipper;
            $zipper->make($params['template']->getRealPath())->extractTo($templatePath);
            $zipper->close();

            $indexFile = collect($this->filesystem->allFiles($templatePath))->first(function(SplFileInfo $file) {
                return !$file->isDir() && str_contains($file->getPathname(), 'index.html');
            });

            if ( ! $indexFile) {
                // make sure there is always an index.html file in template folder
                $this->filesystem->put("$templatePath/index.html", 'Could not find index.html file in the template, so this file was created automatically.');
            } else {
                // move template files to the root if they were nested inside .zip file
                $indexFolder = trim(str_replace($templatePath, '', $indexFile->getPath()), '/');
                if ($indexFolder) {
                    foreach ($this->filesystem->allFiles("$templatePath/$indexFolder") as $file) {
                        $newPath = str_replace($indexFolder, '', $file->getPathname());
                        $newPath = str_replace('//', '/', $newPath);
                        Storage::disk('root')->move(
                            str_replace(base_path('').'/', '', $file->getPathname()),
                            str_replace(base_path('').'/', '', $newPath)
                        );
                    }
                    $this->filesystem->deleteDirectory("$templatePath/$indexFolder");
                }
            }
        }

        //load config file if it exists
        $configPath = "$this->templatesPath/$name/config.json";
        $config = [];
        if ($this->filesystem->exists($configPath)) {
            $config = json_decode($this->filesystem->get($configPath), true);
        }

        //update config file
        foreach (array_except($params, ['template', 'thumbnail']) as $key => $value) {
            $config[$key] = $value === 'null' ? null : $value;
        }
        $this->filesystem->put($configPath, json_encode($config, JSON_PRETTY_PRINT));

        //update thumbnail
        if (isset($params['thumbnail'])) {
            $this->filesystem->put("$this->templatesPath/$name/thumbnail.png", file_get_contents($params['thumbnail']));
        }
    }

    /**
     * Delete specified templates.
     *
     * @param array $names
     */
    public function delete($names)
    {
        foreach ($names as $name) {
            $this->filesystem->deleteDirectory("$this->templatesPath/$name");
        }
    }
}
