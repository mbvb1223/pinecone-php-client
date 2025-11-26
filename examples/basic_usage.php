<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Mbvb1223\Pinecone\Pinecone;
use Mbvb1223\Pinecone\Errors\PineconeException;

try {
    // Initialize Pinecone client
    $pinecone = new Pinecone();

    // List existing indexes
    echo "Listing indexes...\n";
    $indexes = $pinecone->listIndexes();
    foreach ($indexes as $index) {
        echo "- " . $index['name'] . "\n";
    }

    // Create a new index (if needed)
    $indexName = 'example-index';

    echo "\nCreating index '{$indexName}'...\n";
    $pinecone->createIndex($indexName, [
        'dimension' => 1536,
        'metric' => 'cosine',
        'spec' => [
            'serverless' => [
                'cloud' => 'aws',
                'region' => 'us-east-1'
            ]
        ]
    ]);

    // Wait a moment for index to be ready
    echo "Waiting for index to be ready...\n";
    sleep(5);

    // Get index reference
    $index = $pinecone->index($indexName);

    // Upsert some vectors
    echo "Upserting vectors...\n";
    $vectors = [
        [
            'id' => 'vec1',
            'values' => array_fill(0, 1536, 0.1),
            'metadata' => ['category' => 'A', 'year' => 2023]
        ],
        [
            'id' => 'vec2',
            'values' => array_fill(0, 1536, 0.2),
            'metadata' => ['category' => 'B', 'year' => 2024]
        ],
        [
            'id' => 'vec3',
            'values' => array_fill(0, 1536, 0.3),
            'metadata' => ['category' => 'A', 'year' => 2024]
        ]
    ];

    $upsertResult = $index->upsert($vectors);
    echo "Upserted {$upsertResult['upsertedCount']} vectors\n";

    // Query vectors
    echo "\nQuerying vectors...\n";
    $queryVector = array_fill(0, 1536, 0.15);
    $queryResults = $index->query(
        vector: $queryVector,
        topK: 3,
        includeMetadata: true
    );

    echo "Found {$queryResults['matches']} matches:\n";
    foreach ($queryResults['matches'] as $match) {
        echo "- ID: {$match['id']}, Score: {$match['score']}\n";
        if (isset($match['metadata'])) {
            echo "  Metadata: " . json_encode($match['metadata']) . "\n";
        }
    }

    // Fetch specific vectors
    echo "\nFetching specific vectors...\n";
    $fetchResult = $index->fetch(['vec1', 'vec2']);
    echo "Fetched " . count($fetchResult['vectors']) . " vectors\n";

    // Get index statistics
    echo "\nIndex statistics:\n";
    $stats = $index->describeIndexStats();
    echo "Total vector count: {$stats['totalVectorCount']}\n";
    echo "Index fullness: {$stats['indexFullness']}\n";

    // Clean up - delete the index
    echo "\nCleaning up - deleting index...\n";
    $pinecone->deleteIndex($indexName);
    echo "Index deleted successfully\n";

} catch (PineconeException $e) {
    echo "Pinecone error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General error: " . $e->getMessage() . "\n";
}
