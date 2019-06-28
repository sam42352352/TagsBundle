<?php

declare(strict_types=1);

namespace Netgen\TagsBundle\Tests\Core\Pagination\Pagerfanta;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\Repository\Values\Content\Content;
use Netgen\TagsBundle\API\Repository\TagsService;
use Netgen\TagsBundle\API\Repository\Values\Tags\Tag;
use Netgen\TagsBundle\Core\Pagination\Pagerfanta\RelatedContentAdapter;
use PHPUnit\Framework\TestCase;

final class RelatedContentAdapterTest extends TestCase
{
    /**
     * @var \Netgen\TagsBundle\API\Repository\TagsService&\PHPUnit\Framework\MockObject\MockObject
     */
    private $tagsService;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $configResolver;

    protected function setUp(): void
    {
        $this->tagsService = $this->createMock(TagsService::class);
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
    }

    /**
     * @covers \Netgen\TagsBundle\Core\Pagination\Pagerfanta\RelatedContentAdapter::__construct
     * @covers \Netgen\TagsBundle\Core\Pagination\Pagerfanta\RelatedContentAdapter::getNbResults
     * @covers \Netgen\TagsBundle\Core\Pagination\Pagerfanta\RelatedContentAdapter::setTag
     */
    public function testGetNbResults(): void
    {
        $nbResults = 4;

        $tag = new Tag(
            [
                'id' => 42,
            ]
        );

        $this->tagsService
            ->expects(self::once())
            ->method('getRelatedContentCount')
            ->with(self::equalTo($tag))
            ->willReturn($nbResults);

        $adapter = $this->getAdapter($tag, $this->tagsService);
        self::assertSame($nbResults, $adapter->getNbResults());

        // Running a 2nd time to ensure TagsService::getRelatedContentCount() is called only once.
        self::assertSame($nbResults, $adapter->getNbResults());
    }

    /**
     * @covers \Netgen\TagsBundle\Core\Pagination\Pagerfanta\RelatedContentAdapter::__construct
     * @covers \Netgen\TagsBundle\Core\Pagination\Pagerfanta\RelatedContentAdapter::getNbResults
     * @covers \Netgen\TagsBundle\Core\Pagination\Pagerfanta\RelatedContentAdapter::setTag
     */
    public function testGetNbResultsWithoutTag(): void
    {
        $this->tagsService
            ->expects(self::never())
            ->method('getRelatedContentCount');

        $adapter = new RelatedContentAdapter($this->tagsService, $this->configResolver);
        self::assertSame(0, $adapter->getNbResults());
    }

    /**
     * @covers \Netgen\TagsBundle\Core\Pagination\Pagerfanta\RelatedContentAdapter::__construct
     * @covers \Netgen\TagsBundle\Core\Pagination\Pagerfanta\RelatedContentAdapter::getSlice
     * @covers \Netgen\TagsBundle\Core\Pagination\Pagerfanta\RelatedContentAdapter::setTag
     */
    public function testGetSlice(): void
    {
        $offset = 2;
        $limit = 2;
        $nbResults = 5;

        $tag = new Tag(
            [
                'id' => 42,
            ]
        );

        $relatedContent = [
            new Content(
                [
                    'internalFields' => [],
                ]
            ),
            new Content(
                [
                    'internalFields' => [],
                ]
            ),
        ];

        $this->tagsService
            ->expects(self::once())
            ->method('getRelatedContentCount')
            ->with(self::equalTo($tag))
            ->willReturn($nbResults);

        $this->tagsService
            ->expects(self::once())
            ->method('getRelatedContent')
            ->with(
                self::equalTo($tag),
                self::equalTo($offset),
                self::equalTo($limit)
            )
            ->willReturn($relatedContent);

        $this->configResolver
            ->expects(self::any())
            ->method('getParameter')
            ->with(
                self::identicalTo('tag_view.related_content_list.return_content_info'),
                self::identicalTo('eztags')
            )
            ->willReturn(true);

        $adapter = $this->getAdapter($tag, $this->tagsService);

        self::assertSame($relatedContent, $adapter->getSlice($offset, $limit));
        self::assertSame($nbResults, $adapter->getNbResults());
    }

    /**
     * @covers \Netgen\TagsBundle\Core\Pagination\Pagerfanta\RelatedContentAdapter::__construct
     * @covers \Netgen\TagsBundle\Core\Pagination\Pagerfanta\RelatedContentAdapter::getSlice
     * @covers \Netgen\TagsBundle\Core\Pagination\Pagerfanta\RelatedContentAdapter::setTag
     */
    public function testGetSliceWithoutTag(): void
    {
        $this->tagsService
            ->expects(self::never())
            ->method('getRelatedContentCount');

        $this
            ->tagsService
            ->expects(self::never())
            ->method('getRelatedContent');

        $adapter = new RelatedContentAdapter($this->tagsService, $this->configResolver);

        self::assertCount(0, $adapter->getSlice(2, 2));
    }

    /**
     * Returns the adapter to test.
     */
    private function getAdapter(Tag $tag, TagsService $tagsService): RelatedContentAdapter
    {
        $adapter = new RelatedContentAdapter($tagsService, $this->configResolver);
        $adapter->setTag($tag);

        return $adapter;
    }
}
