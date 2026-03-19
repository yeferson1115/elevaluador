<?php

namespace Tests\Unit;

use App\Jobs\GenerateCertificadosZipJob;
use PHPUnit\Framework\TestCase;

class GenerateCertificadosZipJobTest extends TestCase
{
    public function test_it_accepts_a_null_filter_when_dispatching_the_job(): void
    {
        $job = new GenerateCertificadosZipJob(123, null, [1, 2, 3], true);

        $this->assertInstanceOf(GenerateCertificadosZipJob::class, $job);
        $this->assertSame('database', $job->connection);
        $this->assertSame('exports', $job->queue);
    }
}
