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

// Upsert vectors
$index->upsert([
    [
        'id' => 'vec1',
        'values' => [0.1, 0.2, 0.3, /* ... more dimensions */],
        'metadata' => ['genre' => 'comedy', 'year' => 2020]
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

## License

This project is licensed under the Apache-2.0 License.
