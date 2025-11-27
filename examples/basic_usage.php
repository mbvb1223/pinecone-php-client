<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Mbvb1223\Pinecone\Pinecone;

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

function createIndex()
{
    $pinecone = new Pinecone();

    $index = $pinecone->createIndex('test-integrations-php', [
        'dimension' => 1024,
        'metric' => 'cosine',
        'spec' => [
            'serverless' => [
                'cloud' => 'aws',
                'region' => 'us-east-1'
            ]
        ]
    ]);

    var_dump($index);
}

function describeIndex()
{
    $pinecone = new Pinecone();

    $index = $pinecone->describeIndex('test-integrations-php');

    var_dump($index);
}

describeIndex();
