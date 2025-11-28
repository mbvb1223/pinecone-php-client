<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Mbvb1223\Pinecone\Pinecone;

function namespaceOperations()
{
    $pinecone = new Pinecone();

    // Get index
    $index = $pinecone->index('test-integrations-php');

    // Get namespace
    $namespace = $index->namespace('example-namespace');

    // Vector operations
    $vectors = [
        ['id' => 'vec1', 'values' => array_fill(0, 1024, 0.5),],
        ['id' => 'vec2', 'values' => array_fill(0, 1024, 0.5),]
    ];

    $namespace->upsert($vectors);

    $result = $namespace->fetch(['vec1', 'vec2']);

    $namespace->delete(['vec1']);
    $namespace->update('vec2', array_fill(0, 1024, 0.8));
    $namespace->query(
        vector: array_fill(0, 1024, 0.8),
        topK: 1,
    );

    var_dump($result);
}

namespaceOperations();
