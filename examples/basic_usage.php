<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Mbvb1223\Pinecone\Pinecone;
use Mbvb1223\Pinecone\Utils\Configuration;

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

function createForModel()
{
    $pinecone = new Pinecone();

    $index = $pinecone->createForModel('test-embed-index', [
        'cloud' => 'aws',
        'region' => 'us-east-1',
        'embed' => [
            'model' => 'multilingual-e5-large',
            'metric' => 'cosine',
            'field_map' => [
                'text' => 'content'
            ]
        ],
        'deletion_protection' => 'disabled',
        'tags' => [
            'environment' => 'test'
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

function deleteIndex()
{
    $pinecone = new Pinecone();

    $pinecone->deleteIndex('test-embed-index');

    var_dump(true);
}

function configureIndex()
{
    $pinecone = new Pinecone();

    $index = $pinecone->configureIndex('test-integrations-php', [
        'tags' => [
            'project' => 'pinecone-php-client',
            'owner' => 'mbvb1223',
            'environment' => 'development'
        ]
    ]);

    var_dump($index);
}

function describeIndexStats()
{
    $pinecone = new Pinecone();

    $index = $pinecone->describeIndex('test-integrations-php');

    $config = new Configuration();
    $dataPlan = new \Mbvb1223\Pinecone\Data\DataPlane($config, $index);
    $data = $dataPlan->describeIndexStats();

    var_dump($data);
}

describeIndexStats();
