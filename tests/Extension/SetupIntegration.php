<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Extension;

use Mbvb1223\Pinecone\Pinecone;
use Mbvb1223\Pinecone\Tests\Integration\Base\BaseIntegrationTestCase;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

class SetupIntegration implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        echo "\n🚀 Starting Integration Test Suite Setup...\n";
        $pinecone = new Pinecone();
        foreach (BaseIntegrationTestCase::INDEX_NAMES as $index) {
            try {
                $pinecone->deleteIndex($index);
            } catch (\Exception $exception) {
            }
        }
        sleep(5);
    }
}
