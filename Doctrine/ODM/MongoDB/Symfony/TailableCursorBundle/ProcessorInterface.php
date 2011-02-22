<?php

namespace Doctrine\ODM\MongoDB\Symfony\TailableCursorBundle;

interface ProcessorInterface
{
    function process($document);
}