<?php


namespace PetFoodDB\Command\Tools;


use PetFoodDB\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends TableCommand
{

    protected function configure()
    {
        $this
            ->setDescription("Display random info")
            ->setName('info')
            ->addArgument('sub-command', InputArgument::REQUIRED, 'Command. Options include one of [listBrands|listBlog]');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $command = $input->getArgument('sub-command');
        switch ($command) {
            case 'listBrands': return $this->listBrands();
            case 'listBlog': return $this->listBlogs();
            default:
                $output->writeln("<error>$command</error><info> is an invalid command; exiting</info>");
        }

    }

    protected function listBrands() {

        /* @var \PetFoodDB\Service\CatFoodService $service */
        $service = $this->container->get('catfood');
        $brands = $service->getBrands();
        $brandLabels = array_map(function ($b) { return $b['name']; }, $brands);

        $export = var_export($brandLabels);
        $this->output->writeln("<info>$export</info>");


    }

    protected function listBlogs() {

        /* @var \PetFoodDB\Service\Blog $service */
        $service = $this->container->get('blog.service');
        chdir("./public");
        $posts = $service->getBlogPosts();
        $data = [];
        foreach ($posts as $post) {
            $data[] = [
                'url' => $service->getPostUrl($post->getYAML()['slug']),
                'title' =>  $post->getYAML()['title']
            ];
        }

        dump($data);

        $export = var_export($data);
        $this->output->writeln("<info>$export</info>");;


    }
}
