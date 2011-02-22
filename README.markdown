Doctrine MongoDB Tailable Cursor Bundle
=======================================

This is a simple command which allows you to tail a MongoDB cursor for a capped collection
in a daemon like Symfony2 console command that runs forever processing new documents as they
inserted to the capped collection.

The bundle consists of a single interface and a console command. The command is:

    $ ./app/console doctrine:mongodb:tail-cursor <document> <finder> <processor>

The arguments are:

* document - The name of the document class to tail.
* finder - The method used on the repository for the document to get the cursor.
* processor - The name of the service to use to process each document.

The processor must implement the simple ProcessorInterface:

    <?php

    namespace Doctrine\ODM\MongoDB\Symfony\TailableCursorBundle;

    interface ProcessorInterface
    {
        function process($document);
    }

When you implement your own processor, register it as a service name and then you can run the command like
the following:

    $ ./app/console doctrine:mongodb:tail-cursor MainBundle:User findNewUsers new_user.processor