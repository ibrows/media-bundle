<?php

namespace Ibrows\MediaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ibrows\MediaBundle\Model\MediaInterface;
use Ibrows\MediaBundle\Manager\MediaTypeManager;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Persistence\ObjectManager;

class MigrationCommand extends ContainerAwareCommand
{
    /**
     * Due to the implicit persistence feature of doctrine we need
     * to load and persist each entry on its own in order to not
     * have unwished side effects while we change the type of the
     * media.
     *
     * @var integer
     */
    const LIMIT = 1;

    /**
     * @var MediaTypeManager
     */
    protected $manager;
    /**
     * @var ObjectManager
     */
    protected $em;

    protected function configure()
    {
        $this
            ->setName('ibrows:media:migrate')
            ->setDescription(
'Migrate full class or single media type to an optionally given media type')
            ->addArgument(
                'class',
                InputArgument::REQUIRED,
'Which class you want to migrate'
            )
            ->addOption(
                'source',
                'src',
                InputOption::VALUE_OPTIONAL,
'which media type you want to migrate. If this option is given,
only the source type will be migrated, else all types in the table
will be migrated'
            )
            ->addOption(
                'target',
                'tgt',
                InputOption::VALUE_OPTIONAL,
'which media type you want to migrate to. If this option is given,
it will only be migrated to the target type if it does support the
original data, else the target type will be guessed again.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $class = $input->getArgument('class');
        $source = $input->getOption('source');
        $target = $input->getOption('target');
        $criteria = array();
        if ($source) {
            $criteria['type'] = $source;
        }

        $this->manager = $this->getContainer()->get('ibrows_media.type.manager');
        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass($class);
        $repo = $this->em->getRepository($class);

        $medias = array();
        $page = 0;
        do {
            $medias = $repo->findBy($criteria, array(), self::LIMIT, self::LIMIT*$page);
            foreach ($medias as $media) {
                $this->migrate($input, $output, $media, $target);
            }
            ++$page;
        } while (!empty($medias));
    }

    protected function migrate(InputInterface $input, OutputInterface $output, MediaInterface $media, $target = null)
    {
        $sourceType = $this->manager->getType($media->getType());
        $data = $media->getData();
        if ($target) {
            $type = $this->manager->getType($target);
        } else {
            $type = $this->getBestMatchingType($data);
        }

        if (!$type) {
            $output->writeln(
                sprintf('<error>can not find a matching type for value "%s"</error>',
                    (string) $data
                )
            );

            return;
        }

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_QUIET) {
            $output->writeln(
                sprintf('migrating media "%s" entry from <info>%s</info> to <info>%s</info>',
                    (string) $media,
                    $media->getType(),
                    $type->getName()
                )
            );
        }

        if ($data instanceof File) {
            $extra = $media->getExtra();
            $originalName = $extra['originalFilename'];
            $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.$originalName;
            copy($data->getPathname(), $path);
            $data = new File($path);
        }

        if ($type->supports($data)) {
            $sourceType->postRemove($media);
            $media->setExtra(null);
            $media->setHtml(null);
            $media->setUrl(null);
            $media->setData($data);
            $media->setType($type->getName());
            $type->prePersist($media);
            $this->em->persist($media);
            $this->em->flush();
            $this->em->clear();
            $type->postPersist($media);
        } else {
            $output->writeln(
                sprintf('   <error>media type "%s" does not support data "%s"</error>',
                    $type->getName(),
                    (string) $data
                )
            );
        }
    }

    /**
     * @param  mixed                                       $value
     * @return \Ibrows\MediaBundle\Type\MediaTypeInterface
     */
    protected function getBestMatchingType($value)
    {
        $all = $this->manager->getAllMediaTypes();
        $types = $this->manager->getSupportingTypes($value, $all);
        if (count($types)>1) {
            return $this->manager->guessBestSupportingType($value, $types);
        }

        return reset($types);
    }
}
