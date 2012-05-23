<?php

namespace Doctrine\Bundle\MongoDBTailableCursorBundle;

interface ProcessorInterface
{
    function process($document);
}
