# Pinecone PHP Client

A PHP SDK for [Pinecone](https://www.pinecone.io) vector database.

## Installation

Install the package via Composer:

```bash
composer require mbvb1223/pinecone-php-client
```

## Quick Start

```php
<?php
require 'vendor/autoload.php';

use Mbvb1223\Pinecone\Pinecone;

// Initialize client
$pinecone = new Pinecone('your-api-key');

// Create an index
$pinecone->createIndex('my-index', [
    'dimension' => 1536,
    'metric' => 'cosine',
    'spec' => [
        'serverless' => [
            'cloud' => 'aws',
            'region' => 'us-east-1'
        ]
    ]
]);

// Get index reference
$index = $pinecone->index('my-index');

// Upsert vectors directly on the index (default namespace)
$index->upsert([
    [
        'id' => 'vec1',
        'values' => [0.1, 0.2, 0.3, /* ... more dimensions */],
        'metadata' => ['genre' => 'comedy', 'year' => 2020]
    ]
]);

// Or use a specific namespace
$namespace = $index->namespace('test-namespace');
$namespace->upsert([
    [
        'id' => 'vec2',
        'values' => [0.4, 0.5, 0.6, /* ... */],
        'metadata' => ['genre' => 'drama', 'year' => 2021]
    ]
]);

// Query vectors
$results = $index->query(
    vector: [0.1, 0.2, 0.3, /* ... query vector */],
    topK: 10,
    includeMetadata: true
);
```

## Configuration

### Environment Variables

Set your API key as an environment variable:

```bash
export PINECONE_API_KEY="your-api-key"
```

Then initialize without passing the key:

```php
$pinecone = new Pinecone();
```

### Configuration Options

```php
$pinecone = new Pinecone('your-api-key', [
    'environment' => 'us-east1-aws',
    'controllerHost' => 'https://api.pinecone.io',
    'timeout' => 30,
    'additionalHeaders' => [
        'Custom-Header' => 'value'
    ]
]);
```

## Features

- **Index Management**: Create, describe, list, configure, and delete indexes
- **Vector Operations**: Upsert, query, fetch, update, delete, and list vector IDs
- **Sparse Vector Support**: Hybrid search with dense and sparse vectors
- **Metadata Filtering**: Filter vectors using rich query operators
- **Namespaces**: Organize vectors within indexes
- **Data Plane on Index**: Access vector operations directly on the index or via namespaces
- **Inference API**: Generate embeddings and rerank documents
- **Assistant API**: Chat with AI assistants
- **Collections**: Create and manage collections (pod-based indexes)
- **Backups & Restore**: Create backups and restore indexes
- **Bulk Import**: Import vectors in bulk

## Requirements

- PHP 8.1 or higher
- Guzzle HTTP client (`guzzlehttp/guzzle ^7.0`)

## API Reference

### Index Management

```php
$pinecone = new Pinecone('your-api-key');

// List all indexes
$indexes = $pinecone->listIndexes();

// Check if an index exists
if ($pinecone->hasIndex('my-index')) {
    echo "Index exists!\n";
}

// Create an index
$pinecone->createIndex('my-index', [
    'dimension' => 1536,
    'metric' => 'cosine',                    // optional, defaults to 'cosine'
    'spec' => [
        'serverless' => [
            'cloud' => 'aws',
            'region' => 'us-east-1'
        ]
    ],
    'deletion_protection' => 'enabled',       // optional
    'tags' => ['env' => 'production'],        // optional
]);

// Create an index for a specific embedding model
$pinecone->createForModel('my-model-index', [
    'cloud' => 'aws',
    'region' => 'us-east-1',
    'embed' => [
        'model' => 'multilingual-e5-large',
        'field_map' => ['text' => 'chunk_text']
    ]
]);

// Describe an index
$indexInfo = $pinecone->describeIndex('my-index');
echo "Host: " . $indexInfo['host'] . "\n";

// Configure an index
$pinecone->configureIndex('my-index', [
    'deletion_protection' => 'disabled',
]);

// Delete an index
$pinecone->deleteIndex('my-index');
```

### Vector Operations

Vector operations can be accessed directly on the `Index` object or through a namespace:

```php
$index = $pinecone->index('my-index');

// --- Direct operations on index (default namespace) ---

// Upsert vectors
$index->upsert([
    [
        'id' => 'vec1',
        'values' => [0.1, 0.2, 0.3],
        'metadata' => ['genre' => 'comedy']
    ],
    [
        'id' => 'vec2',
        'values' => [0.4, 0.5, 0.6],
        'metadata' => ['genre' => 'drama']
    ]
]);

// Query by vector
$results = $index->query(
    vector: [0.1, 0.2, 0.3],
    topK: 10,
    filter: ['genre' => ['$eq' => 'comedy']],
    includeMetadata: true,
    includeValues: false
);

// Query by vector ID
$results = $index->query(
    id: 'vec1',
    topK: 5,
    includeMetadata: true
);

// Fetch specific vectors
$vectors = $index->fetch(['vec1', 'vec2']);

// Update a vector
$index->update('vec1', values: [0.9, 0.8, 0.7], setMetadata: ['year' => 2024]);

// Delete vectors by ID
$index->delete(ids: ['vec1', 'vec2']);

// Delete by metadata filter
$index->delete(filter: ['genre' => ['$eq' => 'drama']]);

// Delete all vectors in a namespace
$index->delete(namespace: 'old-data', deleteAll: true);

// --- Operations through a namespace ---

$namespace = $index->namespace('my-namespace');
$namespace->upsert([/* vectors */]);
$namespace->query(vector: [0.1, 0.2], topK: 5);
$namespace->fetch(['vec1']);
$namespace->update('vec1', values: [0.9, 0.8]);
$namespace->delete(ids: ['vec1']);
```

### List Vector IDs (Serverless Only)

```php
$index = $pinecone->index('my-index');

// List all vector IDs
$result = $index->listVectorIds();

// List with prefix filter and pagination
$result = $index->listVectorIds(
    prefix: 'doc1#',
    limit: 100,
    namespace: 'my-namespace'
);

foreach ($result['vectors'] as $vector) {
    echo $vector['id'] . "\n";
}

// Paginate through all results
$paginationToken = $result['pagination']['next'] ?? null;
while ($paginationToken) {
    $result = $index->listVectorIds(paginationToken: $paginationToken);
    // process results...
    $paginationToken = $result['pagination']['next'] ?? null;
}

// Also available on namespaces
$namespace = $index->namespace('my-namespace');
$result = $namespace->listVectorIds(prefix: 'doc1#', limit: 50);
```

### Sparse Vector Support (Hybrid Search)

```php
$index = $pinecone->index('my-index');

// Query with both dense and sparse vectors for hybrid search
$results = $index->query(
    vector: [0.1, 0.2, 0.3],
    topK: 10,
    sparseVector: [
        'indices' => [0, 3, 5],
        'values' => [0.5, 0.3, 0.8]
    ],
    includeMetadata: true
);

// Also available on namespaces
$namespace = $index->namespace('my-namespace');
$results = $namespace->query(
    vector: [0.1, 0.2, 0.3],
    topK: 10,
    sparseVector: [
        'indices' => [1, 4],
        'values' => [0.7, 0.2]
    ]
);
```

### Namespace & Index Stats

```php
$index = $pinecone->index('my-index');

// Get index statistics
$stats = $index->describeIndexStats();
echo "Total vectors: " . $stats['totalVectorCount'] . "\n";
echo "Dimension: " . $stats['dimension'] . "\n";

// List all namespaces
$namespaces = $index->listNamespaces();
foreach ($namespaces as $ns) {
    echo "Namespace: $ns\n";
}

// Get stats for a specific namespace
$nsStats = $index->describeNamespace('my-namespace');
echo "Vectors in namespace: " . $nsStats['vectorCount'] . "\n";

// Delete all vectors in a namespace
$index->deleteNamespace('old-namespace');
```

### Bulk Import

```php
$index = $pinecone->index('my-index');

// Start a bulk import
$import = $index->startImport([
    'uri' => 's3://my-bucket/vectors/',
    'integration_id' => 'my-integration'
]);

// List imports
$imports = $index->listImports();

// Check import status
$status = $index->describeImport($import['id']);

// Cancel an import
$index->cancelImport($import['id']);
```

### Inference API

Generate embeddings and rerank documents:

```php
$inference = $pinecone->inference();

// Generate embeddings
$embeddings = $inference->embed('multilingual-e5-large', [
    ['text' => 'The quick brown fox jumps over the lazy dog'],
    ['text' => 'A journey of a thousand miles begins with a single step']
]);

echo "Generated " . count($embeddings['data']) . " embeddings\n";

// Rerank documents
$ranked = $inference->rerank(
    model: 'bge-reranker-v2-m3',
    query: 'What is machine learning?',
    documents: [
        ['text' => 'Machine learning is a subset of artificial intelligence'],
        ['text' => 'Python is a programming language'],
        ['text' => 'Deep learning uses neural networks']
    ],
    topN: 2,
    returnDocuments: true,
    rankFields: ['text']
);

foreach ($ranked['data'] as $result) {
    echo "Score: " . $result['score'] . "\n";
}

// List available models
$models = $inference->listModels();
```

### Assistant API

Create and chat with AI assistants:

```php
// Create an assistant via the control plane
$pinecone->createAssistant([
    'name' => 'my-assistant',
    'instructions' => 'You are a helpful customer support bot.',
]);

// Get an assistant client (resolves host automatically)
$assistant = $pinecone->assistant('my-assistant');

// Chat with the assistant
$response = $assistant->chat('my-assistant', [
    ['role' => 'user', 'content' => 'How do I return an item?']
]);

echo $response['choices'][0]['message']['content'];

// List all assistants
$assistants = $pinecone->listAssistants();

// Update an assistant
$pinecone->updateAssistant('my-assistant', [
    'instructions' => 'Updated instructions here.',
]);

// Delete an assistant
$pinecone->deleteAssistant('my-assistant');
```

### Collections (Pod-based Indexes)

```php
// Create a collection from an existing index
$pinecone->createCollection([
    'name' => 'my-collection',
    'source' => 'my-pod-index'
]);

// List collections
$collections = $pinecone->listCollections();

// Describe a collection
$info = $pinecone->describeCollection('my-collection');

// Delete a collection
$pinecone->deleteCollection('my-collection');
```

### Backups & Restore

```php
// Create a backup
$backup = $pinecone->createBackup([
    'source_index_name' => 'my-index',
    'name' => 'my-backup',
]);

// List backups
$backups = $pinecone->listBackups();

// Describe a backup
$info = $pinecone->describeBackup($backup['backup_id']);

// List restore jobs
$jobs = $pinecone->listRestoreJobs();

// Delete a backup
$pinecone->deleteBackup($backup['backup_id']);
```

### Metadata Filtering

Filter vectors using Pinecone's query operators:

```php
$index = $pinecone->index('my-index');

// Equality
$results = $index->query(
    vector: [0.1, 0.2, 0.3],
    topK: 10,
    filter: ['genre' => ['$eq' => 'comedy']]
);

// Comparison operators: $eq, $ne, $gt, $gte, $lt, $lte
$results = $index->query(
    vector: [0.1, 0.2, 0.3],
    topK: 10,
    filter: ['year' => ['$gte' => 2020, '$lt' => 2024]]
);

// Set membership: $in, $nin
$results = $index->query(
    vector: [0.1, 0.2, 0.3],
    topK: 10,
    filter: ['genre' => ['$in' => ['comedy', 'drama']]]
);

// Logical operators: $and, $or
$results = $index->query(
    vector: [0.1, 0.2, 0.3],
    topK: 5,
    filter: [
        '$and' => [
            ['genre' => ['$eq' => 'comedy']],
            ['year' => ['$gte' => 2020]]
        ]
    ],
    includeMetadata: true
);
```

### Error Handling

```php
use Mbvb1223\Pinecone\Pinecone;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Errors\PineconeApiException;

$pinecone = new Pinecone('your-api-key');

try {
    $pinecone->createIndex('my-index', [
        'dimension' => 1536,
        'spec' => [
            'serverless' => ['cloud' => 'aws', 'region' => 'us-east-1']
        ]
    ]);
} catch (PineconeApiException $e) {
    // HTTP error from the API
    echo "Status: " . $e->getCode() . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Response: " . print_r($e->getResponseData(), true) . "\n";
} catch (PineconeException $e) {
    // General client error (network, JSON decode, etc.)
    echo "Error: " . $e->getMessage() . "\n";
}
```

**Exception hierarchy:**

| Exception | Description |
|-----------|-------------|
| `PineconeException` | Base exception for all errors |
| `PineconeApiException` | HTTP errors from the API (includes status code and response data) |
| `PineconeAuthException` | Authentication failures |
| `PineconeValidationException` | Input validation errors |
| `PineconeTimeoutException` | Request timeout errors |

## Code Coverage

<a href="https://mbvb1223.github.io/pinecone-php-client/" target="_blank" rel="noopener noreferrer">https://mbvb1223.github.io/pinecone-php-client/</a>

## License

This project is licensed under the Apache-2.0 License.
