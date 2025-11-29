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
$indexNamespace = $index->namespace('test-namespace');

// Upsert vectors
$indexNamespace->upsert([
    [
        'id' => 'vec1',
        'values' => [0.1, 0.2, 0.3, /* ... more dimensions */],
        'metadata' => ['genre' => 'comedy', 'year' => 2020]
    ]
]);

// Query vectors
$results = $indexNamespace->query(
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

- **Index Management**: Create, describe, list, and delete indexes
- **Vector Operations**: Upsert, query, fetch, update, and delete vectors
- **Metadata Filtering**: Filter vectors by metadata
- **Namespaces**: Organize vectors within indexes
- **Inference API**: Generate embeddings and rerank documents
- **Assistant API**: Chat with AI assistants

## Requirements

- PHP 8.1 or higher
- Guzzle HTTP client

## Examples

### Assistant API

Create and chat with AI assistants:

```php
<?php
use Mbvb1223\Pinecone\Pinecone;

$pinecone = new Pinecone('your-api-key');

// Create an assistant
$assistant = $pinecone->createAssistant('my-assistant', [
    'instructions' => 'You are a helpful customer support bot for an e-commerce store.',
    'model' => 'gpt-4'
]);

// Chat with the assistant
$response = $pinecone->chat('my-assistant', [
    ['role' => 'user', 'content' => 'How do I return an item?']
]);

echo $response['choices'][0]['message']['content'];

// List all assistants
$assistants = $pinecone->listAssistants();
foreach ($assistants as $assistant) {
    echo "Assistant: " . $assistant['name'] . "\n";
}

// Delete an assistant
$pinecone->deleteAssistant('my-assistant');
```

### Inference API

Generate embeddings and rerank documents:

```php
<?php
use Mbvb1223\Pinecone\Pinecone;

$pinecone = new Pinecone('your-api-key');

// Generate embeddings
$embeddings = $pinecone->embed('multilingual-e5-large', [
    'The quick brown fox jumps over the lazy dog',
    'A journey of a thousand miles begins with a single step'
]);

echo "Generated " . count($embeddings['data']) . " embeddings\n";

// Rerank documents
$query = "What is machine learning?";
$documents = [
    'Machine learning is a subset of artificial intelligence',
    'Python is a programming language',
    'Deep learning uses neural networks'
];

$ranked = $pinecone->rerank('bge-reranker-v2-m3', $query, $documents, [
    'top_k' => 2
]);

foreach ($ranked['data'] as $result) {
    echo "Score: " . $result['score'] . " - " . $result['document']['text'] . "\n";
}

// List available models
$models = $pinecone->listModels();
foreach ($models as $model) {
    echo "Model: " . $model['name'] . " - " . $model['description'] . "\n";
}
```

### Metadata Filtering

Filter vectors by metadata:

```php
<?php
use Mbvb1223\Pinecone\Pinecone;

$pinecone = new Pinecone('your-api-key');
$index = $pinecone->index('my-index');
$namespace = $index->namespace('products');

// Upsert vectors with metadata
$namespace->upsert([
    [
        'id' => 'product-1',
        'values' => [0.1, 0.2, 0.3, /* ... */],
        'metadata' => ['category' => 'electronics', 'price' => 299.99, 'brand' => 'TechCorp']
    ],
    [
        'id' => 'product-2', 
        'values' => [0.4, 0.5, 0.6, /* ... */],
        'metadata' => ['category' => 'books', 'price' => 19.99, 'author' => 'John Doe']
    ]
]);

// Query with metadata filters
$results = $namespace->query(
    vector: [0.1, 0.2, 0.3, /* ... */],
    topK: 10,
    filter: [
        'category' => ['$eq' => 'electronics'],
        'price' => ['$lt' => 500]
    ],
    includeMetadata: true
);

// Complex filter with logical operators
$complexResults = $namespace->query(
    vector: [0.1, 0.2, 0.3, /* ... */],
    topK: 5,
    filter: [
        '$and' => [
            ['category' => ['$in' => ['electronics', 'computers']]],
            ['price' => ['$gte' => 100, '$lte' => 1000]]
        ]
    ],
    includeMetadata: true
);
```

### Advanced Vector Operations

Perform batch operations and updates:

```php
<?php
use Mbvb1223\Pinecone\Pinecone;

$pinecone = new Pinecone('your-api-key');
$index = $pinecone->index('my-index');
$namespace = $index->namespace('documents');

// Batch upsert with progress tracking
$batchSize = 100;
$vectors = []; // Your large vector dataset

for ($i = 0; $i < count($vectors); $i += $batchSize) {
    $batch = array_slice($vectors, $i, $batchSize);
    $namespace->upsert($batch);
    echo "Uploaded batch " . ($i / $batchSize + 1) . "\n";
}

// Update vector values
$namespace->update('doc-123', [0.9, 0.8, 0.7, /* ... */], [
    'updated_at' => date('Y-m-d H:i:s'),
    'version' => 2
]);

// Fetch specific vectors
$vectors = $namespace->fetch(['doc-123', 'doc-456', 'doc-789']);
foreach ($vectors['vectors'] as $id => $vector) {
    echo "Vector $id has " . count($vector['values']) . " dimensions\n";
}

// Delete vectors by ID
$namespace->delete(['doc-old-1', 'doc-old-2']);

// Delete vectors by metadata filter
$namespace->delete([], [
    'status' => ['$eq' => 'archived']
]);

// Get index statistics
$stats = $index->describeIndexStats();
echo "Total vectors: " . $stats['totalVectorCount'] . "\n";
echo "Index fullness: " . ($stats['indexFullness'] * 100) . "%\n";
```

### Error Handling

Handle API errors gracefully:

```php
<?php
use Mbvb1223\Pinecone\Pinecone;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Errors\PineconeApiException;

$pinecone = new Pinecone('your-api-key');

try {
    // Attempt to create an index
    $index = $pinecone->createIndex('my-index', [
        'dimension' => 1536,
        'metric' => 'cosine',
        'spec' => [
            'serverless' => [
                'cloud' => 'aws',
                'region' => 'us-east-1'
            ]
        ]
    ]);
} catch (PineconeApiException $e) {
    // Handle specific API errors
    if ($e->getCode() === 409) {
        echo "Index already exists\n";
    } elseif ($e->getCode() === 401) {
        echo "Invalid API key\n";
    } else {
        echo "API Error: " . $e->getMessage() . "\n";
    }
} catch (PineconeException $e) {
    // Handle general Pinecone errors
    echo "Pinecone Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    // Handle any other errors
    echo "Unexpected Error: " . $e->getMessage() . "\n";
}

// Retry logic example
$maxRetries = 3;
$retryCount = 0;

while ($retryCount < $maxRetries) {
    try {
        $index = $pinecone->index('my-index');
        $results = $index->namespace('docs')->query(
            vector: [0.1, 0.2, 0.3, /* ... */],
            topK: 10
        );
        break; // Success, exit retry loop
    } catch (PineconeException $e) {
        $retryCount++;
        if ($retryCount >= $maxRetries) {
            throw $e; // Re-throw after max retries
        }
        sleep(pow(2, $retryCount)); // Exponential backoff
    }
}
```

## Code coverage

<a href="https://mbvb1223.github.io/pinecone-php-client/" target="_blank" rel="noopener noreferrer">https://mbvb1223.github.io/pinecone-php-client/</a>

## License

This project is licensed under the Apache-2.0 License.
