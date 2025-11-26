<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Mbvb1223\Pinecone\Pinecone;
use Mbvb1223\Pinecone\Errors\PineconeException;

function listIndexes()
{
    // Initialize Pinecone client
    $pinecone = new Pinecone();

    // List existing indexes
    echo "Listing indexes...\n";
    $indexes = $pinecone->listIndexes();
    foreach ($indexes as $index) {
        echo "-" . $index->name .  PHP_EOL;
    }
}

listIndexes();
