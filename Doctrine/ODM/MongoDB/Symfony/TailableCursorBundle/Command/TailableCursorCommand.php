<?php

namespace Doctrine\ODM\MongoDB\Symfony\TailableCursorBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ODM\MongoDB\Symfony\TailableCursorBundle\ProcessorInterface;
use ReflectionClass;
use Exception;
use InvalidArgumentException;

class TailableCursorCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('doctrine:mongodb:tail-cursor')
            ->setDescription('Tails a mongodb cursor and processes the documents that come through')
            ->addArgument('document', InputArgument::REQUIRED, 'The document we are going to tail the cursor for.')
            ->addArgument('finder', InputArgument::REQUIRED, 'The repository finder method which returns the cursor to tail.')
            ->addArgument('processor', InputArgument::REQUIRED, 'The service id to use to process the documents.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $repository = $dm->getRepository($input->getArgument('document'));
        $repositoryReflection = new ReflectionClass(get_class($repository));
        $documentReflection = $repository->getDocumentManager()->getMetadataFactory()->getMetadataFor($input->getArgument('document'))->getReflectionClass();
        $processor = $this->container->get($input->getArgument('processor'));

        if ( ! $processor instanceof ProcessorInterface) {
            throw new InvalidArgumentException('A tailable cursor processor must implement the ProcessorInterface.');
        }

        $processorReflection = new ReflectionClass(get_class($processor));
        $method = $input->getArgument('finder');

        $output->writeln(sprintf('Getting cursor for <info>%s</info> from <info>%s#%s</info>', $input->getArgument('document'), $repositoryReflection->getShortName(), $method));
        $output->writeln(null);

        $cursor = $repository->$method();

        if (!count($cursor)) {
            $output->writeln('<comment>Nothing found, waiting to try again</comment>');
        }

        while (true) {
            if ( ! $cursor->hasNext()) {
                // read all results so far, wait for more
                sleep(10);
            }
            $cursor->next();
            $document = $cursor->current();
            $id = $document->getId();

            $output->writeln(null);
            $output->writeln(sprintf('Processing <info>%s</info> with id of <info>%s</info>', $documentReflection->getShortName(), (string) $id));
            $output->writeln(null);
            $output->writeln(sprintf('   <info>%s</info><comment>#</comment><info>process</info>(<info>%s</info> <comment>$document</comment>)', $processorReflection->getShortName(), $documentReflection->getShortName()));
            $output->writeln(null);

            try {
                $processor->process($output, $document);
            } catch (Exception $e) {
                $output->writeln(sprintf('Error occurred while processing document: <error>%s</error>', $e->getMessage()));
                continue;
            }

            $dm->flush(array('safe' => true));
            $dm->clear();
        }
    }
}