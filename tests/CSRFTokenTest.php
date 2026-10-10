<?php

declare(strict_types=1);

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class CSRFTokenTest extends TestCase
{
    public function testDefinesTheTokenModule(): void
    {
        BigPipe::setCSRFToken('s3cr3t');

        $this->assertSame([
            ['CSRFToken', ['token' => 's3cr3t', 'header' => 'X-CSRF-TOKEN', 'param' => null]],
        ], BigPipe::jsmods()['define']);
    }

    public function testSendsTheTokenAsAParameter(): void
    {
        BigPipe::setCSRFToken('s3cr3t', header: null, param: '_token');

        $this->assertSame(
            ['token' => 's3cr3t', 'header' => null, 'param' => '_token'],
            BigPipe::jsmods()['define'][0][1]
        );
    }

    public function testIsPartOfTheAsyncResponse(): void
    {
        BigPipe::setCSRFToken('n3w');
        $response = new AsyncResponse();

        $this->assertSame('CSRFToken', $response->getResponse()['jsmods']['define'][0][0]);
    }

    public function testRendersTheTokenInThePageScript(): void
    {
        BigPipe::setCSRFToken('s3cr3t');

        $this->assertStringContainsString(
            '"define":[["CSRFToken",{"token":"s3cr3t","header":"X-CSRF-TOKEN","param":null}]]',
            BigPipe::render()
        );
    }

    public function testNeedsATokenAndAPlaceForIt(): void
    {
        $this->expectException(BigPipeInvalidArgumentException::class);

        BigPipe::setCSRFToken('s3cr3t', header: null, param: null);
    }

    public function testRejectsAnEmptyToken(): void
    {
        $this->expectException(BigPipeInvalidArgumentException::class);

        BigPipe::setCSRFToken('');
    }

    public function testNamesTheUrlThatRefreshesTheToken(): void
    {
        BigPipe::setCSRFToken('s3cr3t', refreshUri: '/csrf-token');

        $this->assertSame(
            ['token' => 's3cr3t', 'header' => 'X-CSRF-TOKEN', 'param' => null, 'refresh' => '/csrf-token'],
            BigPipe::jsmods()['define'][0][1]
        );
    }

    public function testAResponseCanAskForTheRequestToBeSentAgain(): void
    {
        $response = (new AsyncResponse())->retryWithCSRFToken('n3w', refreshUri: '/csrf-token');

        $data = $response->getResponse();

        $this->assertSame(1, $data['csrf_refresh']);
        $this->assertSame(
            [['CSRFToken', ['token' => 'n3w', 'header' => 'X-CSRF-TOKEN', 'param' => null, 'refresh' => '/csrf-token']]],
            $data['jsmods']['define']
        );
    }

    public function testOtherResponsesDoNotAskForIt(): void
    {
        $this->assertArrayNotHasKey('csrf_refresh', (new AsyncResponse())->getResponse());
    }

    public function testRetryNeedsATokenToo(): void
    {
        $this->expectException(BigPipeInvalidArgumentException::class);

        (new AsyncResponse())->retryWithCSRFToken('');
    }
}
