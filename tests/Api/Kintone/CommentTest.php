<?php

namespace Api\Kintone;

require_once __DIR__ . '/../../_support/KintoneTestHelper.php';
use KintoneTestHelper;

use CybozuHttp\Api\KintoneApi;

/**
 * @author ochi51 <ochiai07@gmail.com>
 */
class CommentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var KintoneApi
     */
    private $api;

    /**
     * @var integer
     */
    private $spaceId;

    /**
     * @var array
     */
    private $space;

    /**
     * @var integer
     */
    private $guestSpaceId;

    /**
     * @var array
     */
    private $guestSpace;

    /**
     * @var integer
     */
    private $appId;

    /**
     * @var integer
     */
    private $guestAppId;

    protected function setup()
    {
        $this->api = KintoneTestHelper::getKintoneApi();
        $this->spaceId = KintoneTestHelper::createTestSpace();
        $this->space = $this->api->space()->get($this->spaceId);
        $this->guestSpaceId = KintoneTestHelper::createTestSpace(true);
        $this->guestSpace = $this->api->space()->get($this->guestSpaceId, $this->guestSpaceId);

        $this->appId = KintoneTestHelper::createTestApp($this->spaceId, $this->space['defaultThread']);
        $this->guestAppId = KintoneTestHelper::createTestApp($this->guestSpaceId, $this->guestSpace['defaultThread'], $this->guestSpaceId);

        $postRecord = KintoneTestHelper::getRecord();
        $this->api->record()->post($this->appId, KintoneTestHelper::getRecord());
        $this->api->record()->post($this->guestAppId, $postRecord, $this->guestSpaceId);
    }

    public function testComment()
    {
        $recordId = $this->api->record()->post($this->appId, KintoneTestHelper::getRecord())['id'];

        $id = $this->api->comment()->post($this->appId, $recordId, 'test comment')['id'];

        $comments = $this->api->comments()->get($this->appId, $recordId, 'desc', 0, 1);
        $comment = reset($comments);
        self::assertEquals($comment['id'], $id);
        self::assertEquals(rtrim(ltrim($comment['text'])), 'test comment');

        $this->api->comment()->delete($this->appId, $recordId, $id);
        $comments = $this->api->comments()->get($this->appId, $recordId);
        self::assertEquals(count($comments), 0);
    }

    public function testGuestComment()
    {
        $recordId = $this->api->record()->post(
            $this->guestAppId,
            KintoneTestHelper::getRecord(),
            $this->guestSpaceId)['id'];
        $id = $this->api->comment()->post(
            $this->guestAppId,
            $recordId,
            'test comment',
            [],
            $this->guestSpaceId)['id'];

        $comments = $this->api->comments()->get(
            $this->guestAppId,
            $recordId,
            'desc',
            0,
            1,
            $this->guestSpaceId);
        $comment = reset($comments);
        self::assertEquals($comment['id'], $id);
        self::assertEquals(rtrim(ltrim($comment['text'])), 'test comment');

        $this->api->comment()->delete($this->guestAppId, $recordId, $id, $this->guestSpaceId);
        $comments = $this->api->comments()->get(
            $this->guestAppId,
            $recordId,
            'desc',
            0,
            10,
            $this->guestSpaceId);
        self::assertEquals(count($comments), 0);
    }

    protected function tearDown()
    {
        $this->api->space()->delete($this->spaceId);
        $this->api->space()->delete($this->guestSpaceId, $this->guestSpaceId);
    }
}
