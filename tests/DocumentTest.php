<?php namespace Vaites\ApacheTika\Tests;

/**
 * Test for documents
 */
class DocumentTest extends BaseTest
{
    /**
     * Metadata test
     *
     * @dataProvider    fileProvider
     * @param   string  $file
     */
    public function testMetadata($file, $class = 'Metadata')
    {
        parent::testMetadata($file, 'DocumentMetadata');
    }

    /**
     * Metadata title test
     *
     * @dataProvider    fileProvider
     * @param   string  $file
     */
    public function testMetadataTitle($file)
    {
        $this->assertEquals
        (
            self::$client->getMetadata($file)->title,
            'Lorem ipsum dolor sit amet'
        );
    }

    /**
     * Metadata author test
     *
     * @dataProvider    fileProvider
     * @param   string  $file
     */
    public function testMetadataAuthor($file)
    {
        $this->assertEquals
        (
            self::$client->getMetadata($file)->author,
            'David Martínez'
        );
    }

    /**
     * Metadata dates test
     *
     * @dataProvider    fileProvider
     * @param   string  $file
     */
    public function testMetadataCreated($file)
    {
        $this->assertInstanceOf
        (
            'DateTime',
            self::$client->getMetadata($file)->created
        );
    }

    /**
     * Metadata dates test
     *
     * @dataProvider    fileProvider
     * @param   string  $file
     */
    public function testMetadataUpdated($file)
    {
        $this->assertInstanceOf
        (
            'DateTime',
            self::$client->getMetadata($file)->updated
        );
    }

    /**
     * Metadata keywords test
     *
     * @dataProvider    fileProvider
     * @param   string  $file
     */
    public function testMetadataKeywords($file)
    {
        $this->assertContains
        (
            'ipsum',
            self::$client->getMetadata($file)->keywords
        );
    }

    /**
     * Language test
     *
     * @dataProvider    fileProvider
     * @param   string  $file
     */
    public function testLanguage($file)
    {
        $this->assertRegExp
        (
            '/^[a-z]{2}$/',
            self::$client->getLanguage($file)
        );
    }

    /**
     * MIME test
     *
     * @dataProvider    fileProvider
     * @param   string  $file
     */
    public function testMIME($file)
    {
        $this->assertNotEmpty(self::$client->getMIME($file));
    }

    /**
     * HTML test
     *
     * @dataProvider    fileProvider
     * @param   string  $file
     */
    public function testHTML($file)
    {
        $this->assertContains
        (
            'Zenonis est, inquam, hoc Stoici',
            self::$client->getHTML($file)
        );
    }

    /**
     * Text test
     *
     * @dataProvider    fileProvider
     * @param   string  $file
     */
    public function testText($file)
    {
        $this->assertContains
        (
            'Zenonis est, inquam, hoc Stoici',
            self::$client->getText($file)
        );
    }

    /**
     * File provider using "samples" folder
     *
     * @return array
     */
    public function fileProvider()
    {
        return $this->samples('sample1');
    }
}