<?php

declare(strict_types=1);

namespace Tests;

use Ancarda\Psr7\StringStream\StringStream;
use PHPUnit\Framework\TestCase;

class StringStreamTest extends TestCase
{
    public function testRead(): void
    {
        $stringStream = new StringStream('hello world');

        static::assertTrue($stringStream->isReadable());

        // getContents will read until the end, so the next time round it should be empty
        static::assertSame('hello world', $stringStream->getContents());
        static::assertSame('', $stringStream->getContents());

        // Simple functions
        static::assertSame(11, $stringStream->getSize());

        // __toString always returns everything
        static::assertSame('hello world', (string) $stringStream);
        static::assertSame('hello world', (string) $stringStream);
    }

    public function testSeeking(): void
    {
        $stringStream = new StringStream('hello world');

        static::assertTrue($stringStream->isSeekable());

        // We should start at 0
        static::assertSame(0, $stringStream->tell());
        static::assertFalse($stringStream->eof());

        // Read the first 5 bytes.
        static::assertSame('hello', $stringStream->read(5));
        static::assertSame(5, $stringStream->tell());
        static::assertFalse($stringStream->eof());

        // Read till the rest of the file with getContents
        static::assertSame(' world', $stringStream->getContents());
        static::assertTrue($stringStream->eof());
        static::assertSame($stringStream->getSize(), $stringStream->tell());

        // Now rewind and do it all again
        $stringStream->rewind();
        static::assertSame(0, $stringStream->tell());

        // Finally, test more esoteric seeking
        $stringStream->seek(2, SEEK_SET);
        static::assertSame(2, $stringStream->tell());
        $stringStream->seek(3, SEEK_CUR);
        static::assertSame(5, $stringStream->tell());
        $stringStream->seek(2, SEEK_END);
        static::assertSame(13, $stringStream->tell());

        // @TODO(ancarda): The specification doesn't say what to do for an unknown $whence value.
        // Given an invalid SEEK flag (oh how I wish PHP had enums...) what should the implementation do?
        // It seems it could either do nothing or do the default behavior (SEEK_SET).
        // This implementation does nothing as that appears to be the correct behavior (going by what fseek() does).
        $stringStream->rewind();
        $stringStream->seek(2);
        $stringStream->seek(5, -1);
        static::assertSame(2, $stringStream->tell()); // Do nothing when whence is invalid
    }

    public function testWriting(): void
    {
        $stringStream = new StringStream('hello world');
        $fullString = 'hello world, isn\'t it a lovely day';

        static::assertTrue($stringStream->isWritable());
        $bytesWritten = $stringStream->write(', isn\'t it a lovely day');
        static::assertSame(strlen($fullString), $stringStream->getSize());
        static::assertSame($fullString, (string) $stringStream);
        static::assertSame(strlen(', isn\'t it a lovely day'), $bytesWritten);
    }

    public function testMiscFunctions(): void
    {
        $stringStream = new StringStream('hello world');

        // These functions do nothing as we don't use strings.
        static::assertNull($stringStream->detach());
        static::assertNull($stringStream->getMetadata());
    }

    public function testClose(): void
    {
        $stringStream = new StringStream('hello world');

        $stringStream->close();
        static::assertSame(0, $stringStream->getSize());
        static::assertSame(0, $stringStream->tell());
        static::assertTrue($stringStream->eof());
    }
}