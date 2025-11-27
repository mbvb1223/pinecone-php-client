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

    // New structure - much simpler!
    $index = $pinecone->index('test-integrations-php');
    $data = $index->describeIndexStats();

    var_dump($data);
}

function vectorOperations()
{
    $pinecone = new Pinecone();

    // Get index
    $index = $pinecone->index('test-integrations-php');
    
    // Get namespace
    $namespace = $index->namespace('example-namespace');
    
    // Vector operations
    $vectors = [
        ['id' => 'vec1', 'values' => [0.1, 0.2, 0.3]],
        ['id' => 'vec2', 'values' => [0.4, 0.5, 0.6]]
    ];
    
    $namespace->upsert($vectors);
    $results = $namespace->query([0.1, 0.2, 0.3], topK: 5);
    
    var_dump($results);
}

describeIndexStats();
